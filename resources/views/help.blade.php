@php
    $u = auth()->user();
    $company = \App\Models\Setting::get('company_name', 'BURFORD Collection');
    $susu = $u->hasRole('admin', 'manager', 'collector');
    $credit = $u->hasRole('admin', 'manager', 'loan_officer');
    $manager = $u->hasRole('admin', 'manager');
    $admin = $u->isAdmin();
    $sections = array_filter([
        'start' => 'Getting started',
        'dashboard' => 'Your dashboard',
        'susu' => $susu ? 'Susu collections' : null,
        'loans' => $credit ? 'Loans' : null,
        'sms' => 'Text messages (SMS)',
        'reports' => $manager ? 'Reports' : null,
        'admin' => $admin ? 'Running the system' : null,
        'faq' => 'Common questions',
    ]);
@endphp
<x-layouts.app title="Help & guide">
    <x-page-header title="Help & guide" :subtitle="'How to get your daily work done in '.$company.'. You only see the parts that apply to your role ('.$u->roleLabel().').'" />

    <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
        {{-- Contents --}}
        <nav class="no-print lg:sticky lg:top-24 lg:self-start">
            <div class="card p-2">
                <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500">On this page</p>
                @foreach ($sections as $id => $label)
                    <a href="#{{ $id }}" class="block rounded-lg px-3 py-1.5 text-sm text-ink-600 hover:bg-ink-50 hover:text-ink-900 dark:text-ink-300 dark:hover:bg-ink-800 dark:hover:text-white">{{ $label }}</a>
                @endforeach
            </div>
        </nav>

        <div class="help-guide min-w-0 space-y-6">
            <section id="start" class="card card-body scroll-mt-24">
                <h2>Getting started</h2>
                <p>Welcome! This is where the team records susu collections, looks after loans and keeps customers informed by text message. Everything is saved as you go, so there is no "save file" to remember.</p>
                <h3>Signing in</h3>
                <p>Use the email address and password your administrator gave you. If you forget your password, ask your administrator. They can set a new one for you from the Users page.</p>
                <h3>Changing your password</h3>
                <p>Click your name at the bottom of the menu (or the little key next to it). Type your current password once, then your new one twice, and press <strong>Change password</strong>. It's a good idea to do this the first time you sign in, so you're the only one who knows it.</p>
                <h3>Finding your way around</h3>
                <ul>
                    <li>The menu on the left lists everything you're allowed to use. If something you need is missing, your role probably doesn't include it. Have a word with your administrator.</li>
                    <li>On a phone, tap the <strong>☰</strong> button at the top to open the menu.</li>
                    <li>The moon/sun button at the top right switches between light and dark screens. Use whichever is easier on your eyes.</li>
                    <li>When you're done, press the arrow next to your name at the bottom of the menu to sign out, especially on a shared computer.</li>
                </ul>
                <h3>Before anything important happens</h3>
                <p>Whenever an action moves money, deletes something or sends text messages, a box pops up asking you to confirm. Read it: it shows the amounts and names involved. Press <strong>Cancel</strong> if anything looks wrong.</p>
            </section>

            <section id="dashboard" class="card card-body scroll-mt-24">
                <h2>Your dashboard</h2>
                <p>The dashboard is the first thing you see after signing in. It's a quick picture of how today is going.</p>
                <ul>
                    @if ($susu)<li><strong>Susu figures</strong> show what was expected, what was collected and the collection rate.@if ($u->hasRole('collector') && ! $manager) As a collector, you only see your own customers and figures.@endif</li>@endif
                    @if ($credit)<li><strong>Loan figures</strong> show what's still owed, today's repayments, applications waiting for a decision and borrowers who are behind.</li>@endif
                    @if ($manager)<li><strong>SMS credit</strong> shows how much text message credit is left. See <a href="#top-up">Topping up SMS credit</a>.</li>@endif
                </ul>
            </section>

            @if ($susu)
                <section id="susu" class="card card-body scroll-mt-24">
                    <h2>Susu collections</h2>
                    <h3>Recording a day's collections</h3>
                    <p>The quickest way is the <strong>Collection Sheet</strong>. It works like the paper sheet you carry on your round.</p>
                    <ol>
                        <li>Open <strong>Collection Sheet</strong> from the menu.@if ($manager) Pick the collector and the date, then load the sheet.@endif</li>
                        <li>For each customer who paid, type the amount they gave you. If they paid their usual amount, press the small tick and it fills in for you.</li>
                        <li>Choose how they paid (cash, Mobile Money or bank).</li>
                        <li>Leave <em>SMS each customer their receipt</em> ticked if you want them to get a text confirming the payment.</li>
                        <li>Press <strong>Save collections</strong>, check the total in the box that pops up, and confirm.</li>
                    </ol>
                    <p>Customers you've already recorded today show a green badge, so you won't enter them twice. The system works out whether each customer is <em>Paid</em>, <em>Partial</em> or <em>Unpaid</em> by itself.</p>

                    <h3>One receipt at a time</h3>
                    <p>If a customer pays at the office, or you missed someone, use <strong>Receipts → New receipt</strong>. From the Receipts list you can also print a receipt for the customer.</p>

                    <h3>Customers</h3>
                    <p>Use <strong>Customers</strong> to register someone new or update their details. Get the phone number right, because that's where their text messages go. The <em>contribution amount</em> is what you expect from them each time you collect.</p>

                    <h3>Withdrawals</h3>
                    <p>When a customer takes money out, record it under <strong>Withdrawals → New withdrawal</strong>. As you type, the screen shows their available balance and what will be left afterwards. It won't let you pay out more than they have. Choose <em>Closure</em> as the type if they're closing their account for good.</p>

                    @if ($manager)
                        <h3>End-of-day reconciliation</h3>
                        <p>At the end of the day, check each collector's cash against what they recorded. Open <strong>Reconciliation</strong>, pick the collector and date, and enter the cash they handed in. The system brings in what their receipts say they collected, so any shortage or excess shows straight away. Note the bank deposit amount and reference if they banked it.</p>
                        <h3>Collectors</h3>
                        <p>Under <strong>Collectors</strong> you set each collector's area, daily target and the most cash they may hold before depositing. To let a collector sign in and see only their own customers, link them to a user account with the <em>Field Collector</em> role.</p>
                    @endif
                </section>
            @endif

            @if ($credit)
                <section id="loans" class="card card-body scroll-mt-24">
                    <h2>Loans</h2>
                    <p>A loan goes through four steps: <strong>application → decision → disbursement → repayments</strong>.</p>

                    <h3>1. Taking an application</h3>
                    <p>Press <strong>Loan application</strong> at the top of any page. The form follows the paper credit form: the applicant's details, their business, family, any other loans they're paying, the amount and term they'd like, and a guarantor. Once it's saved you can print it for the applicant to sign.</p>

                    <h3>2. Approving or declining</h3>
                    @if ($manager)
                        <p>Open the application and use the <strong>For official use only</strong> panel. Choose Approve, keep it Pending or Decline. When approving, set the amount, the interest rate per period, the number of repayments and how often they're due. The panel shows the interest, the total to repay and each installment as you type.</p>
                        <p>Interest is <em>flat</em>: amount × rate × number of periods. For example, GH₵ 1,000 at 5% for 10 weeks is GH₵ 500 interest, so the borrower repays GH₵ 1,500 in ten installments of GH₵ 150.</p>
                    @else
                        <p>A manager reviews each application and approves or declines it. You'll see the decision on the application page, and the applicant can get a text message about it.</p>
                    @endif

                    <h3>3. Paying out the loan</h3>
                    @if ($manager)
                        <p>Once an application is approved, press <strong>Disburse loan</strong> on it. Enter the date the money was handed over and how it was paid, then confirm. This creates the loan and its repayment schedule, and can text the borrower their repayment plan.</p>
                    @else
                        <p>A manager hands over approved loans. After that, the loan appears under <strong>Loans</strong> with its full repayment schedule.</p>
                    @endif

                    <h3>4. Recording repayments</h3>
                    <ol>
                        <li>Open the loan from <strong>Loans</strong>.</li>
                        <li>In <strong>Record repayment</strong>, the next amount due is already filled in. The <em>Arrears</em> and <em>Pay off</em> buttons fill in those amounts instead.</li>
                        <li>Choose the payment method, and add the Mobile Money transaction ID if there is one.</li>
                        <li>Press <strong>Save repayment</strong> and confirm.</li>
                    </ol>
                    <p>The coloured boxes across the schedule show each period at a glance: green is paid, amber is part-paid, red is missed and grey is still to come. When the last cedi is paid, the loan closes by itself.@if ($manager) If a repayment was entered by mistake, you can reverse it from the <em>Payments received</em> list.@endif</p>

                    <h3>Statements and reminders</h3>
                    <p>Press <strong>Statement</strong> on a loan to print everything the borrower has paid and still owes. <strong>SMS reminder</strong> texts them their balance and next payment. You don't need to remember this, though: reminders also go out automatically every morning before a payment is due, and again when a payment is late.</p>
                </section>
            @endif

            <section id="sms" class="card card-body scroll-mt-24">
                <h2>Text messages (SMS)</h2>
                <p>Customers and borrowers get a text message automatically when:</p>
                <ul>
                    <li>a susu payment or withdrawal is recorded (their receipt and new balance)</li>
                    <li>a loan application is approved or declined</li>
                    <li>a loan is paid out (with the repayment plan)</li>
                    <li>a loan repayment is recorded</li>
                    <li>a loan payment is coming up or is late</li>
                </ul>
                <p>Most screens have a <em>send SMS</em> tick box, so you can skip the text for one entry if you need to.</p>

                @if ($manager)
                    <h3>The SMS Centre</h3>
                    <p>Every message is kept in the <strong>SMS Centre</strong> with its status:</p>
                    <ul>
                        <li><strong>Sent</strong>: handed over to the phone network.</li>
                        <li><strong>Failed</strong>: it didn't go. The reason is shown in red underneath, and you can press the resend button to try again.</li>
                        <li><strong>Queued</strong>: waiting to go out.</li>
                        <li><strong>Disabled</strong>: logged but not sent, because text messages are switched off or not set up yet.</li>
                    </ul>
                    <p>To send your own message, press <strong>Compose</strong>. You can text one or more numbers, or send to a whole group, such as all active susu customers or everyone in arrears. Type <code>{name}</code> where the person's name should go and it fills in for each person. The preview shows how it will look.</p>

                    <h3 id="top-up" class="scroll-mt-24">Topping up SMS credit</h3>
                    <p>Text messages are paid for from a prepaid TextTango account. Each one uses a little credit, and when it runs out messages stop going out. The dashboard warns you in amber when credit is running low and in red when it has run out.</p>
                    <ol>
                        <li>On the dashboard, press <strong>Top up</strong> on the SMS credit card.</li>
                        <li>Press <strong>Open TextTango wallet</strong>. TextTango's site opens in a new tab. Sign in with the company's TextTango account.</li>
                        <li>Choose an amount and pay on TextTango's site. They handle the payment, so this system never sees your card or Mobile Money details.</li>
                        <li>Come back and press <strong>Refresh balance</strong>. If the new credit isn't showing yet, give it a minute and try again.</li>
                    </ol>
                    <p>If any messages failed while you were out of credit, you can resend them from the SMS Centre.</p>
                @else
                    <p>If a customer says they didn't get a message, double-check the phone number on their record, then let your manager know. They can see every message and resend it.</p>
                @endif
            </section>

            @if ($manager)
                <section id="reports" class="card card-body scroll-mt-24">
                    <h2>Reports</h2>
                    <p>Open <strong>Reports</strong> for the bigger picture:</p>
                    <ul>
                        <li><strong>Susu report</strong>: how each collector is doing against their target, how customers are paying, and a day-by-day summary.</li>
                        <li><strong>Portfolio report</strong>: the loan book by how late payments are, how each branch is doing, and the loans most at risk.</li>
                        <li><strong>Weekly repayment sheet</strong>: every loan's payments week by week, like the old spreadsheet.</li>
                    </ul>
                    <p>Use the download buttons to get a file that opens in Excel. Pages such as receipts, statements and the application form have a print button, and print cleanly without the menus.</p>
                </section>
            @endif

            @if ($admin)
                <section id="admin" class="card card-body scroll-mt-24">
                    <h2>Running the system</h2>
                    <h3>People and what they can do</h3>
                    <p>Add staff under <strong>Users</strong> and give each person a role:</p>
                    <ul>
                        <li><strong>Administrator</strong>: everything, including users, branches and settings.</li>
                        <li><strong>Manager</strong>: all daily work, reports, the SMS Centre, approving and paying out loans.</li>
                        <li><strong>Loan Officer</strong>: borrowers, applications, loans and repayments.</li>
                        <li><strong>Field Collector</strong>: only their own susu customers, collection sheet, receipts and withdrawals.</li>
                    </ul>
                    <p>When someone leaves, delete their account so they can no longer sign in. Their past work stays on record.</p>
                    <h3>Branches</h3>
                    <p>Add each office under <strong>Branches</strong> so collectors and loans can be grouped by location in reports.</p>
                    <h3>Settings</h3>
                    <ul>
                        <li><strong>Company</strong>: the name, phone and address printed on receipts and forms.</li>
                        <li><strong>Lending defaults</strong>: the usual interest rate, the start of each loan number, and how many days before a due date reminders go out.</li>
                        <li><strong>TextTango SMS</strong>: connects text messaging. Paste the API token from your TextTango account (Profile → API Tokens), enter your approved sender name, and use <em>Send a test SMS</em> to make sure it works. You can also set when the low-credit warning appears.</li>
                        <li><strong>Notification templates</strong>: change the wording of each automatic message, or switch one off entirely.</li>
                    </ul>
                </section>
            @endif

            <section id="faq" class="card card-body scroll-mt-24">
                <h2>Common questions</h2>
                <div class="divide-y divide-ink-100 dark:divide-ink-800">
                    @foreach (array_filter([
                        $susu ? ['I typed the wrong amount for a customer. What now?', 'Open Receipts, find the entry and edit it. The customer\'s balance updates by itself. If it needs removing completely, ask a manager to delete it.'] : null,
                        ['A customer says they didn\'t get a text message.', 'First check their phone number is correct on their record. '.($manager ? 'Then look the message up in the SMS Centre: if it failed, the reason is shown and you can resend it. Also check the SMS credit on the dashboard.' : 'Then let your manager know. They can see whether it was sent and resend it.')],
                        ['I can\'t find a page someone mentioned.', 'Each role sees a different menu. If you need access to something, ask your administrator to check your role.'],
                        $credit ? ['Why does a loan say Overdue?', 'At least one payment that was due hasn\'t been fully paid. Loan statuses are updated every night, and the borrower gets reminder texts on the 1st, 3rd and 7th day after a missed payment, then weekly.'] : null,
                        ['Nothing happened when I pressed a button.', 'Look for a message in the bottom corner or a box in the middle of the screen. If a form has a problem, the fields that need fixing are outlined in red.'],
                        ['Is my work saved if the internet drops?', 'Each entry is saved the moment you press save and see the green confirmation. If the connection drops before that, check the list (for example, Receipts) before entering it again so you don\'t record it twice.'],
                    ]) as [$q, $a])
                        <details class="group py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-ink-900 dark:text-white">{{ $q }}<x-icon name="chevron-down" class="size-4 shrink-0 text-ink-400 transition group-open:rotate-180" /></summary>
                            <p class="mt-2">{{ $a }}</p>
                        </details>
                    @endforeach
                </div>
                <p class="mt-4 rounded-xl bg-ink-50 p-4 dark:bg-ink-800/60">Still stuck? Speak to {{ $admin ? 'whoever supports this system for you' : 'your manager or administrator' }}. It helps to say which page you were on and what you pressed.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
