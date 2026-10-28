{{-- Sections A (Personal information) and B (Employment / business) of the MK credit application form. --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-brand-600 text-xs font-bold text-white">A</span> Personal information</h3>
    </div>
    <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="flex items-center gap-4 sm:col-span-2 lg:col-span-3" x-data="{ preview: @js($borrower->photo_path ? asset('storage/'.$borrower->photo_path) : null) }">
            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-dashed border-ink-200 bg-ink-50 dark:border-ink-700 dark:bg-ink-800">
                <template x-if="preview"><img :src="preview" class="size-full object-cover" alt="Passport photo"></template>
                <template x-if="!preview"><x-icon name="user" class="size-8 text-ink-300" /></template>
            </div>
            <div>
                <label class="label">Passport photo</label>
                <input type="file" name="photo" accept="image/*" @change="preview = URL.createObjectURL($event.target.files[0])"
                    class="text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white dark:text-ink-300">
                @error('photo')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <x-field label="Borrower ID" name="code" :value="$borrower->code" required />
        <x-select label="Title" name="title" :value="$borrower->title" :options="['Mr.', 'Ms.', 'Mrs.', 'Dr.']" placeholder="—" />
        <x-field label="Full name" name="full_name" :value="$borrower->full_name" required />
        <x-field label="Father's name" name="father_name" :value="$borrower->father_name" />
        <x-field label="Mother's name" name="mother_name" :value="$borrower->mother_name" />
        <x-field label="Date of birth" name="date_of_birth" type="date" :value="$borrower->date_of_birth" />
        <x-select label="Gender" name="gender" :value="$borrower->gender" :options="['Male', 'Female']" placeholder="—" />
        <x-select label="Marital status" name="marital_status" :value="$borrower->marital_status" :options="['Single', 'Married', 'Divorced', 'Widowed']" placeholder="—" />
        <x-field label="Nationality" name="nationality" :value="$borrower->nationality" />
        <x-field label="Ghana Card No." name="ghana_card" :value="$borrower->ghana_card" placeholder="GHA-000000000-0" />
        <x-field label="Mobile no. (for SMS)" name="phone" :value="$borrower->phone" required />
        <x-field label="Email address" name="email" type="email" :value="$borrower->email" />
        <x-field label="Residential address" name="residential_address" :value="$borrower->residential_address" class="sm:col-span-2" />
        <x-field label="Digital address" name="digital_address" :value="$borrower->digital_address" placeholder="GA-000-0000" />
        <x-field label="City / town" name="city" :value="$borrower->city" />
        <x-field label="District" name="district" :value="$borrower->district" />
        <x-select label="Branch" name="branch_id" :value="$borrower->branch_id" :options="$branches->pluck('name', 'id')" placeholder="—" />
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title flex items-center gap-2"><span class="grid size-6 place-items-center rounded-md bg-brand-600 text-xs font-bold text-white">B</span> Employment / business information</h3>
    </div>
    <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-field label="Occupation / business" name="occupation" :value="$borrower->occupation" />
        <x-field label="Employer / business name" name="employer_name" :value="$borrower->employer_name" />
        <x-select label="Nature" name="business_nature" :value="$borrower->business_nature" :options="['Trading', 'Agriculture', 'Service', 'Manufacturing', 'Other']" placeholder="—" />
        <x-field label="Business registration no." name="business_registration_no" :value="$borrower->business_registration_no" help="If applicable" />
        <x-field label="Years in operation" name="years_in_operation" type="number" step="1" :value="$borrower->years_in_operation" />
        <x-field label="Monthly income" name="monthly_income" type="number" :value="$borrower->monthly_income" prefix="GH₵" />
        <x-field label="Business location" name="business_location" :value="$borrower->business_location" />
        <x-field label="Business address" name="business_address" :value="$borrower->business_address" />
        <x-field label="Contact person" name="business_contact_person" :value="$borrower->business_contact_person" help="If business" />
        <x-field label="Telephone" name="business_phone" :value="$borrower->business_phone" />
    </div>
</div>
