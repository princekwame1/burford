<?php

namespace App\Http\Controllers\Susu;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Collector;
use App\Models\User;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectorController extends Controller
{
    public function index()
    {
        $collectors = Collector::with('user', 'branch')
            ->withCount('customers')
            ->withSum(['collections as month_collected' => fn ($q) => $q->whereDate('date', '>=', today()->startOfMonth())], 'collected')
            ->withSum(['collections as today_collected' => fn ($q) => $q->whereDate('date', today())], 'collected')
            ->orderBy('code')->get();

        return view('susu.collectors.index', compact('collectors'));
    }

    public function create()
    {
        return view('susu.collectors.form', $this->formData(new Collector([
            'code' => Sequence::next(Collector::class, 'code', 'COL'), 'status' => 'Active', 'start_date' => today(),
        ])));
    }

    public function store(Request $request)
    {
        $collector = Collector::create($this->validated($request));

        return redirect()->route('susu.collectors.show', $collector)->with('success', 'Collector added.');
    }

    public function show(Collector $collector)
    {
        $collector->load('customers', 'user', 'branch');
        $recent = $collector->collections()->with('customer')->latest('date')->limit(15)->get();
        $reconciliations = $collector->reconciliations()->latest('date')->limit(10)->get();

        return view('susu.collectors.show', compact('collector', 'recent', 'reconciliations'));
    }

    public function edit(Collector $collector)
    {
        return view('susu.collectors.form', $this->formData($collector));
    }

    public function update(Request $request, Collector $collector)
    {
        $collector->update($this->validated($request, $collector));

        return redirect()->route('susu.collectors.show', $collector)->with('success', 'Collector updated.');
    }

    public function destroy(Collector $collector)
    {
        $collector->delete();

        return redirect()->route('susu.collectors.index')->with('success', 'Collector removed.');
    }

    protected function formData(Collector $collector): array
    {
        return [
            'collector' => $collector,
            'branches' => Branch::orderBy('name')->get(),
            'users' => User::where('role', 'collector')->orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request, ?Collector $collector = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('collectors')->ignore($collector)],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'territory' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('collectors')->ignore($collector)],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', 'in:Active,Inactive,Suspended'],
            'daily_target' => ['nullable', 'numeric', 'min:0'],
            'cash_limit' => ['nullable', 'numeric', 'min:0'],
            'supervisor' => ['nullable', 'string', 'max:120'],
        ]);
        $data['daily_target'] ??= 0;
        $data['cash_limit'] ??= 0;

        return $data;
    }
}
