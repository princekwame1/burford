<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\Branch;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Reconciliation;
use App\Models\Setting;
use App\Models\SusuCollection;
use App\Models\SusuCustomer;
use App\Models\SusuWithdrawal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put([
            'company_name' => 'BURFORD Collection',
            'company_tagline' => 'Small credit • Big dreams • Brighter futures',
            'company_phone' => '',
            'company_address' => 'Barrier, Kasoa — Ghana',
            'default_interest_rate' => '5',
            'loan_prefix' => 'BCL-',
            'reminder_days_before' => '1',
            'sms_enabled' => '1',
            'texttango_base_url' => config('services.texttango.base_url'),
            'texttango_sender_id' => config('services.texttango.sender_id'),
        ]);

        $admin = User::create(['name' => 'System Administrator', 'email' => 'admin@burford.test', 'password' => 'password', 'role' => 'admin']);
        $manager = User::create(['name' => 'Operations Manager', 'email' => 'manager@burford.test', 'password' => 'password', 'role' => 'manager']);
        $officer = User::create(['name' => 'MK', 'email' => 'officer@burford.test', 'password' => 'password', 'role' => 'loan_officer']);
        $u1 = User::create(['name' => 'Collector 1', 'email' => 'collector1@burford.test', 'password' => 'password', 'role' => 'collector', 'phone' => '0200000001']);
        $u2 = User::create(['name' => 'Collector 2', 'email' => 'collector2@burford.test', 'password' => 'password', 'role' => 'collector', 'phone' => '0200000002']);

        $kasoa = Branch::create(['name' => 'Barrier-Kasoa', 'location' => 'Kasoa, Central Region']);
        $accra = Branch::create(['name' => 'Accra Central', 'location' => 'Accra']);

        // ---- Collector Register (MK DUA PA SUSU ENTERPRISE.xlsx) ----
        $c1 = Collector::create(['code' => 'COL001', 'user_id' => $u1->id, 'branch_id' => $accra->id, 'name' => 'Collector 1', 'phone' => '0200000001',
            'territory' => 'Accra Central', 'start_date' => '2026-01-01', 'status' => 'Active', 'daily_target' => 1500, 'cash_limit' => 3000, 'supervisor' => 'Manager']);
        $c2 = Collector::create(['code' => 'COL002', 'user_id' => $u2->id, 'branch_id' => $accra->id, 'name' => 'Collector 2', 'phone' => '0200000002',
            'territory' => 'Tema/Madina', 'start_date' => '2026-01-01', 'status' => 'Active', 'daily_target' => 1500, 'cash_limit' => 3000, 'supervisor' => 'Manager']);

        // ---- Customer Master ----
        $customers = [];
        foreach ([
            ['CUS001', 'Ama Mensah', '0240000001', 'Accra', $c1, 'Daily', 50],
            ['CUS002', 'Kofi Asare', '0240000002', 'Accra', $c1, 'Daily', 30],
            ['CUS003', 'Akosua Boateng', '0240000003', 'Tema', $c2, 'Weekly', 100],
            ['CUS004', 'Yaw Owusu', '0240000004', 'Madina', $c2, 'Daily', 50],
            ['CUS005', 'Abena Ofori', '0240000005', 'Kasoa', $c1, 'Daily', 20],
            ['CUS006', 'Kwame Addo', '0240000006', 'Accra', $c2, 'Weekly', 150],
        ] as [$code, $name, $phone, $addr, $col, $plan, $amt]) {
            $customers[$code] = SusuCustomer::create(['code' => $code, 'name' => $name, 'phone' => $phone, 'address' => $addr,
                'collector_id' => $col->id, 'plan' => $plan, 'contribution' => $amt, 'frequency' => $plan,
                'start_date' => '2026-09-01', 'status' => 'Active', 'opening_balance' => 0]);
        }

        // ---- Daily Collections: [day offset from 1 Sep 2026, customer, expected, collected, method, deposit ref] ----
        $start = Carbon::parse('2026-09-01');
        $rows = [
            [0, 'CUS002', 30, 30, 'M', 'DEP-200'], [0, 'CUS004', 50, 50, 'C', 'DEP-200'], [1, 'CUS001', 50, 50, 'M', 'DEP-200'],
            [1, 'CUS001', 50, 50, 'M', 'DEP-200'], [2, 'CUS002', 30, 30, 'M', 'DEP-201'], [3, 'CUS002', 30, 30, 'C', 'DEP-201'],
            [3, 'CUS006', 150, 150, 'M', 'DEP-201'], [4, 'CUS002', 30, 30, 'M', 'DEP-201'], [6, 'CUS001', 50, 50, 'M', 'DEP-201'],
            [7, 'CUS004', 50, 50, 'C', 'DEP-202'], [7, 'CUS002', 30, 30, 'C', 'DEP-202'], [8, 'CUS005', 20, 20, 'C', 'DEP-202'],
            [9, 'CUS005', 20, 20, 'M', 'DEP-202'], [9, 'CUS005', 20, 20, 'M', 'DEP-202'], [10, 'CUS005', 20, 20, 'M', 'DEP-203'],
            [10, 'CUS002', 30, 30, 'M', 'DEP-203'], [11, 'CUS001', 50, 25, 'M', 'DEP-203'], [11, 'CUS005', 20, 20, 'M', 'DEP-203'],
            [13, 'CUS002', 30, 30, 'C', 'DEP-203'], [13, 'CUS001', 50, 50, 'C', 'DEP-204'], [15, 'CUS004', 50, 50, 'M', 'DEP-204'],
            [15, 'CUS004', 50, 50, 'M', 'DEP-204'], [16, 'CUS005', 20, 20, 'M', 'DEP-204'], [17, 'CUS001', 50, 50, 'C', 'DEP-204'],
            [17, 'CUS003', 100, 100, 'C', 'DEP-205'], [18, 'CUS001', 50, 50, 'C', 'DEP-205'], [19, 'CUS004', 50, 25, 'C', 'DEP-205'],
            [21, 'CUS001', 50, 50, 'M', 'DEP-205'], [22, 'CUS002', 30, 30, 'M', 'DEP-205'], [22, 'CUS005', 20, 20, 'C', 'DEP-206'],
            [23, 'CUS005', 20, 20, 'M', 'DEP-206'], [24, 'CUS003', 100, 100, 'M', 'DEP-206'], [24, 'CUS005', 20, 20, 'M', 'DEP-206'],
        ];
        foreach ($rows as $i => [$offset, $code, $expected, $collected, $method, $dep]) {
            $customer = $customers[$code];
            SusuCollection::create([
                'receipt_no' => 'RCP-'.(1001 + $i), 'date' => $start->copy()->addDays($offset), 'susu_customer_id' => $customer->id,
                'collector_id' => $customer->collector_id, 'expected' => $expected, 'collected' => $collected,
                'payment_method' => $method === 'M' ? 'Mobile Money' : 'Cash', 'verified_by' => 'Supervisor', 'deposit_ref' => $dep,
                'recorded_by' => $manager->id,
            ]);
        }

        // ---- Withdrawals ----
        foreach ([['WDR-2001', 20, 'CUS001', 300], ['WDR-2002', 21, 'CUS002', 100], ['WDR-2003', 22, 'CUS003', 200], ['WDR-2004', 23, 'CUS004', 100]]
            as [$no, $offset, $code, $amount]) {
            $customer = $customers[$code];
            SusuWithdrawal::create(['withdrawal_no' => $no, 'date' => $start->copy()->addDays($offset), 'susu_customer_id' => $customer->id,
                'amount' => $amount, 'type' => 'Normal', 'approved_by' => 'Manager', 'payment_method' => 'Cash',
                'balance_after' => $customer->balance() - $amount, 'recorded_by' => $manager->id]);
        }

        // ---- Daily Reconciliation: counted from the receipts; the last two days left open with a small variance ----
        $days = SusuCollection::selectRaw('date, collector_id')->groupBy('date', 'collector_id')->orderBy('date')->get();
        foreach ($days as $i => $d) {
            $q = SusuCollection::whereDate('date', $d->date)->where('collector_id', $d->collector_id);
            $cash = (float) (clone $q)->where('payment_method', 'Cash')->sum('collected');
            $momo = (float) (clone $q)->where('payment_method', 'Mobile Money')->sum('collected');
            $open = $i >= $days->count() - 2;
            Reconciliation::create(['date' => $d->date, 'collector_id' => $d->collector_id, 'cash_counted' => $open ? max($cash - 10, 0) : $cash,
                'mobile_money' => $momo, 'deposit_ref' => 'DEP-'.(3000 + $i), 'status' => $open ? 'Open' : 'Reconciled', 'reconciled_by' => $manager->id]);
        }

        // ---- Micro Credit: Loan Data (MK Micro Credit.xlsx) ----
        $edua = Borrower::create(['code' => 'BOR0001', 'title' => 'Ms.', 'full_name' => 'Edua Kojo', 'phone' => '0240000101', 'gender' => 'Female',
            'nationality' => 'Ghanaian', 'city' => 'Kasoa', 'occupation' => 'Trader', 'business_nature' => 'Trading', 'branch_id' => $kasoa->id]);
        $grace = Borrower::create(['code' => 'BOR0002', 'title' => 'Mrs.', 'full_name' => 'Grace Botwe', 'phone' => '0240000102', 'gender' => 'Female',
            'nationality' => 'Ghanaian', 'city' => 'Kasoa', 'occupation' => 'Trader', 'business_nature' => 'Trading', 'branch_id' => $kasoa->id]);

        $loan1 = Loan::create(['loan_no' => 'BCL-0001', 'borrower_id' => $edua->id, 'branch_id' => $kasoa->id, 'officer_id' => $officer->id,
            'disbursement_date' => '2026-09-17', 'principal' => 4000, 'interest_rate' => 5.14, 'term' => 9, 'frequency' => 'Weekly']);
        $loan1->repayments()->create(['receipt_no' => 'LRP-10001', 'date' => '2026-09-24', 'period_no' => 1, 'amount' => 650,
            'payment_method' => 'Cash', 'received_by' => $officer->id]);
        $loan1->recalculate();

        $loan2 = Loan::create(['loan_no' => 'BCL-0002', 'borrower_id' => $grace->id, 'branch_id' => $kasoa->id, 'officer_id' => $officer->id,
            'disbursement_date' => '2026-09-22', 'principal' => 10000, 'interest_rate' => 2, 'term' => 12, 'frequency' => 'Weekly']);
        $loan2->refreshStatus();

        // A pending application to demonstrate the approval workflow.
        $kwesi = Borrower::create(['code' => 'BOR0003', 'title' => 'Mr.', 'full_name' => 'Kwesi Appiah', 'phone' => '0240000103', 'gender' => 'Male',
            'marital_status' => 'Married', 'nationality' => 'Ghanaian', 'city' => 'Kasoa', 'district' => 'Awutu Senya East',
            'occupation' => 'Provision shop owner', 'business_nature' => 'Trading', 'years_in_operation' => 4, 'monthly_income' => 3500,
            'business_location' => 'Kasoa Market', 'branch_id' => $kasoa->id]);
        LoanApplication::create(['application_no' => 'APP-260001', 'application_date' => today(), 'borrower_id' => $kwesi->id,
            'branch_id' => $kasoa->id, 'officer_id' => $officer->id, 'amount_requested' => 3000, 'purpose' => 'Stock Purchase',
            'preferred_term' => 10, 'repayment_frequency' => 'Weekly', 'guarantor_name' => 'Ama Appiah', 'guarantor_phone' => '0240000104',
            'guarantor_relationship' => 'Spouse', 'declaration_accepted' => true,
            'family_members' => [['name' => 'Ama Appiah', 'age' => 34, 'relationship' => 'Spouse', 'occupation' => 'Seamstress']],
            'existing_obligations' => []]);

        $this->command?->info('Seeded. Log in as admin@burford.test / password');
    }
}
