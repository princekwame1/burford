<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Credit\ApplicationController;
use App\Http\Controllers\Credit\BorrowerController;
use App\Http\Controllers\Credit\LoanController;
use App\Http\Controllers\Credit\RepaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\Susu\CollectionController;
use App\Http\Controllers\Susu\CollectorController;
use App\Http\Controllers\Susu\CustomerController;
use App\Http\Controllers\Susu\ReconciliationController;
use App\Http\Controllers\Susu\WithdrawalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::view('/help', 'help')->name('help');
    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update')->middleware('throttle:6,1');

    // ---------------- Susu ----------------
    Route::middleware('role:manager,collector')->prefix('susu')->name('susu.')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::get('collections/sheet', [CollectionController::class, 'sheet'])->name('collections.sheet');
        Route::post('collections/sheet', [CollectionController::class, 'storeSheet'])->name('collections.sheet.store');
        Route::get('collections/{collection}/receipt', [CollectionController::class, 'receipt'])->name('collections.receipt');
        Route::resource('collections', CollectionController::class)->except('show');
        Route::resource('withdrawals', WithdrawalController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::middleware('role:manager')->group(function () {
            Route::resource('collectors', CollectorController::class);
            Route::resource('reconciliations', ReconciliationController::class)->except('show');
        });
    });

    // ---------------- Micro Credit ----------------
    Route::middleware('role:manager,loan_officer')->prefix('credit')->name('credit.')->group(function () {
        Route::resource('borrowers', BorrowerController::class);
        Route::get('applications/{application}/print', [ApplicationController::class, 'print'])->name('applications.print');
        Route::post('applications/{application}/decide', [ApplicationController::class, 'decide'])->name('applications.decide')->middleware('role:manager');
        Route::post('applications/{application}/disburse', [ApplicationController::class, 'disburse'])->name('applications.disburse')->middleware('role:manager');
        Route::resource('applications', ApplicationController::class);
        Route::get('loans/{loan}/statement', [LoanController::class, 'statement'])->name('loans.statement');
        Route::post('loans/{loan}/remind', [LoanController::class, 'remind'])->name('loans.remind');
        Route::resource('loans', LoanController::class);
        Route::post('loans/{loan}/repayments', [RepaymentController::class, 'store'])->name('repayments.store');
        Route::get('repayments', [RepaymentController::class, 'index'])->name('repayments.index');
        Route::get('weekly-sheet', [ReportController::class, 'weekly'])->name('weekly');
        Route::delete('repayments/{repayment}', [RepaymentController::class, 'destroy'])->name('repayments.destroy')->middleware('role:manager');
    });

    // ---------------- Reports ----------------
    Route::middleware('role:manager')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('susu', [ReportController::class, 'susu'])->name('susu');
        Route::get('portfolio', [ReportController::class, 'portfolio'])->name('portfolio');
        Route::get('export/{dataset}', [ReportController::class, 'export'])->name('export');
    });

    // ---------------- SMS ----------------
    Route::middleware('role:manager')->prefix('sms')->name('sms.')->group(function () {
        Route::get('/', [SmsController::class, 'index'])->name('index');
        Route::get('compose', [SmsController::class, 'compose'])->name('compose');
        Route::post('send', [SmsController::class, 'send'])->name('send');
        Route::post('wallet/refresh', [SmsController::class, 'refreshWallet'])->name('wallet.refresh');
        Route::post('{sms}/resend', [SmsController::class, 'resend'])->name('resend');
        Route::post('{sms}/status', [SmsController::class, 'status'])->name('status');
    });

    // ---------------- Administration ----------------
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('branches', BranchController::class)->except('show');
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/sms-test', [SettingController::class, 'testSms'])->name('settings.sms-test');
    });
});
