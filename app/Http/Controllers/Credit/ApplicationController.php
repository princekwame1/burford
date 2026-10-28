<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = LoanApplication::with('borrower', 'branch', 'officer')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('application_no', 'like', "%$s%")
                ->orWhereHas('borrower', fn ($b) => $b->where('full_name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('application_date')->latest('id')->paginate(20)->withQueryString();

        $counts = LoanApplication::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('credit.applications.index', compact('applications', 'counts'));
    }

    public function create(Request $request)
    {
        $borrower = $request->borrower ? Borrower::findOrFail($request->borrower) : new Borrower([
            'code' => BorrowerController::nextCode(), 'nationality' => 'Ghanaian',
        ]);

        return view('credit.applications.form', [
            'application' => new LoanApplication([
                'application_no' => self::nextNumber(),
                'application_date' => today(),
                'repayment_frequency' => 'Weekly',
                'officer_id' => auth()->id(),
                'branch_id' => $borrower->branch_id,
            ]),
            'borrower' => $borrower,
            ...$this->lookups(),
        ]);
    }

    public function store(Request $request)
    {
        $application = DB::transaction(function () use ($request) {
            $existing = $request->borrower_id ? Borrower::findOrFail($request->borrower_id) : null;
            $borrowerData = BorrowerController::validateBorrower($request, $existing);
            $borrower = $existing ? tap($existing)->update($borrowerData) : Borrower::create($borrowerData);

            return LoanApplication::create($this->validated($request) + ['borrower_id' => $borrower->id]);
        });

        return redirect()->route('credit.applications.show', $application)->with('success', 'Application '.$application->application_no.' submitted.');
    }

    public function show(LoanApplication $application)
    {
        $application->load('borrower.loans', 'branch', 'officer', 'approver', 'loan');

        return view('credit.applications.show', [
            'application' => $application,
            'defaultRate' => (float) Setting::get('default_interest_rate', 5),
        ]);
    }

    public function print(LoanApplication $application)
    {
        $application->load('borrower', 'branch', 'officer', 'approver');

        return view('credit.applications.print', compact('application'));
    }

    public function edit(LoanApplication $application)
    {
        abort_if($application->status === 'Disbursed', 422, 'A disbursed application cannot be edited.');

        return view('credit.applications.form', [
            'application' => $application,
            'borrower' => $application->borrower,
            ...$this->lookups($application),
        ]);
    }

    public function update(Request $request, LoanApplication $application)
    {
        abort_if($application->status === 'Disbursed', 422);
        DB::transaction(function () use ($request, $application) {
            $application->borrower->update(BorrowerController::validateBorrower($request, $application->borrower));
            $application->update($this->validated($request, $application));
        });

        return redirect()->route('credit.applications.show', $application)->with('success', 'Application updated.');
    }

    public function destroy(LoanApplication $application)
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);
        abort_if($application->status === 'Disbursed', 422, 'A disbursed application cannot be deleted.');
        $application->delete();

        return redirect()->route('credit.applications.index')->with('success', 'Application deleted.');
    }

    /** "For official use only" box: approve with terms, or decline. */
    public function decide(Request $request, LoanApplication $application, SmsNotifier $sms)
    {
        abort_if($application->status === 'Disbursed', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:Approved,Declined,Pending'],
            'approved_amount' => ['required_if:decision,Approved', 'nullable', 'numeric', 'min:1'],
            'interest_rate' => ['required_if:decision,Approved', 'nullable', 'numeric', 'min:0', 'max:100'],
            'approved_term' => ['required_if:decision,Approved', 'nullable', 'integer', 'min:1', 'max:520'],
            'approved_frequency' => ['required_if:decision,Approved', 'nullable', 'in:'.implode(',', Loan::FREQUENCIES)],
            'decision_notes' => ['nullable', 'string'],
        ]);

        $application->update([
            'status' => $data['decision'],
            'approved_amount' => $data['decision'] === 'Approved' ? $data['approved_amount'] : null,
            'interest_rate' => $data['decision'] === 'Approved' ? $data['interest_rate'] : null,
            'approved_term' => $data['decision'] === 'Approved' ? $data['approved_term'] : null,
            'approved_frequency' => $data['decision'] === 'Approved' ? $data['approved_frequency'] : null,
            'decision_notes' => $data['decision_notes'] ?? null,
            'approved_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        if ($data['decision'] !== 'Pending' && $request->boolean('send_sms', true)) {
            $sms->applicationDecided($application);
        }

        return back()->with('success', 'Application marked '.$data['decision'].'.');
    }

    public function disburse(Request $request, LoanApplication $application, SmsNotifier $sms)
    {
        abort_unless($application->status === 'Approved', 422, 'Only approved applications can be disbursed.');
        $data = $request->validate([
            'disbursement_date' => ['required', 'date'],
            'disbursement_method' => ['required', 'in:Cash,Mobile Money,Bank'],
            'notes' => ['nullable', 'string'],
        ]);

        $loan = DB::transaction(function () use ($application, $data) {
            $loan = Loan::create([
                'loan_no' => LoanController::nextNumber(),
                'borrower_id' => $application->borrower_id,
                'loan_application_id' => $application->id,
                'branch_id' => $application->branch_id,
                'officer_id' => $application->officer_id,
                'disbursement_date' => $data['disbursement_date'],
                'principal' => $application->approved_amount,
                'interest_rate' => $application->interest_rate,
                'term' => $application->approved_term,
                'frequency' => $application->approved_frequency,
                'disbursement_method' => $data['disbursement_method'],
                'notes' => $data['notes'] ?? null,
            ]);
            $application->update(['status' => 'Disbursed']);
            $loan->refreshStatus();

            return $loan;
        });

        if ($request->boolean('send_sms', true)) {
            $sms->loanDisbursed($loan);
        }

        return redirect()->route('credit.loans.show', $loan)->with('success', "Loan {$loan->loan_no} disbursed.");
    }

    public static function nextNumber(): string
    {
        return Sequence::next(LoanApplication::class, 'application_no', 'APP-'.now()->format('y'), 4);
    }

    protected function lookups(?LoanApplication $application = null): array
    {
        return [
            'branches' => Branch::orderBy('name')->get(),
            'officers' => User::officers($application?->officer_id)->where('is_active', true)->get(),
            'borrowers' => Borrower::orderBy('full_name')->get(['id', 'code', 'full_name', 'phone']),
        ];
    }

    protected function validated(Request $request, ?LoanApplication $application = null): array
    {
        $data = $request->validate([
            'application_no' => ['required', 'string', 'max:30', 'unique:loan_applications,application_no'.($application ? ','.$application->id : '')],
            'application_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'officer_id' => ['nullable', 'exists:users,id'],
            'amount_requested' => ['required', 'numeric', 'min:1'],
            'purpose' => ['nullable', 'in:'.implode(',', LoanApplication::PURPOSES)],
            'purpose_other' => ['nullable', 'string', 'max:150'],
            'preferred_term' => ['nullable', 'integer', 'min:1', 'max:520'],
            'repayment_frequency' => ['required', 'in:'.implode(',', Loan::FREQUENCIES)],
            'preferred_disbursement_date' => ['nullable', 'date'],
            'proposed_repayment_start' => ['nullable', 'date'],
            'family_members' => ['nullable', 'array'],
            'family_members.*.name' => ['nullable', 'string', 'max:120'],
            'family_members.*.age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'family_members.*.relationship' => ['nullable', 'string', 'max:60'],
            'family_members.*.occupation' => ['nullable', 'string', 'max:120'],
            'existing_obligations' => ['nullable', 'array'],
            'existing_obligations.*.lender' => ['nullable', 'string', 'max:120'],
            'existing_obligations.*.balance' => ['nullable', 'numeric', 'min:0'],
            'existing_obligations.*.monthly_repayment' => ['nullable', 'numeric', 'min:0'],
            'guarantor_name' => ['nullable', 'string', 'max:150'],
            'guarantor_phone' => ['nullable', 'string', 'max:20'],
            'guarantor_relationship' => ['nullable', 'string', 'max:60'],
            'guarantor_address' => ['nullable', 'string', 'max:255'],
            'declaration_accepted' => ['accepted'],
        ], ['declaration_accepted.accepted' => 'The applicant must accept the declaration.']);

        // Drop empty repeater rows.
        $data['family_members'] = array_values(array_filter($data['family_members'] ?? [], fn ($r) => filled($r['name'] ?? null)));
        $data['existing_obligations'] = array_values(array_filter($data['existing_obligations'] ?? [], fn ($r) => filled($r['lender'] ?? null)));
        $data['declaration_accepted'] = true;

        return $data;
    }
}
