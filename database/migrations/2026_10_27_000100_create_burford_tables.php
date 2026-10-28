<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // ---------------- SUSU ----------------
        Schema::create('collectors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('territory')->nullable();
            $table->date('start_date')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive, Suspended
            $table->decimal('daily_target', 12, 2)->default(0);
            $table->decimal('cash_limit', 12, 2)->default(0);
            $table->string('supervisor')->nullable();
            $table->timestamps();
        });

        Schema::create('susu_customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('ghana_card')->nullable();
            $table->foreignId('collector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan')->default('Daily'); // Daily, Weekly, Monthly
            $table->decimal('contribution', 12, 2)->default(0);
            $table->string('frequency')->default('Daily');
            $table->date('start_date')->nullable();
            $table->string('status')->default('Active'); // Active, Dormant, Closed
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('susu_collections', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->date('date')->index();
            $table->foreignId('susu_customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('expected', 12, 2)->default(0);
            $table->decimal('collected', 12, 2)->default(0);
            $table->decimal('outstanding', 12, 2)->default(0);
            $table->string('payment_method')->default('Cash'); // Cash, Mobile Money, Bank
            $table->string('status')->default('Paid'); // Paid, Partial, Unpaid
            $table->string('verified_by')->nullable();
            $table->string('deposit_ref')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('susu_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_no')->unique();
            $table->date('date')->index();
            $table->foreignId('susu_customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission', 12, 2)->default(0);
            $table->string('type')->default('Normal'); // Normal, Emergency, Closure
            $table->string('approved_by')->nullable();
            $table->string('payment_method')->default('Cash');
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('collector_id')->constrained()->cascadeOnDelete();
            $table->decimal('system_collections', 12, 2)->default(0);
            $table->decimal('cash_counted', 12, 2)->default(0);
            $table->decimal('mobile_money', 12, 2)->default(0);
            $table->decimal('total_counted', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('deposit_ref')->nullable();
            $table->string('status')->default('Open'); // Open, Reconciled, Escalated
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['date', 'collector_id']);
        });

        // ---------------- MICRO CREDIT ----------------
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title')->nullable();
            $table->string('full_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable()->default('Ghanaian');
            $table->string('ghana_card')->nullable();
            $table->string('residential_address')->nullable();
            $table->string('digital_address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('photo_path')->nullable();
            // Employment / business
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('business_nature')->nullable();
            $table->string('business_registration_no')->nullable();
            $table->unsignedSmallInteger('years_in_operation')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('business_location')->nullable();
            $table->string('business_address')->nullable();
            $table->string('business_contact_person')->nullable();
            $table->string('business_phone')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->date('application_date');
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount_requested', 12, 2);
            $table->string('purpose')->nullable();
            $table->string('purpose_other')->nullable();
            $table->unsignedSmallInteger('preferred_term')->nullable();
            $table->string('repayment_frequency')->default('Weekly');
            $table->date('preferred_disbursement_date')->nullable();
            $table->date('proposed_repayment_start')->nullable();
            $table->json('family_members')->nullable();
            $table->json('existing_obligations')->nullable();
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('guarantor_relationship')->nullable();
            $table->string('guarantor_address')->nullable();
            $table->boolean('declaration_accepted')->default(false);
            // Official use
            $table->string('status')->default('Pending'); // Pending, Approved, Declined, Disbursed
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('interest_rate', 6, 3)->nullable();
            $table->unsignedSmallInteger('approved_term')->nullable();
            $table->string('approved_frequency')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no')->unique();
            $table->foreignId('borrower_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('disbursement_date')->index();
            $table->decimal('principal', 12, 2);
            $table->decimal('interest_rate', 6, 3); // flat % per period
            $table->unsignedSmallInteger('term'); // number of periods
            $table->string('frequency')->default('Weekly'); // Daily, Weekly, Bi-weekly, Monthly
            $table->date('first_due_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('total_repayment', 12, 2);
            $table->decimal('installment', 12, 2);
            $table->decimal('amount_repaid', 12, 2)->default(0);
            $table->string('status')->default('Active'); // Active, Overdue, Defaulted, Closed
            $table->string('disbursement_method')->default('Cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->unsignedSmallInteger('period_no')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('Cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ---------------- SMS ----------------
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->text('message');
            $table->string('type')->default('manual'); // manual, collection, withdrawal, loan_approved, loan_disbursed, repayment, reminder, overdue, bulk
            $table->nullableMorphs('related');
            $table->string('status')->default('queued'); // queued, sent, failed, disabled
            $table->string('provider_uuid')->nullable()->index();
            $table->text('error')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sms_messages', 'loan_repayments', 'loans', 'loan_applications', 'borrowers', 'reconciliations',
            'susu_withdrawals', 'susu_collections', 'susu_customers', 'collectors', 'branches', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
