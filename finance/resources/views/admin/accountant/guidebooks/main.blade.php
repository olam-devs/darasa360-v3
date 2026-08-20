@extends('layouts.accountant')

@section('title', 'Main Accountant Guidebook — Darasa Finance')
@section('page_title', 'Main Accountant Guidebook')

@push('head')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap">
<style>
:root {
  --gn: #14375A; --gt: #0F6B54; --gtw: #E2F0EC; --gaw: #FEF3C7; --ga: #D97706;
  --gred: #DC2626; --gredw: #FEE2E2;
  --gbg: #F3F5F8; --gmu: #667788; --gbr: #D4DCE4;
}
.gb-wrap { max-width: 880px; margin: 0 auto; font-family: 'Nunito', sans-serif; }
.gb-cover {
  background: linear-gradient(135deg, #14375A 60%, #0F6B54);
  color: #fff; border-radius: 12px;
  padding: 2.5rem 2.5rem 2rem; margin-bottom: 2rem; position: relative; overflow: hidden;
}
.gb-cover::before {
  content: ''; position: absolute; top: -40px; right: -40px;
  width: 180px; height: 180px; border-radius: 50%;
  background: rgba(255,255,255,.06); pointer-events: none;
}
.gb-eye { font-family: 'DM Mono', monospace; font-size: .7rem; letter-spacing: .12em;
  text-transform: uppercase; color: #A8CFC4; margin-bottom: .6rem; }
.gb-cover h1 { font-family: 'DM Serif Display', Georgia, serif; font-size: 2rem; line-height: 1.2; margin-bottom: .5rem; }
.gb-cover p { font-size: .88rem; color: #BDD5CD; max-width: 520px; }
.gb-badge { display: inline-block; margin-top: 1.2rem; background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3); color: #fff;
  font-weight: 700; font-size: .75rem; padding: .3rem .85rem; border-radius: 999px; }
.gb-dl { display: flex; gap: .6rem; margin-top: 1.1rem; flex-wrap: wrap; }
.gb-dl a { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem 1rem;
  border-radius: 8px; font-size: .8rem; font-weight: 700; text-decoration: none; transition: opacity .15s; }
.gb-dl a:hover { opacity: .85; }
.btn-pdf { background: #fff; color: var(--gn); }
/* TOC */
.gb-toc { background: #fff; border: 1px solid var(--gbr); border-left: 4px solid var(--gt);
  border-radius: 8px; padding: 1.2rem 1.5rem; margin-bottom: 1.8rem; }
.gb-toc h2 { font-size: .68rem; font-family: 'DM Mono', monospace; letter-spacing: .12em;
  text-transform: uppercase; color: var(--gmu); margin-bottom: .7rem; }
.gb-toc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .25rem .7rem; }
.gb-toc-grid a { color: var(--gn); text-decoration: none; font-weight: 600; font-size: .84rem;
  display: flex; align-items: center; gap: .35rem; }
.gb-toc-grid a:hover { color: var(--gt); }
.gb-toc-grid a span { font-family: 'DM Mono', monospace; font-size: .67rem; color: var(--gmu); width: 20px; flex-shrink: 0; }
/* Section */
.gb-sec { background: #fff; border: 1px solid var(--gbr); border-radius: 10px; margin-bottom: 1.6rem; overflow: hidden; }
.gb-sh { border-top: 4px solid var(--gt); padding: 1.2rem 1.5rem .8rem; display: flex; align-items: flex-start; gap: .9rem; }
.gb-sh.red-top { border-top-color: var(--gred); }
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
.callout.danger { background: var(--gredw); border-left: 3px solid var(--gred); }
.callout.tip strong    { color: var(--gt); }
.callout.warn strong   { color: var(--ga); }
.callout.danger strong { color: var(--gred); }
kbd { font-family: 'DM Mono', monospace; font-size: .75rem; background: var(--gbg);
  border: 1px solid var(--gbr); border-radius: 4px; padding: .08rem .38rem; color: var(--gn); }
.btn-lbl { display: inline-block; background: var(--gt); color: #fff; font-size: .7rem;
  font-weight: 700; padding: .13rem .5rem; border-radius: 4px; }
.gt { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: .55rem; }
.gt th { background: var(--gbg); font-weight: 800; font-size: .72rem; letter-spacing: .04em;
  text-transform: uppercase; color: var(--gmu); text-align: left; padding: .45rem .65rem;
  border-bottom: 2px solid var(--gbr); }
.gt td { padding: .45rem .65rem; border-bottom: 1px solid var(--gbr); vertical-align: top; }
.gt tr:last-child td { border-bottom: none; }
.gt td:first-child { font-weight: 700; color: var(--gn); }
.table-wrap { overflow-x: auto; }
.pill { display: inline-block; font-size: .68rem; font-weight: 700; padding: .1rem .45rem;
  border-radius: 999px; vertical-align: middle; }
.pill.main-only { background: #dbeafe; color: #1e40af; }
.pill.both      { background: var(--gtw); color: var(--gt); }
.gb-footer { text-align: center; font-size: .78rem; color: var(--gmu); margin-top: 2.5rem;
  padding-top: 1.2rem; border-top: 1px solid var(--gbr); }
.gb-footer strong { color: var(--gt); }
</style>
@endpush

@section('content')
<div class="gb-wrap">

  {{-- Cover --}}
  <div class="gb-cover">
    <div class="gb-eye">Darasa Finance · Main Accountant Edition</div>
    <h1>Finance System<br>Main Accountant Guide</h1>
    <p>Complete reference covering all features — student management, expense approvals, payroll, reconciliation, settings, and team management.</p>
    <span class="gb-badge">For Main Accountants Only</span>
    <div class="gb-dl">
      <a href="{{ route('accountant.guidebook.main.pdf') }}" class="btn-pdf" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
        Download PDF
      </a>
    </div>
  </div>

  {{-- TOC --}}
  <div class="gb-toc">
    <h2>Contents</h2>
    <div class="gb-toc-grid">
      <a href="#m1"><span>01</span>Your Role &amp; Responsibilities</a>
      <a href="#m2"><span>02</span>Student Management</a>
      <a href="#m3"><span>03</span>Fee Particulars</a>
      <a href="#m4"><span>04</span>Fee Entry &amp; Receipts</a>
      <a href="#m5"><span>05</span>Expense Approvals</a>
      <a href="#m6"><span>06</span>Payroll</a>
      <a href="#m7"><span>07</span>Books &amp; Accounting</a>
      <a href="#m8"><span>08</span>Reconciliation</a>
      <a href="#m9"><span>09</span>School Settings</a>
      <a href="#m10"><span>10</span>Team Permissions</a>
      <a href="#m11"><span>11</span>Reports &amp; Excel Export</a>
      <a href="#m12"><span>12</span>Activity Logs</a>
      <a href="#m13"><span>13</span>Common Issues &amp; Help</a>
    </div>
  </div>

  {{-- 01 Role --}}
  <div class="gb-sec" id="m1">
    <div class="gb-sh"><div class="gb-sn">01</div><div><h2>Your Role &amp; Responsibilities</h2><div class="sd">What the main accountant controls that normal accountants cannot.</div></div></div>
    <div class="gb-body">
      <p style="font-size:.87rem;margin-bottom:.7rem">As main accountant you are the financial gatekeeper of the school. All transactions, approvals, and configuration go through you. Normal accountants can record data — you approve, reverse, configure, and manage.</p>
      <div class="table-wrap"><table class="gt">
        <thead><tr><th>Feature</th><th>Normal Accountant</th><th>Main Accountant</th></tr></thead>
        <tbody>
          <tr><td>Fee entry &amp; receipts</td><td>✔</td><td>✔</td></tr>
          <tr><td>View ledgers &amp; reports</td><td>✔</td><td>✔</td></tr>
          <tr><td>Submit expenses</td><td>✔</td><td>✔</td></tr>
          <tr><td>Send SMS</td><td>✔</td><td>✔</td></tr>
          <tr><td><strong>Approve/deny expenses</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Reverse vouchers</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Add/edit particulars</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Add/edit students &amp; classes</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Payroll</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Reconciliation</strong></td><td>✘ (unless granted)</td><td>✔</td></tr>
          <tr><td><strong>School settings</strong></td><td>✘</td><td>✔</td></tr>
          <tr><td><strong>Team permissions</strong></td><td>✘</td><td>✔</td></tr>
        </tbody>
      </table></div>
    </div>
  </div>

  {{-- 02 Students --}}
  <div class="gb-sec" id="m2">
    <div class="gb-sh"><div class="gb-sn">02</div><div><h2>Student Management</h2><div class="sd">Register, edit, and promote students.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Add a new student</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Student Management</strong> → <span class="btn-lbl">Add Student</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Fill in: full name, registration number, gender, date of birth, admission date, class, and parent contacts.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Save</span>. The student now appears in fee entry and reports.</p></div></div>
      </div>
      <div class="gb-sub">Edit or deactivate a student</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Find the student in the list and click <span class="btn-lbl">Edit</span>. Change the required fields and save.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>To deactivate (e.g. student has left), change their status to <strong>Inactive</strong>. They will no longer appear in active fee lists.</p></div></div>
      </div>
      <div class="gb-sub">Promote students to next class</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Student Promotion</strong>. Select the source class and destination class.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Review the list and click <span class="btn-lbl">Promote Selected</span>. All selected students move to the new class.</p></div></div>
      </div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div><strong>Promotion is not reversible</strong> in bulk — move students individually if only a few need to change class.</div></div>
    </div>
  </div>

  {{-- 03 Particulars --}}
  <div class="gb-sec" id="m3">
    <div class="gb-sh"><div class="gb-sn">03</div><div><h2>Fee Particulars</h2><div class="sd">Define the fee types available for billing.</div></div></div>
    <div class="gb-body">
      <p style="font-size:.86rem;margin-bottom:.6rem">Particulars are the fee categories (e.g. School Fees, Transport, Uniform). Normal accountants select these when entering fees — you control what exists.</p>
      <div class="gb-sub">Add a particular</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Books &amp; Accounting</strong> → <strong>Particulars</strong> → <span class="btn-lbl">Add Particular</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Enter name, optionally link it to a book (account), and set any default amount.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Save. The particular is immediately available in fee entry.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>Keep particular names short and clear — accountants select from them on every fee entry. Avoid duplicates.</div></div>
    </div>
  </div>

  {{-- 04 Fee Entry --}}
  <div class="gb-sec" id="m4">
    <div class="gb-sh"><div class="gb-sn">04</div><div><h2>Fee Entry &amp; Reversals</h2><div class="sd">Post fees and receipts — and reverse mistakes.</div></div></div>
    <div class="gb-body">
      <p style="font-size:.86rem;margin-bottom:.5rem">The fee entry process is the same as for normal accountants (Sales → assign fee; Receipt → record payment). As main accountant you also have the power to reverse entries.</p>
      <div class="gb-sub">Reverse a receipt or sales entry</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Find the voucher via the <strong>Student Ledger</strong> or <strong>Particular Ledger</strong>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Click the voucher to expand it, then click <span class="btn-lbl">Reverse</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Enter the reason for the reversal and confirm. A counter-entry is automatically created.</p></div></div>
      </div>
      <div class="callout danger"><span class="callout-icon">⚠</span><div><strong>Reversals are permanent.</strong> The original voucher and the reversal both remain in the ledger for audit purposes. Never reverse a correct entry — post a correcting entry instead.</div></div>
    </div>
  </div>

  {{-- 05 Expense Approvals --}}
  <div class="gb-sec" id="m5">
    <div class="gb-sh"><div class="gb-sn">05</div><div><h2>Expense Approvals</h2><div class="sd">Review and approve or deny submissions from normal accountants.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Expenses</strong>. The list shows all submissions. Filter by <strong>Pending</strong> to see items awaiting action.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Click a submission to open it. Review the category, date, items, and total.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Approve</span> to accept and post the expense, or <span class="btn-lbl">Deny</span> to reject it (add a reason — the submitter will see it).</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>Approved expenses are posted to the expense book and deducted from the budget balance.</p></div></div>
      </div>
      <div class="gb-sub">Expense categories and budgets</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>To add or edit categories: Sidebar → <strong>Expenses</strong> → <strong>Categories</strong> tab. Set the category name and optionally a budget plan (expected amount and date range).</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Budget plans appear in the Excel report's Sheet 2 for budget vs actual comparison.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>Approve expenses promptly — pending submissions block the submitter from seeing their status and may delay school operations.</div></div>
    </div>
  </div>

  {{-- 06 Payroll --}}
  <div class="gb-sec" id="m6">
    <div class="gb-sh"><div class="gb-sn">06</div><div><h2>Payroll</h2><div class="sd">Manage staff salaries and process monthly payroll.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Add a staff member</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Payroll</strong> → <span class="btn-lbl">Add Staff</span>. Enter name, role, salary, and bank/phone details.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Save. The staff member appears in the payroll list.</p></div></div>
      </div>
      <div class="gb-sub">Process monthly payroll</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>In the Payroll page, select the month and click <span class="btn-lbl">Generate Payroll</span>.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Review the salary entries. Adjust for absences, bonuses, or deductions if needed.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Approve Payroll</span> to finalise and post the salary expense to the accounting books.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>Download the payroll summary as PDF for records.</p></div></div>
      </div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div>Once approved, payroll entries are posted to the books. Contact the administrator (devs@olamtec.co.tz) if a correction is needed after approval.</div></div>
    </div>
  </div>

  {{-- 07 Books --}}
  <div class="gb-sec" id="m7">
    <div class="gb-sh"><div class="gb-sn">07</div><div><h2>Books &amp; Accounting</h2><div class="sd">Manage the chart of accounts underlying all transactions.</div></div></div>
    <div class="gb-body">
      <p style="font-size:.86rem;margin-bottom:.6rem">Every transaction (fee sales, receipt, expense) is posted to a "book" (account). The Books module lets you view these accounts and their running balances.</p>
      <div class="gb-sub">View book balances</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Books &amp; Accounting</strong> → <strong>Books Management</strong>. See each account's name, type, and current balance.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Click a book to see its transaction history.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>If a balance seems wrong, use the Reconciliation module (Section 08) to identify and correct any mismatches.</div></div>
    </div>
  </div>

  {{-- 08 Reconciliation --}}
  <div class="gb-sec" id="m8">
    <div class="gb-sh"><div class="gb-sn">08</div><div><h2>Reconciliation</h2><div class="sd">Match system records against real bank or cash statements.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Reconciliation</strong>. Select the book (account) and date range.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>The system lists all posted transactions. Check them against your physical bank statement or cash count.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Mark transactions as reconciled. The running difference shows how much remains unmatched.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">4</div><div class="gb-step-c"><p>When the difference reaches zero, reconciliation is complete for that period.</p></div></div>
      </div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>You can grant normal accountants access to reconciliation via <strong>Team Permissions</strong> (Section 10) if needed.</div></div>
    </div>
  </div>

  {{-- 09 Settings --}}
  <div class="gb-sec" id="m9">
    <div class="gb-sh"><div class="gb-sn">09</div><div><h2>School Settings</h2><div class="sd">Configure school information, academic years, and system options.</div></div></div>
    <div class="gb-body">
      <div class="gb-sub">Basic school info</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Settings</strong>. Update the school name, logo, address, and contact details.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Save Settings</span>. The school name appears in reports and the PDF header.</p></div></div>
      </div>
      <div class="gb-sub">Academic years</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>In Settings, scroll to <strong>Academic Years</strong>. Add a new year with its start and end date.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Set the active academic year — fee entries default to this year.</p></div></div>
      </div>
      <div class="gb-sub">Classes</div>
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Students</strong> → <strong>Class Management</strong>. Add or rename classes (e.g. Standard 1, Form 2).</p></div></div>
      </div>
    </div>
  </div>

  {{-- 10 Team --}}
  <div class="gb-sec" id="m10">
    <div class="gb-sh"><div class="gb-sn">10</div><div><h2>Team Permissions</h2><div class="sd">Control what each normal accountant can see and do.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Team Permissions</strong>. The page lists all accountants at your school.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Click <span class="btn-lbl">Edit Permissions</span> next to an accountant to open their settings.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>Toggle the available permission flags and click <span class="btn-lbl">Save</span>.</p></div></div>
      </div>
      <div class="table-wrap"><table class="gt" style="margin-top:.7rem">
        <thead><tr><th>Permission</th><th>What it unlocks</th></tr></thead>
        <tbody>
          <tr><td>Can Edit History</td><td>Access to the Reconciliation module</td></tr>
          <tr><td>Can View Logs</td><td>Access to Activity Logs</td></tr>
        </tbody>
      </table></div>
      <div class="callout warn"><span class="callout-icon">⚠</span><div>The system administrator (devs@olamtec.co.tz) manages adding new accountants and setting initial passwords — contact them to onboard a new team member.</div></div>
    </div>
  </div>

  {{-- 11 Reports --}}
  <div class="gb-sec" id="m11">
    <div class="gb-sh"><div class="gb-sn">11</div><div><h2>Reports &amp; Excel Export</h2><div class="sd">All reports available to you.</div></div></div>
    <div class="gb-body">
      <div class="table-wrap"><table class="gt">
        <thead><tr><th>Report</th><th>Contents</th><th>Format</th></tr></thead>
        <tbody>
          <tr><td>Fee Collection</td><td>Expected vs collected per particular, balance, advance — grouped by class</td><td><kbd>PDF</kbd></td></tr>
          <tr><td>Overdue List</td><td>Students with unpaid fees past deadline</td><td><kbd>PDF</kbd> <kbd>CSV</kbd></td></tr>
          <tr><td>Advance Payments</td><td>Students with credit balance</td><td><kbd>PDF</kbd> <kbd>CSV</kbd></td></tr>
          <tr><td>Student Statement</td><td>Full ledger for one student (print for parent)</td><td><kbd>PDF</kbd></td></tr>
          <tr><td>Income Statement</td><td>Revenue vs expenses summary</td><td><kbd>JSON/View</kbd></td></tr>
          <tr><td>Balance Sheet</td><td>Assets and liabilities</td><td><kbd>JSON/View</kbd></td></tr>
          <tr><td>Trial Balance</td><td>All account balances</td><td><kbd>JSON/View</kbd></td></tr>
          <tr><td>Excel Report</td><td>Sheet 1: full fee collection by class. Sheet 2: budget vs actual + weekly expense breakdown</td><td><kbd>Excel</kbd></td></tr>
        </tbody>
      </table></div>
      <div class="callout tip"><span class="callout-icon">💡</span><div>The <strong>Excel Report</strong> is the most complete export. Download it from <strong>Reports → Download Excel report</strong>, select the year, and click Download.</div></div>
    </div>
  </div>

  {{-- 12 Logs --}}
  <div class="gb-sec" id="m12">
    <div class="gb-sh"><div class="gb-sn">12</div><div><h2>Activity Logs</h2><div class="sd">Audit trail of all actions taken in the system.</div></div></div>
    <div class="gb-body">
      <div class="gb-steps">
        <div class="gb-step"><div class="gb-step-n">1</div><div class="gb-step-c"><p>Sidebar → <strong>Activity Logs</strong>. Every action (login, voucher creation, approval, setting change) is recorded with time and user.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">2</div><div class="gb-step-c"><p>Filter by date range or user to investigate a specific event.</p></div></div>
        <div class="gb-step"><div class="gb-step-n">3</div><div class="gb-step-c"><p>If you see an unauthorised action, report it to the administrator immediately.</p></div></div>
      </div>
    </div>
  </div>

  {{-- 13 Help --}}
  <div class="gb-sec" id="m13">
    <div class="gb-sh red-top"><div class="gb-sn">13</div><div><h2>Common Issues &amp; Help</h2><div class="sd">What to do when things go wrong — and who to call.</div></div></div>
    <div class="gb-body">
      <div class="callout tip"><span class="callout-icon">❓</span><div><strong>A normal accountant posted the wrong voucher amount.</strong><br>Go to the student's ledger, find the voucher, click Reverse. Then post the correct entry. Explain the reason in the reversal note for audit clarity.</div></div>
      <div class="callout tip" style="margin-top:.6rem"><span class="callout-icon">❓</span><div><strong>An expense was approved by mistake.</strong><br>Contact the administrator (devs@olamtec.co.tz) — approved expense reversals require developer-level access.</div></div>
      <div class="callout tip" style="margin-top:.6rem"><span class="callout-icon">❓</span><div><strong>A student's balance is wrong.</strong><br>Check the student ledger for duplicate or incorrect vouchers. Reverse the incorrect ones and re-post correctly. If the balance still appears wrong after corrections, check whether advance balance is involved.</div></div>
      <div class="callout tip" style="margin-top:.6rem"><span class="callout-icon">❓</span><div><strong>Excel report shows wrong budget amounts.</strong><br>Budget figures come from Expense Category Plans. Check that plans have been set up for the current year under Expenses → Categories → Budgets.</div></div>
      <div class="callout tip" style="margin-top:.6rem"><span class="callout-icon">❓</span><div><strong>Need to add a new accountant to the system.</strong><br>Contact the administrator — only they can create new user accounts.</div></div>

      <div class="gb-sub" style="margin-top:1.2rem">Contact the administrator</div>
      <div class="callout tip"><span class="callout-icon">📧</span><div>
        <strong>Email:</strong> devs@olamtec.co.tz<br>
        <strong>System:</strong> Darasa Finance by Olam Technologies<br>
        When contacting: describe what page you were on, what you were trying to do, and the exact error message or unexpected result you saw.
      </div></div>

      <div class="callout danger" style="margin-top:.6rem"><span class="callout-icon">🔒</span><div><strong>Your account has elevated access.</strong> Never share your password. Log out whenever you leave your workstation. If you suspect your account has been compromised, inform the administrator immediately.</div></div>
    </div>
  </div>

  <div class="gb-footer">
    <p>Darasa Finance · <strong>Main Accountant Guidebook</strong></p>
    <p style="margin-top:.25rem">For support: <strong>devs@olamtec.co.tz</strong> &nbsp;|&nbsp; finance.darasa360.co.tz &nbsp;|&nbsp; Olam Technologies</p>
  </div>

</div>
@endsection
