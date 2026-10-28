<?php

namespace App\Http\Controllers\Susu;

use App\Http\Controllers\Controller;
use App\Models\SusuCustomer;
use App\Models\SusuWithdrawal;
use App\Services\SmsNotifier;
use App\Support\Money;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $own = $this->ownCollectorId();
        $query = SusuWithdrawal::with('customer')
            ->when($own !== null, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('collector_id', $own)))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('withdrawal_no', 'like', "%$s%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"))))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d));

        return view('susu.withdrawals.index', [
            'withdrawals' => (clone $query)->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'total' => (float) (clone $query)->sum('amount'),
            'commission' => (float) (clone $query)->sum('commission'),
        ]);
    }

    public function create(Request $request)
    {
        $own = $this->ownCollectorId();
        $customers = SusuCustomer::whereIn('status', ['Active', 'Dormant'])
            ->when($own !== null, fn ($q) => $q->where('collector_id', $own))
            ->orderBy('name')->get()
            ->map(fn ($c) => ['id' => $c->id, 'label' => "{$c->code} — {$c->name}", 'balance' => round($c->balance(), 2)]);

        return view('susu.withdrawals.form', [
            'withdrawalNo' => Sequence::next(SusuWithdrawal::class, 'withdrawal_no', 'WDR-', 4, 2001),
            'customers' => $customers,
            'selected' => $request->customer,
        ]);
    }

    public function store(Request $request, SmsNotifier $sms)
    {
        $data = $request->validate([
            'withdrawal_no' => ['required', 'string', 'max:30', 'unique:susu_withdrawals,withdrawal_no'],
            'date' => ['required', 'date'],
            'susu_customer_id' => ['required', 'exists:susu_customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'commission' => ['nullable', 'numeric', 'min:0'],
            'type' => ['required', 'in:Normal,Emergency,Closure'],
            'approved_by' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['required', 'in:Cash,Mobile Money,Bank'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = SusuCustomer::findOrFail($data['susu_customer_id']);
        if (($own = $this->ownCollectorId()) !== null) {
            abort_unless($customer->collector_id === $own, 403);
        }
        $data['commission'] ??= 0;
        $balance = $customer->balance();
        $debit = (float) $data['amount'] + (float) $data['commission'];

        if ($debit > $balance + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient balance. Available: '.Money::format($balance).'.',
            ]);
        }

        $data['balance_after'] = $balance - $debit;
        $data['approved_by'] ??= auth()->user()->name;
        $withdrawal = SusuWithdrawal::create($data + ['recorded_by' => auth()->id()]);

        if ($data['type'] === 'Closure') {
            $customer->update(['status' => 'Closed']);
        }
        if ($request->boolean('send_sms', true)) {
            $sms->withdrawalPaid($withdrawal);
        }

        return redirect()->route('susu.withdrawals.index')->with('success', "Withdrawal {$withdrawal->withdrawal_no} recorded.");
    }

    public function destroy(SusuWithdrawal $withdrawal)
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);
        $withdrawal->delete();

        return back()->with('success', 'Withdrawal deleted.');
    }
}
