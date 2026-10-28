<?php

namespace App\Http\Controllers\Susu;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\SusuCustomer;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $collectorId = $this->ownCollectorId();

        $customers = SusuCustomer::with('collector')
            ->withSum('collections as total_collected', 'collected')
            ->withSum('withdrawals as total_withdrawn', 'amount')
            ->withSum('withdrawals as total_commission', 'commission')
            ->when($collectorId !== null, fn ($q) => $q->where('collector_id', $collectorId))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->when($request->collector, fn ($q, $c) => $q->where('collector_id', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('code')
            ->paginate(20)->withQueryString();

        return view('susu.customers.index', [
            'customers' => $customers,
            'collectors' => Collector::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('susu.customers.form', [
            'customer' => new SusuCustomer(['code' => Sequence::next(SusuCustomer::class, 'code', 'CUS'),
                'start_date' => today(), 'status' => 'Active', 'plan' => 'Daily', 'frequency' => 'Daily']),
            'collectors' => Collector::where('status', 'Active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $customer = SusuCustomer::create($this->validated($request));

        return redirect()->route('susu.customers.show', $customer)->with('success', 'Customer '.$customer->code.' registered.');
    }

    public function show(SusuCustomer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->load('collector');

        return view('susu.customers.show', [
            'customer' => $customer,
            'ledger' => $customer->ledger(),
            'balance' => $customer->balance(),
            'stats' => [
                'expected' => (float) $customer->collections()->sum('expected'),
                'collected' => (float) $customer->collections()->sum('collected'),
                'withdrawn' => (float) $customer->withdrawals()->sum('amount'),
                'payments' => $customer->collections()->count(),
            ],
        ]);
    }

    public function edit(SusuCustomer $customer)
    {
        $this->authorizeCustomer($customer);

        return view('susu.customers.form', [
            'customer' => $customer,
            'collectors' => Collector::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SusuCustomer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->update($this->validated($request, $customer));

        return redirect()->route('susu.customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(SusuCustomer $customer)
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);
        $customer->delete();

        return redirect()->route('susu.customers.index')->with('success', 'Customer deleted.');
    }

    protected function validated(Request $request, ?SusuCustomer $customer = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('susu_customers')->ignore($customer)],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'ghana_card' => ['nullable', 'string', 'max:30'],
            'collector_id' => ['nullable', 'exists:collectors,id'],
            'plan' => ['required', 'in:Daily,Weekly,Monthly'],
            'contribution' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:Daily,Weekly,Monthly'],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Active,Dormant,Closed'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['opening_balance'] ??= 0;

        // Collectors register customers into their own book.
        if (($own = $this->ownCollectorId()) !== null) {
            $data['collector_id'] = $own ?: null;
        }

        return $data;
    }

    protected function authorizeCustomer(SusuCustomer $customer): void
    {
        $own = $this->ownCollectorId();
        abort_if($own !== null && $customer->collector_id !== $own, 403);
    }
}
