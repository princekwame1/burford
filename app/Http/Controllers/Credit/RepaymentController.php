<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Services\SmsNotifier;
use App\Support\Money;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = LoanRepayment::with('loan.borrower', 'receiver')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('receipt_no', 'like', "%$s%")
                ->orWhereHas('loan', fn ($l) => $l->where('loan_no', 'like', "%$s%")
                    ->orWhereHas('borrower', fn ($b) => $b->where('full_name', 'like', "%$s%")))))
            ->when($request->method, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($request->from, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('date', '<=', $d));

        return view('credit.repayments.index', [
            'repayments' => (clone $query)->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'total' => (float) (clone $query)->sum('amount'),
        ]);
    }

    public function store(Request $request, Loan $loan, SmsNotifier $sms)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:Cash,Mobile Money,Bank'],
            'reference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((float) $data['amount'] > $loan->outstanding() + 0.001) {
            throw ValidationException::withMessages(['amount' => 'Amount exceeds the outstanding balance of '.Money::format($loan->outstanding()).'.']);
        }

        $repayment = DB::transaction(function () use ($loan, $data) {
            $repayment = $loan->repayments()->create($data + [
                'receipt_no' => Sequence::next(LoanRepayment::class, 'receipt_no', 'LRP-', 5, 10001),
                'period_no' => $loan->nextInstallment()['period'] ?? null,
                'received_by' => auth()->id(),
            ]);
            $loan->recalculate();

            return $repayment;
        });

        if ($request->boolean('send_sms', true)) {
            $sms->repaymentReceived($repayment->setRelation('loan', $loan->fresh('borrower')));
        }

        return back()->with('success', 'Repayment of '.Money::format($repayment->amount).' recorded ('.$repayment->receipt_no.').');
    }

    public function destroy(LoanRepayment $repayment)
    {
        $loan = $repayment->loan;
        DB::transaction(function () use ($repayment, $loan) {
            $repayment->delete();
            $loan->recalculate();
        });

        return back()->with('success', 'Repayment reversed.');
    }
}
