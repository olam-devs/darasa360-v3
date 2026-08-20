<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5pt; color: #1a2030; background: #fff; line-height: 1.55; }
.page { padding: 22mm 18mm; }
.cover { background: #14375A; color: #fff; border-radius: 8pt; padding: 22pt 24pt 18pt; margin-bottom: 18pt; }
.cover-eye { font-size: 6.5pt; letter-spacing: 2pt; text-transform: uppercase; color: #A8CFC4; margin-bottom: 6pt; }
.cover h1 { font-size: 20pt; line-height: 1.2; margin-bottom: 5pt; }
.cover p { font-size: 8.5pt; color: #BDD5CD; }
.cover-badge { display: inline-block; margin-top: 10pt; background: #0F6B54; color: #fff;
  font-weight: bold; font-size: 7pt; padding: 3pt 8pt; border-radius: 99pt; }
.toc { border: 1pt solid #D4DCE4; border-left: 3pt solid #0F6B54; border-radius: 5pt;
  padding: 10pt 12pt; margin-bottom: 14pt; }
.toc-title { font-size: 6.5pt; letter-spacing: 1.5pt; text-transform: uppercase; color: #667788; margin-bottom: 7pt; }
.toc-row { display: table; width: 100%; }
.toc-cell { display: table-cell; width: 50%; font-size: 8.5pt; padding: 1.5pt 0; }
.toc-n { color: #667788; font-size: 7pt; margin-right: 4pt; }
.sec { border: 1pt solid #D4DCE4; border-top: 3pt solid #0F6B54; border-radius: 5pt;
  margin-bottom: 13pt; padding: 11pt 13pt; page-break-inside: avoid; }
.sec-hdr { display: flex; align-items: flex-start; gap: 8pt; margin-bottom: 8pt; }
.sec-num { width: 22pt; height: 22pt; border-radius: 4pt; background: #14375A; color: #fff;
  font-size: 7.5pt; font-weight: bold; text-align: center; line-height: 22pt; flex-shrink: 0; }
.sec-hdr h2 { font-size: 12pt; color: #14375A; font-weight: bold; margin: 0; line-height: 1.2; }
.sec-hdr .sd { font-size: 7.5pt; color: #667788; margin-top: 1pt; }
.step { display: table; width: 100%; margin-bottom: 4pt; }
.step-n { display: table-cell; width: 22pt; }
.step-circle { width: 16pt; height: 16pt; border-radius: 8pt; border: 1.5pt solid #0F6B54;
  color: #0F6B54; font-size: 7pt; font-weight: bold; text-align: center; line-height: 15pt; }
.step-c { display: table-cell; vertical-align: top; font-size: 8.5pt; padding-top: 1pt; }
.sub { font-weight: bold; font-size: 8.5pt; color: #14375A; margin: 9pt 0 4pt;
  padding-bottom: 2pt; border-bottom: 1pt dashed #D4DCE4; }
.callout { border-radius: 4pt; padding: 6pt 8pt; font-size: 8pt; margin-top: 7pt; border-left: 2.5pt solid #0F6B54; background: #E2F0EC; }
.callout.warn  { background: #FEF3C7; border-color: #D97706; }
.callout.danger { background: #FEE2E2; border-color: #DC2626; }
.callout strong { color: #0F6B54; }
.callout.warn strong { color: #D97706; }
.callout.danger strong { color: #DC2626; }
table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 5pt; }
th { background: #F3F5F8; font-size: 7pt; letter-spacing: 1pt; text-transform: uppercase;
  color: #667788; text-align: left; padding: 4pt 6pt; border-bottom: 1.5pt solid #D4DCE4; }
td { padding: 4pt 6pt; border-bottom: 1pt solid #D4DCE4; vertical-align: top; }
tr:last-child td { border-bottom: none; }
td:first-child { font-weight: bold; color: #14375A; }
.btn { display: inline-block; background: #0F6B54; color: #fff; font-size: 7pt; font-weight: bold; padding: 1.5pt 5pt; border-radius: 3pt; }
.footer { text-align: center; font-size: 7.5pt; color: #667788; margin-top: 20pt; padding-top: 8pt; border-top: 1pt solid #D4DCE4; }
.footer strong { color: #0F6B54; }
</style>
</head>
<body>
<div class="page">

<div class="cover">
  <div class="cover-eye">Darasa Finance · Main Accountant Edition</div>
  <h1>Finance System<br>Main Accountant Guide</h1>
  <p>Complete reference — student management, expense approvals, payroll, reconciliation, settings, and team management.</p>
  <span class="cover-badge">For Main Accountants Only</span>
</div>

<div class="toc">
  <div class="toc-title">Contents</div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">01</span> Your Role &amp; Responsibilities</div><div class="toc-cell"><span class="toc-n">02</span> Student Management</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">03</span> Fee Particulars</div><div class="toc-cell"><span class="toc-n">04</span> Fee Entry &amp; Reversals</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">05</span> Expense Approvals</div><div class="toc-cell"><span class="toc-n">06</span> Payroll</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">07</span> Books &amp; Accounting</div><div class="toc-cell"><span class="toc-n">08</span> Reconciliation</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">09</span> School Settings</div><div class="toc-cell"><span class="toc-n">10</span> Team Permissions</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">11</span> Reports &amp; Excel Export</div><div class="toc-cell"><span class="toc-n">12</span> Activity Logs</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">13</span> Common Issues &amp; Help</div><div class="toc-cell"></div></div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">01</div><div><h2>Your Role &amp; Responsibilities</h2><div class="sd">What you can do that normal accountants cannot.</div></div></div>
  <table>
    <thead><tr><th>Feature</th><th>Normal</th><th>Main</th></tr></thead>
    <tbody>
      <tr><td>Fee entry &amp; receipts</td><td>Yes</td><td>Yes</td></tr>
      <tr><td>Approve/deny expenses</td><td>No</td><td>Yes</td></tr>
      <tr><td>Reverse vouchers</td><td>No</td><td>Yes</td></tr>
      <tr><td>Add/edit particulars</td><td>No</td><td>Yes</td></tr>
      <tr><td>Add/edit students</td><td>No</td><td>Yes</td></tr>
      <tr><td>Payroll</td><td>No</td><td>Yes</td></tr>
      <tr><td>School settings</td><td>No</td><td>Yes</td></tr>
      <tr><td>Team permissions</td><td>No</td><td>Yes</td></tr>
    </tbody>
  </table>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">02</div><div><h2>Student Management</h2><div class="sd">Register, edit, and promote students.</div></div></div>
  <div class="sub">Add a student</div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Student Management → <span class="btn">Add Student</span>. Fill in name, registration number, class, parent contacts, and save.</div></div>
  <div class="sub">Promote students</div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Student Promotion. Select source and destination class, then click <span class="btn">Promote Selected</span>.</div></div>
  <div class="callout warn"><strong>Promotion is not reversible in bulk.</strong> Move students individually if only a few need to change.</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">03</div><div><h2>Fee Particulars</h2><div class="sd">Define fee types for billing.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Books &amp; Accounting → Particulars → <span class="btn">Add Particular</span>. Enter name and save.</div></div>
  <div class="callout"><strong>Tip:</strong> Keep names short and unique — accountants select from them on every fee entry.</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">04</div><div><h2>Fee Entry &amp; Reversals</h2><div class="sd">Post fees/receipts and reverse errors.</div></div></div>
  <div class="sub">Reverse a voucher</div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Find voucher via Student Ledger or Particular Ledger. Click the voucher, then click <span class="btn">Reverse</span>.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Enter the reversal reason and confirm. A counter-entry is automatically created.</div></div>
  <div class="callout danger"><strong>Reversals are permanent</strong> and remain visible in the ledger for audit. Never reverse a correct entry.</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">05</div><div><h2>Expense Approvals</h2><div class="sd">Review and approve or deny submissions.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Expenses. Filter by Pending. Click a submission to review items and total.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Click <span class="btn">Approve</span> to post, or <span class="btn">Deny</span> and add a reason the submitter will see.</div></div>
  <div class="callout"><strong>Budget plans:</strong> Sidebar → Expenses → Categories. Set expected amount and date range for each category.</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">06</div><div><h2>Payroll</h2><div class="sd">Manage staff salaries.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Add staff: Sidebar → Payroll → <span class="btn">Add Staff</span>. Enter name, role, and salary.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Monthly: select month → <span class="btn">Generate Payroll</span> → review → <span class="btn">Approve Payroll</span>.</div></div>
  <div class="callout warn"><strong>After approval payroll is posted to the books.</strong> Contact devs@olamtec.co.tz for corrections.</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">07</div><div><h2>Books &amp; Accounting</h2></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Books Management. View each account's balance and transaction history.</div></div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">08</div><div><h2>Reconciliation</h2><div class="sd">Match records to bank/cash statements.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Reconciliation. Select book and date range. Mark transactions as reconciled.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">When the difference = 0 reconciliation is complete for that period.</div></div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">09</div><div><h2>School Settings</h2></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Settings. Update school name, logo, and contact details. Click <span class="btn">Save Settings</span>.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Add academic years in Settings → Academic Years. Set the active year for fee defaults.</div></div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">10</div><div><h2>Team Permissions</h2></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Team Permissions. Click <span class="btn">Edit Permissions</span> next to an accountant.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Toggle permissions (Can Edit History → Reconciliation; Can View Logs → Activity Logs) and save.</div></div>
  <div class="callout warn"><strong>Adding new accountants</strong> requires the administrator (devs@olamtec.co.tz).</div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">11</div><div><h2>Reports &amp; Excel Export</h2></div></div>
  <table>
    <thead><tr><th>Report</th><th>Format</th></tr></thead>
    <tbody>
      <tr><td>Fee Collection</td><td>PDF</td></tr>
      <tr><td>Overdue / Advance Payments</td><td>PDF / CSV</td></tr>
      <tr><td>Student Statement</td><td>PDF</td></tr>
      <tr><td>Income Statement / Balance Sheet / Trial Balance</td><td>View</td></tr>
      <tr><td>Excel Report (Sheet 1: fee collection, Sheet 2: budget + weekly expenses)</td><td>Excel</td></tr>
    </tbody>
  </table>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">12</div><div><h2>Activity Logs</h2></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → Activity Logs. Filter by user or date. Every action is recorded.</div></div>
</div>

<div class="sec">
  <div class="sec-hdr"><div class="sec-num">13</div><div><h2>Common Issues &amp; Help</h2></div></div>
  <div class="callout"><strong>Wrong amount posted:</strong> Reverse the voucher (Section 04), then post the correct entry.</div>
  <div class="callout" style="margin-top:5pt"><strong>Expense approved by mistake:</strong> Contact devs@olamtec.co.tz — developer access required.</div>
  <div class="callout" style="margin-top:5pt"><strong>Excel budget wrong:</strong> Check Expenses → Categories — budget plans must be set for the current year.</div>
  <div class="sub">Contact the administrator</div>
  <div class="callout">
    <strong>Email:</strong> devs@olamtec.co.tz<br>
    <strong>System:</strong> Darasa Finance by Olam Technologies<br>
    Include: page, action, and the exact error message or unexpected result.
  </div>
  <div class="callout danger" style="margin-top:6pt"><strong>Your account has elevated access.</strong> Never share your password. Log out when leaving your workstation. Report any suspected compromise to the administrator immediately.</div>
</div>

<div class="footer">
  <p>Darasa Finance · <strong>Main Accountant Guidebook</strong></p>
  <p>Support: devs@olamtec.co.tz &nbsp;|&nbsp; finance.darasa360.co.tz &nbsp;|&nbsp; Olam Technologies</p>
</div>

</div>
</body>
</html>
