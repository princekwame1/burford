<?php

namespace App\Http\Controllers\Susu;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\SusuCollection;
use App\Models\SusuCustomer;
use App\Services\SmsNotifier;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $collectorId = $this->ownCollectorId();
        $query = SusuCollection::with('customer', 'collector')
            ->when($collectorId !== null, fn ($q) => $q->where('collector_id', $collectorId))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('receipt_no', 'like', "%$s%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"))))
            ->when($request->collector, fn ($q, $c) => $q->where('collector_id', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->method, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d));

        $totals = (clone $query)->selectRaw('SUM(expected) e, SUM(collected) c, SUM(outstanding) o')->first();

        return view('susu.collections.index', [
            'collections' => $query->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'collectors' => Collector::orderBy('name')->get(),
            'totals' => $totals,
        ]);
    }

    public function create(Request $request)
    {
        return view('susu.collections.form', [
            'collection' => new SusuCollection([
                'receipt_no' => $this->nextReceipt(), 'date' => today(), 'payment_method' => 'Cash',
                'susu_customer_id' => $request->customer,
            ]),
            'customers' => $this->customers(),
            'collectors' => Collector::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, SmsNotifier $sms)
    {
        $data = $this->validated($request);
        $collection = SusuCollection::create($data + ['recorded_by' => auth()->id()]);
        if ($request->boolean('send_sms', true)) {
            $sms->collectionRecorded($collection);
        }

        return redirect()->route($request->input('after') === 'new' ? 'susu.collections.create' : 'susu.collections.index')
            ->with('success', "Receipt {$collection->receipt_no} saved — ".$collection->status.'.');
    }

    /** Collection sheet: one screen per collector/day listing every active customer, like the paper card. */
    public function sheet(Request $request)
    {
        $own = $this->ownCollectorId();
        $collectorId = $own ?: $request->integer('collector') ?: Collector::where('status', 'Active')->value('id');
        $date = $request->date('date') ?? today();

        $customers = SusuCustomer::where('status', 'Active')->where('collector_id', $collectorId)->orderBy('name')->get();
        $existing = SusuCollection::whereDate('date', $date)->where('collector_id', $collectorId)
            ->get()->keyBy('susu_customer_id');

        return view('susu.collections.sheet', [
            'collectors' => Collector::where('status', 'Active')->orderBy('name')->get(),
            'collectorId' => $collectorId,
            'date' => $date,
            'customers' => $customers,
            'existing' => $existing,
            'lockCollector' => $own !== null,
        ]);
    }

    public function storeSheet(Request $request, SmsNotifier $sms)
    {
        $data = $request->validate([
            'collector_id' => ['required', 'exists:collectors,id'],
            'date' => ['required', 'date'],
            'rows' => ['array'],
            'rows.*.collected' => ['nullable', 'numeric', 'min:0'],
            'rows.*.expected' => ['nullable', 'numeric', 'min:0'],
            'rows.*.method' => ['nullable', 'in:Cash,Mobile Money,Bank'],
        ]);
        if (($own = $this->ownCollectorId()) !== null) {
            abort_unless((int) $data['collector_id'] === $own, 403);
        }

        $saved = collect();
        DB::transaction(function () use ($data, &$saved) {
            foreach ($data['rows'] ?? [] as $customerId => $row) {
                if (($row['collected'] ?? '') === '' || $row['collected'] === null) {
                    continue;
                }
                $customer = SusuCustomer::where('collector_id', $data['collector_id'])->find($customerId);
                if (! $customer) {
                    continue;
                }
                $saved->push(SusuCollection::create([
                    'receipt_no' => $this->nextReceipt(),
                    'date' => $data['date'],
                    'susu_customer_id' => $customer->id,
                    'collector_id' => $data['collector_id'],
                    'expected' => $row['expected'] ?? $customer->contribution,
                    'collected' => $row['collected'],
                    'payment_method' => $row['method'] ?? 'Cash',
                    'recorded_by' => auth()->id(),
                ]));
            }
        });

        if ($request->boolean('send_sms', true)) {
            $saved->each(fn ($c) => $sms->collectionRecorded($c));
        }

        return redirect()->route('susu.collections.sheet', ['collector' => $data['collector_id'], 'date' => $data['date']])
            ->with('success', $saved->count().' collection(s) recorded.');
    }

    public function edit(SusuCollection $collection)
    {
        $this->authorizeRow($collection);

        return view('susu.collections.form', [
            'collection' => $collection,
            'customers' => $this->customers(),
            'collectors' => Collector::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SusuCollection $collection)
    {
        $this->authorizeRow($collection);
        $collection->update($this->validated($request, $collection));

        return redirect()->route('susu.collections.index')->with('success', 'Receipt updated.');
    }

    public function destroy(SusuCollection $collection)
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);
        $collection->delete();

        return back()->with('success', 'Receipt deleted.');
    }

    public function receipt(SusuCollection $collection)
    {
        $this->authorizeRow($collection);
        $collection->load('customer', 'collector');

        return view('susu.collections.receipt', ['c' => $collection, 'balance' => $collection->customer->balance()]);
    }

    protected function validated(Request $request, ?SusuCollection $collection = null): array
    {
        $data = $request->validate([
            'receipt_no' => ['required', 'string', 'max:30', 'unique:susu_collections,receipt_no'.($collection ? ','.$collection->id : '')],
            'date' => ['required', 'date'],
            'susu_customer_id' => ['required', 'exists:susu_customers,id'],
            'collector_id' => ['nullable', 'exists:collectors,id'],
            'expected' => ['required', 'numeric', 'min:0'],
            'collected' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:Cash,Mobile Money,Bank'],
            'verified_by' => ['nullable', 'string', 'max:80'],
            'deposit_ref' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = SusuCustomer::find($data['susu_customer_id']);
        $data['collector_id'] ??= $customer->collector_id;
        if (($own = $this->ownCollectorId()) !== null) {
            abort_unless($customer->collector_id === $own, 403);
            $data['collector_id'] = $own;
        }

        return $data;
    }

    protected function customers()
    {
        $own = $this->ownCollectorId();

        return SusuCustomer::where('status', 'Active')
            ->when($own !== null, fn ($q) => $q->where('collector_id', $own))
            ->orderBy('name')->get(['id', 'code', 'name', 'contribution', 'collector_id']);
    }

    protected function nextReceipt(): string
    {
        return Sequence::next(SusuCollection::class, 'receipt_no', 'RCP-', 4, 1001);
    }

    protected function authorizeRow(SusuCollection $collection): void
    {
        $own = $this->ownCollectorId();
        abort_if($own !== null && $collection->collector_id !== $own, 403);
    }
}
