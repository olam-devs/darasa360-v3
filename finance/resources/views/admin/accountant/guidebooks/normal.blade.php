@extends('layouts.accountant')

@section('title', 'Normal Accountant Guidebook — Darasa Finance')
@section('page_title', 'Guidebook')

@push('head')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap">
<style>
:root {
  --gn: #0D3B57; --gt: #17876D; --gtw: #E6F4F1; --gaw: #FEF3C7; --ga: #D97706;
  --gbg: #F4F6F2; --gmu: #6C7A8E; --gbr: #D8E0DA;
}
.gb-wrap { max-width: 860px; margin: 0 auto; font-family: 'Nunito', sans-serif; }
.gb-cover {
  background: var(--gn); color: #fff; border-radius: 12px;
  padding: 2.5rem 2.5rem 2rem; margin-bottom: 2rem; position: relative; overflow: hidden;
}
.gb-cover::after {
  content: ''; position: absolute; bottom: -50px; right: -50px;
  width: 200px; height: 200px; border-radius: 50%;
  background: var(--gt); opacity: .18; pointer-events: none;
}
.gb-eye { font-family: 'DM Mono', monospace; font-size: .7rem; letter-spacing: .12em;
  text-transform: uppercase; color: #A8D8CC; margin-bottom: .6rem; }
.gb-cover h1 { font-family: 'DM Serif Display', Georgia, serif; font-size: 2rem; line-height: 1.2; margin-bottom: .5rem; }
.gb-cover p { font-size: .9rem; color: #C4E0DA; max-width: 500px; }
.gb-badge { display: inline-block; margin-top: 1.4rem; background: var(--gt); color: #fff;
  font-weight: 700; font-size: .75rem; padding: .3rem .85rem; border-radius: 999px; }
.gb-dl { display: flex; gap: .6rem; margin-top: 1.2rem; flex-wrap: wrap; }
.gb-dl a {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .45rem 1rem; border-radius: 8px; font-size: .8rem; font-weight: 700;
  text-decoration: none; transition: opacity .15s;
}
.gb-dl a:hover { opacity: .85; }
.gb-dl .btn-pdf { background: #fff; color: var(--gn); }
.gb-toc { background: #fff; border: 1px solid var(--gbr); border-left: 4px solid var(--gt);
  border-radius: 8px; padding: 1.2rem 1.5rem; margin-bottom: 1.8rem; }
.gb-toc h2 { font-size: .68rem; font-family: 'DM Mono', monospace; letter-spacing: .12em;
  text-transform: uppercase; color: var(--gmu); margin-bottom: .7rem; }
.gb-toc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .25rem .7rem; }
.gb-toc-grid a { color: var(--gn); text-decoration: none; font-weight: 600; font-size: .85rem;
  display: flex; align-items: center; gap: .35rem; }
.gb-toc-grid a:hover { color: var(--gt); }
.gb-toc-grid a span { font-family: 'DM Mono', monospace; font-size: .68rem; color: var(--gmu); width: 20px; flex-shrink: 0; }
.gb-sec { background: #fff; border: 1px solid var(--gbr); border-radius: 10px; margin-bottom: 1.6rem; overflow: hidden; }
.gb-sh { border-top: 4px solid var(--gt); padding: 1.2rem 1.5rem .8rem; display: flex; align-items: flex-start; gap: .9rem; }
.gb-sn { width: 34px; height: 34px; border-radius: 7px; background: var(--gn); color: #fff;
  font-family: 'DM Mono', monospace; font-size: .8rem; display: flex; align-items: center;
  justify-content: center; flex-shrink: 0; margin-top: .1rem; }
.gb-sh h2 { font-family: 'DM Serif Display', Georgia, serif; font-size: 1.25rem; color: var(--gn); line-height: 1.25; }
.gb-sh .sd { font-size: .8rem; color: var(--gmu); margin-top: .15rem; }
.gb-body { padding: 0 1.5rem 1.5rem; }
.gb-steps { display: flex; flex-direction: column; gap: .5rem; margin-top: .7rem; }
.gb-step { display: grid; grid-template-columns: 48px 1fr; gap: .4rem; }
.gb-step-n { width: 26px; height: 26px; border-radius: 50%; border: 2px solid var(--gt);
  color: var(--gt); font-family: 'DM Mono', monospace; font-size: .72rem;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .18rem; }
.gb-step-c { padding-top: .1rem; font-size: .86rem; }
.gb-step-c p { margin-bottom: .3rem; }
.gb-step-c p:last-child { margin-bottom: 0; }
.gb-sub { font-weight: 800; font-size: .85rem; color: var(--gn); margin: 1.1rem 0 .45rem;
  padding-bottom: .25rem; border-bottom: 1px dashed var(--gbr);
  display: flex; align-items: center; gap: .4rem; }
.gb-sub::before { content: ''; width: 5px; height: 5px; border-radius: 50%;
  background: var(--gt); display: inline-block; }
.callout { border-radius: 7px; padding: .7rem .9rem; font-size: .82rem;
  display: flex; gap: .55rem; margin-top: .8rem; line-height: 1.55; }
.callout-icon { flex-shrink: 0; margin-top: .05rem; }
.callout.tip  { background: var(--gtw); border-left: 3px solid var(--gt); }
.callout.warn { background: var(--gaw); border-left: 3px solid var(--ga); }
.callout.tip strong  { color: var(--gt); }
.callout.warn strong { color: var(--ga); }
kbd { font-family: 'DM Mono', monospace; font-size: .75rem; background: var(--gbg);
  border: 1px solid var(--gbr); border-radius: 4px; padding: .08rem .38rem; color: var(--gn); }
.btn-lbl { display: inline-block; background: var(--gt); color: #fff; font-size: .7rem;
  font-weight: 700; padding: .13rem .5rem; border-radius: 4px; }
.gt { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: .55rem; overflow-x: auto; }
.gt th { background: var(--gbg); font-weight: 800; font-size: .72rem; letter-spacing: .04em;
  text-transform: uppercase; color: var(--gmu); text-align: left; padding: .45rem .65rem;
  border-bottom: 2px solid var(--gbr); }
.gt td { padding: .45rem .65rem; border-bottom: 1px solid var(--gbr); vertical-align: top; }
.gt tr:last-child td { border-bottom: none; }
.gt td:first-child { font-weight: 700; color: var(--gn); }
.table-wrap { overflow-x: auto; }
.gb-footer { text-align: center; font-size: .78rem; color: var(--gmu); margin-top: 2.5rem;
  padding-top: 1.2rem; border-top: 1px solid var(--gbr); }
.gb-footer strong { color: var(--gt); }
</style>
@endpush

@section('content')
<div class="gb-wrap">

  {{-- Cover --}}
  <div class="gb-cover">
    <div class="gb-eye">Darasa Finance · Normal Accountant Edition</div>
    <h1>Finance System<br>User Guidebook</h1>
    <p>Step-by-step reference for everyday tasks — fee entry, receipts, expenses, reports, and more.</p>
    <span class="gb-badge">For Normal Accountants</span>
    <div class="gb-dl">
      <a href="{{ route('accountant.guidebook.pdf') }}" class="btn-pdf" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
        Download PDF
      </a>
    </div>
  </div>

  {{-- TOC --}}
  <div class="gb-toc">
    <h2>Contents</h2>
    <div class="gb-toc-grid">
      <a href="#s1"><span>01</span>Logging In</a>
      <a href="#s2"><span>02</span>Dashboard</a>
      <a href="#s3"><span>03</span>Fee Entry</a>
      <a href="#s4"><span>04</span>Student Ledger</a>
      <a href="#s5"><span>05</span>Overdue Payments</a>
      <a href="#s6"><span>06</span>Advance Payments</a>
      <a href="#s7"><span>07</span>Submit Expenses</a>
      <a href="#s8"><span>08</span>Inventory</a>
      <a href="#s9"><span>09</span>Send SMS</a>
      <a href="#s10"><span>10</span>Download Reports</a>
      <a href="#s11"><span>11</span>Tips &amp; Help</a>
    </div>
  </div>

  {{-- 01 Login --}}
  <div class="gb-sec" id="s1">
    <div class="gb-sh"><div class="gb-sn">01</div><div><h2>Logging In</h2><div class="sd">Access the system from any browser, any device.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Open your browser and go to <strong>{{ optional($settings)->school_name ? 'your school finance link' : 'https://finance.darasa360.co.tz' }}</strong>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Enter your <strong>email address</strong> and <strong>password</strong>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Log In</span>. You will be taken to the Dashboard.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div><strong>First login?</strong> Your administrator will give you your initial password. You can change it from your Profile page (click your name in the top-right corner).</div></div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div><strong>Forgot password?</strong> Contact your main accountant or system administrator — <strong>devs@olamtec.co.tz</strong> — to reset it.</div></div>
    </div>
  </div>

  {{-- 02 Dashboard --}}
  <div class="gb-sec" id="s2">
    <div class="gb-sh"><div class="gb-sn">02</div><div><h2>The Dashboard</h2><div class="sd">Summary view — what's collected, what's pending, recent activity.</div></div></div>
    <div class="gb-body">
      <div class="table-wrap"><table class="gt">
        <thead><tr><th>Card</th><th>What it shows</th></tr></thead>
        <tbody>
          <tr><td>Total Fees Expected</td><td>Sum of all fees assigned to students this year</td></tr>
          <tr><td>Total Collected</td><td>Payments received so far</td></tr>
          <tr><td>Outstanding Balance</td><td>Fees not yet paid</td></tr>
          <tr><td>Advance Balance</td><td>Money paid in excess, held for future fees</td></tr>
          <tr><td>Recent Vouchers</td><td>Last receipts and sales entries made</td></tr>
        </tbody>
      </table></div>
    </div>
  </div>

  {{-- 03 Fee Entry --}}
  <div class="gb-sec" id="s3">
    <div class="gb-sh"><div class="gb-sn">03</div><div><h2>Fee Entry</h2><div class="sd">Assign fees to students (Sales) and record payments received (Receipts).</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Part A — Assigning a fee (Sales entry)</div>
      <p style="font-size:.86rem">Use this when billing a student for a particular fee (e.g. School Fees, Transport, Uniform).</p>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>In the sidebar click <strong>Fee Entry</strong>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Search for the student by name or registration number, then click their name.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>In the <strong>Sales</strong> tab, select the fee <strong>Particular</strong>, enter the amount, choose the academic year, and click <span class="btn-lbl">Save Sales</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>The page asks if you want to add another particular for the same student — click <span class="btn-lbl">Add Another</span> or <span class="btn-lbl">Done</span>.</p></div></div>
      </div>
      <div class="gb-sub">Part B — Recording a payment (Receipt)</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Switch to the <strong>Receipt</strong> tab in Fee Entry.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Search for and select the student.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Select the <strong>Particular</strong>. The outstanding balance auto-fills.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>Enter the <strong>Amount Paid</strong>, select payment method, and optionally a reference number.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">5</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Save Receipt</span>. Note the voucher number for the physical receipt.</p></div></div>
      </div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div><strong>Double-check before saving.</strong> Once posted, only the main accountant can reverse a receipt. Confirm student name, particular, and amount first.</div></div>
    </div>
  </div>

  {{-- 04 Ledger --}}
  <div class="gb-sec" id="s4">
    <div class="gb-sh"><div class="gb-sn">04</div><div><h2>Student Ledger</h2><div class="sd">Full financial history for any student.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>Ledgers</strong> → <strong>Student Ledger</strong> tab.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Type the student's name or registration number and click <span class="btn-lbl">Search</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>The table shows every entry with dates, amounts, and running balance.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Download PDF</span> to generate a printable statement for the parent.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>A <strong>negative balance</strong> = advance (paid more than billed). A <strong>positive balance</strong> = still owes money.</div></div>
    </div>
  </div>

  {{-- 05 Overdue --}}
  <div class="gb-sec" id="s5">
    <div class="gb-sh"><div class="gb-sn">05</div><div><h2>Overdue Payments</h2><div class="sd">Students with outstanding balances past their deadline.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>Overdue Payments</strong> in the sidebar.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Filter by class using the dropdown if needed.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Download PDF</span> or <span class="btn-lbl">Download CSV</span> to export the list.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>Use the <strong>SMS</strong> module (Section 09) to send payment reminders directly from the overdue list.</div></div>
    </div>
  </div>

  {{-- 06 Advance --}}
  <div class="gb-sec" id="s6">
    <div class="gb-sh"><div class="gb-sn">06</div><div><h2>Advance Payments</h2><div class="sd">View credit balances and apply them to outstanding fees.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Viewing advances</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>Advance Payments</strong> in the sidebar.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>The table lists every student with an advance balance and the amount held.</p></div></div>
      </div>
      <div class="gb-sub">Assigning advance to a fee</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Assign to Fee</span> next to the student.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Select the <strong>Fee / Particular</strong>. Amount auto-fills to the lesser of advance or outstanding.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Adjust amount if needed, add a note, click <span class="btn-lbl">Apply Advance</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>When the advance balance reaches zero the student is removed from this page.</p></div></div>
      </div>
    </div>
  </div>

  {{-- 07 Expenses --}}
  <div class="gb-sec" id="s7">
    <div class="gb-sh"><div class="gb-sn">07</div><div><h2>Submit Expenses</h2><div class="sd">Record school expenditure and submit for approval.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>Expenses</strong> → <span class="btn-lbl">New Submission</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Select the <strong>Expense Category</strong>, enter the <strong>Transaction Date</strong>, and an optional description.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Add line items: click <span class="btn-lbl">+ Add Item</span>, enter name, quantity, unit price, and whether it is a <strong>Good</strong> or <strong>Service</strong>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>Review the total and click <span class="btn-lbl">Submit</span>. Status will show <strong>Pending</strong> until the main accountant approves.</p></div></div>
      </div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div><strong>Services vs Goods:</strong> Select <strong>Service</strong> for labour, cleaning, consultation. Select <strong>Good</strong> for physical items purchased.</div></div>
    </div>
  </div>

  {{-- 08 Inventory --}}
  <div class="gb-sec" id="s8">
    <div class="gb-sh"><div class="gb-sn">08</div><div><h2>Inventory</h2><div class="sd">Track school supplies and assets in stock.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Add an item</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>Inventory</strong> → <span class="btn-lbl">Add Item</span>. Enter the name, unit, and initial quantity.</p></div></div>
      </div>
      <div class="gb-sub">Record a movement</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Find the item and click <span class="btn-lbl">Movements</span> → <span class="btn-lbl">Record Movement</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Choose <strong>Stock In</strong> (received) or <strong>Stock Out</strong> (used/issued), enter quantity and date.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>The current balance updates automatically.</p></div></div>
      </div>
    </div>
  </div>

  {{-- 09 SMS --}}
  <div class="gb-sec" id="s9">
    <div class="gb-sh"><div class="gb-sn">09</div><div><h2>Send SMS</h2><div class="sd">Notify parents by text message.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Custom message</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Click <strong>SMS</strong>. Choose recipients: all parents, a class, or individual students.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Type your message and click <span class="btn-lbl">Send SMS</span>.</p></div></div>
      </div>
      <div class="gb-sub">Automated fee-balance reminder</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>In the SMS page, open the <strong>Fee Reminder</strong> tab. Choose a class (or all) and click <span class="btn-lbl">Send Reminders</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Each parent receives a personalised message with their child's outstanding balance.</p></div></div>
      </div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div>Each message uses SMS credits. Check the balance shown at the top of the SMS page before large batches.</div></div>
    </div>
  </div>

  {{-- 10 Reports --}}
  <div class="gb-sec" id="s10">
    <div class="gb-sh"><div class="gb-sn">10</div><div><h2>Download Reports</h2><div class="sd">Export data as PDF, CSV, or Excel.</div></div></div>
    <div class="gb-body">
      <div class="table-wrap"><table class="gt">
        <thead><tr><th>Report</th><th>Contents</th><th>Format</th></tr></thead>
        <tbody>
          <tr><td>Fee Collection</td><td>All students — expected vs collected per particular, balance, advance, grouped by class</td><td><kbd>PDF</kbd></td></tr>
          <tr><td>Overdue List</td><td>Students with unpaid fees past deadline</td><td><kbd>PDF</kbd> <kbd>CSV</kbd></td></tr>
          <tr><td>Advance Payments</td><td>Students with credit balance</td><td><kbd>PDF</kbd> <kbd>CSV</kbd></td></tr>
          <tr><td>Student Statement</td><td>Full ledger for one student</td><td><kbd>PDF</kbd></td></tr>
          <tr><td>Excel Report</td><td>Sheet 1: fee collection by class. Sheet 2: budget vs expenses + weekly breakdown</td><td><kbd>Excel</kbd></td></tr>
        </tbody>
      </table></div>
      <div class="gb-sub">Download the Excel report</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Go to <strong>Reports</strong> and click <span class="btn-lbl">Download Excel report</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Select the year and click <span class="btn-lbl">Download</span>. The file saves to your device automatically.</p></div></div>
      </div>
    </div>
  </div>

  {{-- 11 Tips --}}
  <div class="gb-sec" id="s11">
    <div class="gb-sh"><div class="gb-sn">11</div><div><h2>Tips &amp; Help</h2><div class="sd">Common questions and things to know.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Things only the main accountant can do</div>
      <div class="table-wrap"><table class="gt">
        <thead><tr><th>Action</th><th>Who does it</th></tr></thead>
        <tbody>
          <tr><td>Reverse or delete a voucher</td><td>Main accountant only</td></tr>
          <tr><td>Approve / deny expense submissions</td><td>Main accountant only</td></tr>
          <tr><td>Add or edit fee particulars</td><td>Main accountant only</td></tr>
          <tr><td>Manage students and classes</td><td>Main accountant only</td></tr>
          <tr><td>Payroll management</td><td>Main accountant only</td></tr>
          <tr><td>School settings and academic years</td><td>Main accountant only</td></tr>
          <tr><td>Reset another user's password</td><td>Main accountant / administrator</td></tr>
        </tbody>
      </table></div>
      <div class="callout tip" style="margin-top:.9rem"><span class="callout-icon">❓</span><div><strong>Entered the wrong amount on a receipt?</strong> Inform your main accountant immediately — they can reverse the transaction.</div></div>
      <div class="callout tip" style="margin-top:.55rem"><span class="callout-icon">❓</span><div><strong>Student not appearing in search?</strong> They must be registered by the main accountant before any transactions can be recorded.</div></div>
      <div class="callout tip" style="margin-top:.55rem"><span class="callout-icon">❓</span><div><strong>System shows an error or is slow?</strong> Refresh the page. If it continues, note the error message and contact the administrator — do <em>not</em> submit the same form twice.</div></div>
      <div class="callout warn" style="margin-top:.7rem"><span class="callout-icon">🔒</span><div><strong>Keep your login private.</strong> Every action is recorded against your account. Log out when leaving your workstation.</div></div>

      <div class="gb-sub" style="margin-top:1.3rem">Need help? Contact the administrator</div>
      <div class="callout tip"><span class="callout-icon">📧</span><div>
        Email: <strong>devs@olamtec.co.tz</strong><br>
        System: <strong>Darasa Finance by Olam Technologies</strong><br>
        Describe what page you were on, what you did, and what error message (if any) appeared.
      </div></div>
    </div>
  </div>

  <div class="gb-footer">
    <p>Darasa Finance · <strong>Normal Accountant Guidebook</strong></p>
    <p style="margin-top:.25rem">For support: <strong>devs@olamtec.co.tz</strong> &nbsp;|&nbsp; finance.darasa360.co.tz</p>
  </div>

</div>
@endsection
