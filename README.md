# MK DUA PA

Laravel 12 dashboard for running a microfinance business: **susu (daily savings) collections** and **micro-credit loans**, with **TextTango SMS** notifications.

It is built from the business's existing workbooks:

| Source | Becomes |
|---|---|
| *MK DUA PA SUSU ENTERPRISE.xlsx* — Customer Master, Daily Collections, Withdrawals, Collector Register, Daily Reconciliation, Customer Ledger, Executive Dashboard | Susu module + dashboard KPIs |
| *MK Micro Credit.xlsx* — Loan Data, WEEKLY REPAYMENT, Summary Tables, Dashboard | Loans, repayment schedule, weekly sheet, portfolio report |
| *MK credit forms.pdf* — credit application form, sections A–E plus "For official use only" | Loan application form, approval workflow and a printable copy of the form |

## Features

- **Dashboard**: expected / collected / outstanding / collection rate, unreconciled variance, withdrawals, paid vs partial receipts, a 60-day collection trend, the customer portfolio, plus disbursed / outstanding / PAR30 / repayment rate / default rate / arrears, monthly disbursements, loan status breakdown, loans in arrears and pending applications.
- **Susu**
  - Customers with a running-balance ledger.
  - A collection sheet to record a collector's whole round in one go, plus single receipts. Outstanding and the Paid / Partial / Unpaid status are calculated automatically.
  - Withdrawals are checked against the customer's balance, with an optional commission.
  - Collectors have targets and cash limits.
  - Daily cash and MoMo reconciliation shows the variance.
- **Micro credit**
  - Borrowers with a passport photo.
  - The full application form, approved or declined under "official use".
  - Approved applications are disbursed into a loan.
  - Interest is flat (principal × rate × term), the same as the workbook.
  - Each loan has an installment schedule (W1…Wn) with oldest-first payment allocation and arrears and days overdue.
  - Repayments can be reversed.
  - Each loan has a printable statement, and there is a weekly repayment sheet across all loans.
- **Reports**: a susu report (collector performance against target, payment-method mix, daily summary), a portfolio report (ageing buckets, branch performance, at-risk loans) and CSV exports that open in Excel.
- **SMS (TextTango)**
  - Messages go out automatically for:
    - susu receipts and withdrawals
    - loan approval or decline
    - disbursement, with the repayment plan
    - repayment receipts
    - daily reminders before due dates, and overdue notices on days 1, 3 and 7, then weekly
  - You can also send to specific numbers or broadcast to a group.
  - The SMS log can resend a message or check its delivery status, and shows the wallet balance.
  - Templates and on/off switches are editable in Settings.
- **Roles**:

  | Role | Can access |
  |---|---|
  | Admin | Everything |
  | Manager | All operations, approvals, reports and SMS |
  | Loan Officer | Credit only |
  | Field Collector | Only their own customers and receipts |

- **UI**: Tailwind 4, Alpine.js and Chart.js, a responsive sidebar layout, dark mode, and print-ready receipts, statements and forms.

## Setup

Requirements: PHP 8.2+, Composer, Node 18+. SQLite works out of the box; MySQL is supported too.

```bash
composer install
npm install && npm run build
cp .env.example .env        # already done in this copy
php artisan key:generate    # already done in this copy
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000 and sign in. Every seeded account uses the password `password`.

| Role | Email |
|---|---|
| Admin | admin@burford.test |
| Manager | manager@burford.test |
| Loan officer | officer@burford.test |
| Collectors | collector1@burford.test, collector2@burford.test |

**Change these passwords before going live.**

The seeder loads the sample data from both workbooks: 6 customers, 2 collectors, 33 receipts, 4 withdrawals and the 2 loans. Use it to check that the dashboard matches the spreadsheets. To start empty instead, run `php artisan migrate:fresh` and create an admin with `php artisan tinker`.

### MySQL (e.g. XAMPP)

In `.env`, set the following, then run `php artisan migrate --seed`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=burford
DB_USERNAME=root
DB_PASSWORD=
```

## TextTango SMS

1. In TextTango go to **Profile → API Tokens** and create a token.
2. In the app go to **Settings → TextTango SMS**:
   - Paste the token.
   - Set your approved **Sender ID** (11 characters max).
   - Click **Send test**.
3. Alternatively, set `TEXTTANGO_API_TOKEN` and `TEXTTANGO_SENDER_ID` in `.env`. Values saved in Settings take precedence.

The integration (`app/Services/TextTango.php`) calls TextTango's v2 API:

- Send: `POST https://app.texttango.com/api/v2/sms/transactional/send` with a Bearer token and the body `{to, message, from}`.
- Balance: `GET /v2/wallet`.
- Recent top-ups: `GET /v2/wallet/transactions?type=load_wallet`.

**Topping up.** TextTango's API can't take payments, so the dashboard's **Top up** button opens TextTango's wallet page (`https://app.texttango.com/wallet` by default, changeable in Settings), where the account owner pays. **Refresh balance** then re-reads the wallet. The dashboard card turns amber below the low-credit level set in Settings (GH₵ 20 by default) and red at zero.

Staff can find a plain-language guide under **Help & guide** in the menu (`resources/views/help.blade.php`). It shows each person only the sections for their role.

Ghana numbers such as `024…` are converted to `+233…` automatically. Sends are never retried automatically, so a timeout can't double-bill. Every message is logged in the **SMS Centre**, where a failed message can be resent.

### Scheduled jobs (reminders and loan status)

```bash
php artisan schedule:work   # keep running, or add a cron entry for: php artisan schedule:run
```

| Command | When it runs | What it does |
|---|---|---|
| `loans:refresh-status` | 00:30 daily | Marks loans Active, Overdue or Closed from the schedule |
| `sms:loan-reminders` | 08:00 daily | Sends reminder and overdue SMS |

## Business rules

| Rule | Formula |
|---|---|
| Receipt outstanding | `max(expected − collected, 0)` |
| Receipt status | Paid if outstanding = 0, Partial if something was collected, otherwise Unpaid |
| Customer balance | opening balance + collections − withdrawals − commission |
| Reconciliation variance | (cash + MoMo counted) − system collections for that collector and day |
| Loan interest | principal × rate% × term |
| Loan total | principal + interest |
| Installment | total ÷ term |

- A loan is **Overdue** when the amount paid is below the amount scheduled to date. **Defaulted** is set manually.
- **PAR30**: outstanding balance on loans with an installment 30 or more days late, divided by total outstanding.
- **Repayment rate**: total repaid ÷ total disbursed, the same definition as the workbook.

## Tests

```bash
php artisan test
```

The tests cover:

- every page rendering
- role restrictions
- the dashboard figures against the spreadsheet
- collection, withdrawal and reconciliation maths
- the full application → approval → disbursement → repayment → closure flow
- the TextTango request payload (with HTTP faked)
