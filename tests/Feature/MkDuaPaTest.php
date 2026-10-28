<?php

namespace Tests\Feature;

use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\SusuCustomer;
use App\Models\User;
use App\Services\TextTango;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MkDuaPaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Http::fake([
            'app.texttango.com/api/v2/sms/transactional/send' => Http::response(['data' => ['uuid' => 'tt-uuid-1', 'status' => 'queued']], 201),
            'app.texttango.com/api/v2/wallet' => Http::response(['data' => ['attributes' => ['main_balance' => 42.5, 'bonus_balance' => 1, 'currency' => 'GHS']]]),
            'app.texttango.com/api/v2/wallet/transactions*' => Http::response(['data' => [['attributes' => ['amount' => '150.00', 'currency' => 'GHS', 'status' => 'completed', 'created_at' => '2026-10-20T09:00:00Z']]]]),
            '*' => Http::response([], 404),
        ]);
        Setting::put(['texttango_token' => 'test-token']);
    }

    protected function as(string $role): User
    {
        $user = User::where('role', $role)->first();
        $this->actingAs($user);

        return $user;
    }

    public function test_guest_is_redirected_and_can_log_in(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->post('/login', ['email' => 'admin@burford.test', 'password' => 'password'])->assertRedirect('/dashboard');
    }

    public function test_every_admin_page_renders(): void
    {
        $this->as('admin');
        $loan = Loan::first();
        $app = LoanApplication::first();
        $customer = SusuCustomer::first();
        $collector = Collector::first();

        $pages = [
            '/dashboard', '/susu/customers', '/susu/customers/create', "/susu/customers/{$customer->id}", "/susu/customers/{$customer->id}/edit",
            '/susu/collections', '/susu/collections/create', "/susu/collections/create?customer={$customer->id}", '/susu/collections/sheet',
            '/susu/collections/1/edit', '/susu/collections/1/receipt',
            '/susu/withdrawals', '/susu/withdrawals/create', '/susu/collectors', '/susu/collectors/create', "/susu/collectors/{$collector->id}",
            "/susu/collectors/{$collector->id}/edit", '/susu/reconciliations', '/susu/reconciliations/create', '/susu/reconciliations/1/edit',
            '/credit/borrowers', '/credit/borrowers/create', '/credit/borrowers/1', '/credit/borrowers/1/edit',
            '/credit/applications', '/credit/applications/create', '/credit/applications/create?borrower=1', "/credit/applications/{$app->id}",
            "/credit/applications/{$app->id}/edit", "/credit/applications/{$app->id}/print",
            '/credit/loans', '/credit/loans/create', "/credit/loans/{$loan->id}", "/credit/loans/{$loan->id}/edit", "/credit/loans/{$loan->id}/statement",
            '/credit/repayments', '/credit/weekly-sheet',
            '/reports', '/reports/susu', '/reports/portfolio',
            '/sms', '/sms/compose', '/users', '/users/create', '/users/1/edit', '/branches', '/branches/create', '/branches/1/edit', '/settings',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }

        foreach (['customers', 'collections', 'withdrawals', 'loans', 'repayments'] as $set) {
            $this->get("/reports/export/$set")->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        }
    }

    public function test_dashboard_matches_the_spreadsheets(): void
    {
        $this->as('admin');
        $this->get('/dashboard')
            ->assertSee('GH₵ 1,420.00')   // TOTAL EXPECTED
            ->assertSee('GH₵ 1,370.00')   // TOTAL COLLECTED
            ->assertSee('96.5%')          // COLLECTION RATE
            ->assertSee('GH₵ 700.00')     // Customer withdrawals
            ->assertSee('GH₵ 14,000.00')  // Total disbursed
            ->assertSee('GH₵ 17,600.40')  // Total outstanding
            ->assertSee('4.6%');          // Repayment rate
    }

    public function test_roles_are_enforced(): void
    {
        $this->as('collector');
        $this->get('/susu/collections/sheet')->assertOk();
        $this->get('/credit/loans')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get('/susu/collectors')->assertForbidden();

        // Collector 1 must not see Collector 2's customer.
        $other = SusuCustomer::where('collector_id', '!=', auth()->user()->collector->id)->first();
        $this->get("/susu/customers/{$other->id}")->assertForbidden();

        $this->as('loan_officer');
        $this->get('/credit/loans')->assertOk();
        $this->get('/credit/weekly-sheet')->assertOk();
        $this->get('/susu/customers')->assertForbidden();
        $this->post('/credit/applications/1/decide', ['decision' => 'Declined'])->assertForbidden();
    }

    public function test_collection_records_status_and_sends_texttango_sms(): void
    {
        $this->as('manager');
        $customer = SusuCustomer::where('code', 'CUS001')->first();

        $this->post('/susu/collections', [
            'receipt_no' => 'RCP-9001', 'date' => today()->toDateString(), 'susu_customer_id' => $customer->id,
            'expected' => 50, 'collected' => 20, 'payment_method' => 'Cash', 'send_sms' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('susu_collections', ['receipt_no' => 'RCP-9001', 'status' => 'Partial', 'outstanding' => 30]);

        $sms = SmsMessage::where('type', 'collection')->latest('id')->first();
        $this->assertSame('sent', $sms->status);
        $this->assertSame('tt-uuid-1', $sms->provider_uuid);
        $this->assertSame('+233240000001', $sms->to);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://app.texttango.com/api/v2/sms/transactional/send'
            && $r->hasHeader('Authorization', 'Bearer test-token')
            && $r['to'] === '+233240000001'
            && $r['from'] === 'BURFORD'
            && str_contains($r['message'], 'GH₵ 20.00')
            && str_contains($r['message'], 'Balance: GH₵ 95.00'));
    }

    public function test_collection_sheet_saves_many_rows(): void
    {
        $this->as('manager');
        $collector = Collector::where('code', 'COL001')->first();
        $ids = $collector->customers()->pluck('id');

        $this->post('/susu/collections/sheet', [
            'collector_id' => $collector->id, 'date' => today()->toDateString(), 'send_sms' => 0,
            'rows' => [
                $ids[0] => ['expected' => 50, 'collected' => 50, 'method' => 'Cash'],
                $ids[1] => ['expected' => 30, 'collected' => '', 'method' => 'Cash'],
                $ids[2] => ['expected' => 20, 'collected' => 0, 'method' => 'Mobile Money'],
            ],
        ])->assertRedirect();

        $this->assertSame(2, \App\Models\SusuCollection::whereDate('date', today())->count());
        $this->assertDatabaseHas('susu_collections', ['susu_customer_id' => $ids[2], 'status' => 'Unpaid']);
    }

    public function test_withdrawal_cannot_exceed_balance(): void
    {
        $this->as('manager');
        $customer = SusuCustomer::where('code', 'CUS001')->first(); // balance 75

        $this->post('/susu/withdrawals', [
            'withdrawal_no' => 'WDR-9001', 'date' => today()->toDateString(), 'susu_customer_id' => $customer->id,
            'amount' => 80, 'type' => 'Normal', 'payment_method' => 'Cash',
        ])->assertSessionHasErrors('amount');

        $this->post('/susu/withdrawals', [
            'withdrawal_no' => 'WDR-9002', 'date' => today()->toDateString(), 'susu_customer_id' => $customer->id,
            'amount' => 70, 'commission' => 5, 'type' => 'Normal', 'payment_method' => 'Cash', 'send_sms' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0, $customer->fresh()->balance(), 0.001);
    }

    public function test_reconciliation_computes_variance(): void
    {
        $this->as('manager');
        $collector = Collector::where('code', 'COL001')->first();
        // 2026-09-02: two MoMo receipts of 50 for Ama Mensah.
        \App\Models\Reconciliation::whereDate('date', '2026-09-02')->delete();
        $this->post('/susu/reconciliations', [
            'date' => '2026-09-02', 'collector_id' => $collector->id, 'cash_counted' => 0, 'mobile_money' => 90, 'status' => 'Open',
        ])->assertSessionHasNoErrors();

        $rec = \App\Models\Reconciliation::whereDate('date', '2026-09-02')->where('collector_id', $collector->id)->first();
        $this->assertEquals(100, $rec->system_collections);
        $this->assertEquals(-10, $rec->variance);
    }

    public function test_full_loan_lifecycle(): void
    {
        $this->as('manager');
        $app = LoanApplication::where('status', 'Pending')->first();

        $this->post("/credit/applications/{$app->id}/decide", [
            'decision' => 'Approved', 'approved_amount' => 3000, 'interest_rate' => 5, 'approved_term' => 10,
            'approved_frequency' => 'Weekly', 'send_sms' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Approved', $app->fresh()->status);
        $this->assertTrue(SmsMessage::where('type', 'loan_approved')->exists());

        $this->post("/credit/applications/{$app->id}/disburse", [
            'disbursement_date' => today()->toDateString(), 'disbursement_method' => 'Mobile Money', 'send_sms' => 1,
        ])->assertRedirect();

        $loan = Loan::where('loan_application_id', $app->id)->firstOrFail();
        $this->assertEquals(1500, $loan->interest_amount);   // 3000 × 5% × 10
        $this->assertEquals(4500, $loan->total_repayment);
        $this->assertEquals(450, $loan->installment);
        $this->assertSame('Active', $loan->status);
        $this->assertSame(today()->addWeeks(10)->toDateString(), $loan->due_date->toDateString());
        $this->assertTrue(SmsMessage::where('type', 'loan_disbursed')->exists());

        $this->post("/credit/loans/{$loan->id}/repayments", ['date' => today()->toDateString(), 'amount' => 5000, 'payment_method' => 'Cash'])
            ->assertSessionHasErrors('amount');

        $this->post("/credit/loans/{$loan->id}/repayments", ['date' => today()->toDateString(), 'amount' => 450, 'payment_method' => 'Cash', 'send_sms' => 1])
            ->assertSessionHasNoErrors();
        $loan->refresh();
        $this->assertEquals(450, $loan->amount_repaid);
        $this->assertSame('Paid', $loan->schedule()->first()['status']);
        $this->assertTrue(SmsMessage::where('type', 'repayment')->exists());

        $this->post("/credit/loans/{$loan->id}/repayments", ['date' => today()->toDateString(), 'amount' => 4050, 'payment_method' => 'Cash', 'send_sms' => 0]);
        $this->assertSame('Closed', $loan->fresh()->status);
    }

    public function test_seeded_loan_matches_workbook_and_is_overdue(): void
    {
        $loan = Loan::where('loan_no', 'BCL-0001')->first(); // Edua Kojo
        $this->assertEquals(1850.40, (float) $loan->interest_amount);
        $this->assertEquals(5850.40, (float) $loan->total_repayment);
        $this->assertEquals(5200.40, $loan->outstanding());
        $this->assertSame('Overdue', $loan->status);
    }

    public function test_reminder_command_and_bulk_sms(): void
    {
        $this->artisan('sms:loan-reminders')->assertSuccessful();

        $this->as('manager');
        $this->post('/sms/send', ['mode' => 'group', 'group' => 'susu_active', 'message' => 'Hello {name} from {company}'])->assertRedirect('/sms');
        $this->assertSame(6, SmsMessage::where('type', 'bulk')->count());
        $this->assertTrue(SmsMessage::where('message', 'Hello Ama Mensah from MK DUA PA')->exists());

        $this->post('/sms/send', ['mode' => 'single', 'to' => '0241112222, 0551112222', 'message' => 'Test'])->assertRedirect();
        $this->assertTrue(SmsMessage::where('to', '+233551112222')->exists());
    }

    public function test_sms_failures_are_logged_not_thrown(): void
    {
        Setting::put(['texttango_token' => '']);
        config(['services.texttango.token' => null]);
        $this->as('manager');
        $customer = SusuCustomer::first();
        $this->post('/susu/collections', [
            'receipt_no' => 'RCP-9100', 'date' => today()->toDateString(), 'susu_customer_id' => $customer->id,
            'expected' => 50, 'collected' => 50, 'payment_method' => 'Cash', 'send_sms' => 1,
        ])->assertRedirect();
        $this->assertSame('failed', SmsMessage::latest('id')->first()->status);
    }

    public function test_phone_normalisation(): void
    {
        $this->assertSame('+233241234567', TextTango::normalizePhone('024 123 4567'));
        $this->assertSame('+233241234567', TextTango::normalizePhone('233241234567'));
        $this->assertSame('+233241234567', TextTango::normalizePhone('+233 24 123 4567'));
        $this->assertSame('+233241234567', TextTango::normalizePhone('241234567'));
    }

    public function test_settings_save_and_wallet_shows(): void
    {
        $this->as('admin');
        $this->put('/settings', [
            'company_name' => 'MK DUA PA', 'default_interest_rate' => 4, 'loan_prefix' => 'BCL-', 'reminder_days_before' => 2,
            'texttango_base_url' => 'https://app.texttango.com/api', 'texttango_sender_id' => 'BURFORD', 'texttango_token' => '',
            'sms_enabled' => 1, 'templates' => ['collection' => 'Hi {name}'], 'toggles' => ['collection' => 1],
        ])->assertSessionHasNoErrors();

        $this->assertSame('test-token', Setting::get('texttango_token')); // blank keeps the old token
        $this->assertSame('0', Setting::get('sms_on_withdrawal'));
        $this->get('/sms')->assertSee('GHS 42.50');
    }

    public function test_dashboard_sms_credit_and_top_up(): void
    {
        $this->as('manager');
        $this->get('/dashboard')->assertOk()
            ->assertSee('SMS credit')
            ->assertSee('GHS 42.50')
            ->assertSee('https://app.texttango.com/wallet', false)
            ->assertSee('GHS 150.00'); // recent top-up from /wallet/transactions

        Setting::put(['sms_low_balance' => 100]);
        $this->get('/dashboard')->assertSee('Running low');

        $this->post('/sms/wallet/refresh')->assertRedirect()->assertSessionHas('success', 'SMS credit balance: GHS 42.50.');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v2/wallet/transactions') && str_contains($r->url(), 'type=load_wallet'));

        // Collectors and loan officers don't see the SMS card.
        $this->as('collector');
        $this->get('/dashboard')->assertOk()->assertDontSee('SMS credit · TextTango')->assertDontSee('Top up SMS credit');
    }

    public function test_help_page_matches_role(): void
    {
        $this->as('collector');
        $this->get('/help')->assertOk()->assertSee('Collection Sheet')->assertDontSee('Running the system')->assertDontSee('Topping up SMS credit');

        $this->as('loan_officer');
        $this->get('/help')->assertOk()->assertSee('Recording repayments')->assertDontSee('End-of-day reconciliation');

        $this->as('admin');
        $this->get('/help')->assertOk()->assertSee('Topping up SMS credit')->assertSee('Running the system');
    }

    public function test_any_user_can_change_their_own_password(): void
    {
        $user = $this->as('collector');
        $this->get('/account/password')->assertOk()->assertSee('Change password');

        // Wrong current password, and a new one that doesn't match its confirmation, are both rejected.
        $this->put('/account/password', ['current_password' => 'nope', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
            ->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors('password');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->fresh()->password));

        $this->put('/account/password', ['current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
            ->assertRedirect('/account/password')->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secret-1', $user->fresh()->password));

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'new-secret-1'])->assertRedirect('/dashboard');
    }

    public function test_managers_do_not_see_the_administrator_in_officer_lists(): void
    {
        $this->as('manager');
        $this->get('/credit/applications/create')->assertOk()->assertDontSee('System Administrator');
        $this->get('/credit/loans')->assertOk()->assertDontSee('System Administrator');

        // A loan already assigned to the administrator keeps them selected, so saving doesn't wipe the officer.
        $loan = Loan::first();
        $loan->update(['officer_id' => User::where('role', 'admin')->value('id')]);
        $this->get(route('credit.loans.edit', $loan))->assertOk()->assertSee('System Administrator');

        $this->as('admin');
        $this->get('/credit/applications/create')->assertOk()->assertSee('System Administrator');
    }
}
