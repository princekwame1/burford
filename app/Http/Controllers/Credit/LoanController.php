<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Services\TextTango;
use App\Support\Sequence;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = Loan::with('borrower', 'branch', 'officer')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('loan_no', 'like', "%$s%")
                ->orWhereHas('borrower', fn ($b) => $b->where('full_name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->branch, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($request->officer, fn ($q, $o) => $q->where('officer_id', $o))
            ->latest('disbursement_date')->latest('id')
            ->paginate(20)->withQueryString();

        return view('credit.loans.index', [
            'loans' => $loans,
            'branches' => Branch::orderBy('name')->get(),
            'officers' => User::officers()->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('credit.loans.form', [
            'loan' => new Loan([
                'loan_no' => self::nextNumber(),
                'borrower_id' => $request->borrower,
                'disbursement_date' => today(),
                'interest_rate' => Setting::get('default_interest_rate', 5),
                'term' => 12,
                'frequency' => 'Weekly',
                'officer_id' => auth()->id(),
                'disbursement_method' => 'Cash',
            ]),
            ...$this->lookups(),
        ]);
    }

    public function store(Request $request, SmsNotifier $sms)
    {
        $loan = Loan::create($this->validated($request));
        $loan->refreshStatus(); // a back-dated loan may already be in arrears
        if ($request->boolean('send_sms', true)) {
            $sms->loanDisbursed($loan);
        }

        return redirect()->route('credit.loans.show', $loan)->with('success', "Loan {$loan->loan_no} created.");
    }

    public function show(Loan $loan)
    {
        $loan->load('borrower', 'branch', 'officer', 'application', 'repayments.receiver');
        $loan->refreshStatus();

        return view('credit.loans.show', [
            'loan' => $loan,
            'schedule' => $loan->schedule(),
            'next' => $loan->nextInstallment(),
            'sms' => SmsMessage::where('to', TextTango::normalizePhone($loan->borrower->phone))->latest()->limit(8)->get(),
        ]);
    }

    public function statement(Loan $loan)
    {
        $loan->load('borrower', 'branch', 'officer', 'repayments');

        return view('credit.loans.statement', ['loan' => $loan, 'schedule' => $loan->schedule()]);
    }

    public function edit(Loan $loan)
    {
        return view('credit.loans.form', ['loan' => $loan, ...$this->lookups($loan)]);
    }

    public function update(Request $request, Loan $loan)
    {
        $data = $this->validated($request, $loan);
        // Terms are locked once money has come back; only status and notes can change.
        if ($loan->repayments()->exists()) {
            $data = array_intersect_key($data, array_flip(['status', 'notes', 'officer_id', 'branch_id']));
        }
        $loan->fill($data);
        if (($data['status'] ?? null) !== 'Defaulted') {
            $loan->refreshStatus(false);
        }
        $loan->save();

        return redirect()->route('credit.loans.show', $loan)->with('success', 'Loan updated.');
    }

    public function destroy(Loan $loan)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $loan->application?->update(['status' => 'Approved']);
        $loan->delete();

        return redirect()->route('credit.loans.index')->with('success', 'Loan deleted.');
    }

    public function remind(Loan $loan, SmsNotifier $sms)
    {
        $loan->load('borrower');
        $result = $loan->arrears() > 0.009
            ? $sms->overdueNotice($loan)
            : (($next = $loan->nextInstallment()) ? $sms->repaymentReminder($loan, $next) : null);

        return back()->with($result ? 'success' : 'error', $result ? 'Reminder queued to '.$loan->borrower->phone.'.' : 'Nothing to remind about, or this SMS type is switched off.');
    }

    public static function nextNumber(): string
    {
        return Sequence::next(Loan::class, 'loan_no', Setting::get('loan_prefix', 'BCL-'), 4);
    }

    protected function lookups(?Loan $loan = null): array
    {
        return [
            'borrowers' => Borrower::orderBy('full_name')->get(['id', 'code', 'full_name', 'phone']),
            'branches' => Branch::orderBy('name')->get(),
            'officers' => User::officers($loan?->officer_id)->get(),
        ];
    }

    protected function validated(Request $request, ?Loan $loan = null): array
    {
        $data = $request->validate([
            'loan_no' => ['required', 'string', 'max:30', 'unique:loans,loan_no'.($loan ? ','.$loan->id : '')],
            'borrower_id' => ['required', 'exists:borrowers,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'officer_id' => ['nullable', 'exists:users,id'],
            'disbursement_date' => ['required', 'date'],
            'principal' => ['required', 'numeric', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'term' => ['required', 'integer', 'min:1', 'max:520'],
            'frequency' => ['required', 'in:'.implode(',', Loan::FREQUENCIES)],
            'disbursement_method' => ['required', 'in:Cash,Mobile Money,Bank'],
            'status' => [$loan ? 'required' : 'nullable', 'in:'.implode(',', Loan::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);
        $data['status'] ??= 'Active';

        return $data;
    }
}
