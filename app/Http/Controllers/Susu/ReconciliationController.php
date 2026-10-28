<?php

namespace App\Http\Controllers\Susu;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\Reconciliation;
use App\Models\SusuCollection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reconciliation::with('collector')
            ->when($request->collector, fn ($q, $c) => $q->where('collector_id', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d));

        return view('susu.reconciliations.index', [
            'reconciliations' => (clone $query)->latest('date')->paginate(25)->withQueryString(),
            'collectors' => Collector::orderBy('name')->get(),
            'totals' => (clone $query)->selectRaw('SUM(system_collections) s, SUM(total_counted) t, SUM(variance) v, SUM(deposit_amount) d')->first(),
        ]);
    }

    public function create(Request $request)
    {
        $collectors = Collector::where('status', 'Active')->orderBy('name')->get();
        $date = $request->date('date') ?? today();
        // Pre-compute system totals per collector so the form can show them live.
        $system = SusuCollection::whereDate('date', $date)->selectRaw('collector_id, SUM(collected) total, SUM(CASE WHEN payment_method = ? THEN collected ELSE 0 END) momo', ['Mobile Money'])
            ->groupBy('collector_id')->get()->keyBy('collector_id');

        return view('susu.reconciliations.form', [
            'reconciliation' => new Reconciliation(['date' => $date, 'status' => 'Open', 'collector_id' => $request->collector]),
            'collectors' => $collectors,
            'system' => $system,
        ]);
    }

    public function store(Request $request)
    {
        Reconciliation::create($this->validated($request) + ['reconciled_by' => auth()->id()]);

        return redirect()->route('susu.reconciliations.index')->with('success', 'Reconciliation saved.');
    }

    public function edit(Reconciliation $reconciliation)
    {
        $system = SusuCollection::whereDate('date', $reconciliation->date)->selectRaw('collector_id, SUM(collected) total, SUM(CASE WHEN payment_method = ? THEN collected ELSE 0 END) momo', ['Mobile Money'])
            ->groupBy('collector_id')->get()->keyBy('collector_id');

        return view('susu.reconciliations.form', [
            'reconciliation' => $reconciliation,
            'collectors' => Collector::orderBy('name')->get(),
            'system' => $system,
        ]);
    }

    public function update(Request $request, Reconciliation $reconciliation)
    {
        $reconciliation->update($this->validated($request, $reconciliation) + ['reconciled_by' => auth()->id()]);

        return redirect()->route('susu.reconciliations.index')->with('success', 'Reconciliation updated.');
    }

    public function destroy(Reconciliation $reconciliation)
    {
        $reconciliation->delete();

        return back()->with('success', 'Reconciliation deleted.');
    }

    protected function validated(Request $request, ?Reconciliation $rec = null): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'collector_id' => ['required', 'exists:collectors,id',
                Rule::unique('reconciliations')->where('date', $request->date('date')?->toDateString())->ignore($rec)],
            'cash_counted' => ['required', 'numeric', 'min:0'],
            'mobile_money' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_ref' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'in:Open,Reconciled,Escalated'],
            'notes' => ['nullable', 'string'],
        ], ['collector_id.unique' => 'This collector already has a reconciliation for that date.']);
    }
}
