<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Branch;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BorrowerController extends Controller
{
    public function index(Request $request)
    {
        $borrowers = Borrower::with('branch')
            ->withCount('loans')
            ->withSum('loans as total_borrowed', 'principal')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('full_name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('ghana_card', 'like', "%$s%")))
            ->when($request->branch, fn ($q, $b) => $q->where('branch_id', $b))
            ->orderBy('full_name')->paginate(20)->withQueryString();

        return view('credit.borrowers.index', ['borrowers' => $borrowers, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('credit.borrowers.form', [
            'borrower' => new Borrower(['code' => self::nextCode(), 'nationality' => 'Ghanaian']),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $borrower = Borrower::create(self::validateBorrower($request));

        return redirect()->route('credit.borrowers.show', $borrower)->with('success', 'Borrower registered.');
    }

    public function show(Borrower $borrower)
    {
        $borrower->load(['branch', 'applications' => fn ($q) => $q->latest(), 'loans' => fn ($q) => $q->latest('disbursement_date')]);

        return view('credit.borrowers.show', compact('borrower'));
    }

    public function edit(Borrower $borrower)
    {
        return view('credit.borrowers.form', ['borrower' => $borrower, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, Borrower $borrower)
    {
        $borrower->update(self::validateBorrower($request, $borrower));

        return redirect()->route('credit.borrowers.show', $borrower)->with('success', 'Borrower updated.');
    }

    public function destroy(Borrower $borrower)
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);
        abort_if($borrower->loans()->open()->exists(), 422, 'Borrower has open loans.');
        if ($borrower->photo_path) {
            Storage::disk('public')->delete($borrower->photo_path);
        }
        $borrower->delete();

        return redirect()->route('credit.borrowers.index')->with('success', 'Borrower deleted.');
    }

    public static function nextCode(): string
    {
        return Sequence::next(Borrower::class, 'code', 'BOR', 4);
    }

    /** Shared by the borrower form and the loan application form (section A & B of the paper form). */
    public static function validateBorrower(Request $request, ?Borrower $borrower = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:borrowers,code'.($borrower ? ','.$borrower->id : '')],
            'title' => ['nullable', 'in:Mr.,Ms.,Mrs.,Dr.'],
            'full_name' => ['required', 'string', 'max:150'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:Male,Female'],
            'marital_status' => ['nullable', 'in:Single,Married,Divorced,Widowed'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'ghana_card' => ['nullable', 'string', 'max:30'],
            'residential_address' => ['nullable', 'string', 'max:255'],
            'digital_address' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'employer_name' => ['nullable', 'string', 'max:150'],
            'business_nature' => ['nullable', 'string', 'max:60'],
            'business_registration_no' => ['nullable', 'string', 'max:60'],
            'years_in_operation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'business_location' => ['nullable', 'string', 'max:150'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_contact_person' => ['nullable', 'string', 'max:120'],
            'business_phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        if ($request->hasFile('photo')) {
            if ($borrower?->photo_path) {
                Storage::disk('public')->delete($borrower->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('borrowers', 'public');
        }
        unset($data['photo']);

        return $data;
    }
}
