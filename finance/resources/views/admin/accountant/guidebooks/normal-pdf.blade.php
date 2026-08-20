<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5pt; color: #1a2030; background: #fff; line-height: 1.55; }
.page { padding: 22mm 18mm; }
/* Cover */
.cover { background: #0D3B57; color: #fff; border-radius: 8pt; padding: 22pt 24pt 18pt; margin-bottom: 18pt; }
.cover-eye { font-size: 6.5pt; letter-spacing: 2pt; text-transform: uppercase; color: #A8D8CC; margin-bottom: 6pt; }
.cover h1 { font-size: 20pt; line-height: 1.2; margin-bottom: 5pt; }
.cover p { font-size: 8.5pt; color: #C4E0DA; }
.cover-badge { display: inline-block; margin-top: 11pt; background: #17876D; color: #fff;
  font-weight: bold; font-size: 7pt; padding: 3pt 8pt; border-radius: 99pt; }
/* TOC */
.toc { border: 1pt solid #D8E0DA; border-left: 3pt solid #17876D; border-radius: 5pt;
  padding: 10pt 12pt; margin-bottom: 14pt; }
.toc-title { font-size: 6.5pt; letter-spacing: 1.5pt; text-transform: uppercase; color: #6C7A8E; margin-bottom: 7pt; }
.toc-row { display: table; width: 100%; }
.toc-cell { display: table-cell; width: 50%; font-size: 8.5pt; padding: 1.5pt 0; }
.toc-cell a { color: #0D3B57; text-decoration: none; font-weight: bold; }
.toc-n { color: #6C7A8E; font-size: 7pt; margin-right: 4pt; }
/* Section */
.sec { border: 1pt solid #D8E0DA; border-top: 3pt solid #17876D; border-radius: 5pt;
  margin-bottom: 14pt; padding: 12pt 14pt; page-break-inside: avoid; }
.sec-hdr { display: flex; align-items: flex-start; gap: 8pt; margin-bottom: 8pt; }
.sec-num { width: 22pt; height: 22pt; border-radius: 4pt; background: #0D3B57; color: #fff;
  font-size: 7.5pt; font-weight: bold; text-align: center; line-height: 22pt; flex-shrink: 0; }
.sec-hdr h2 { font-size: 12pt; color: #0D3B57; font-weight: bold; margin: 0; line-height: 1.2; }
.sec-hdr .sd { font-size: 7.5pt; color: #6C7A8E; margin-top: 1pt; }
/* Steps */
.step { display: table; width: 100%; margin-bottom: 4pt; }
.step-n { display: table-cell; width: 22pt; }
.step-circle { width: 16pt; height: 16pt; border-radius: 8pt; border: 1.5pt solid #17876D;
  color: #17876D; font-size: 7pt; font-weight: bold; text-align: center; line-height: 15pt; }
.step-c { display: table-cell; vertical-align: top; font-size: 8.5pt; padding-top: 1pt; }
/* Sub title */
.sub { font-weight: bold; font-size: 8.5pt; color: #0D3B57; margin: 9pt 0 4pt;
  padding-bottom: 2pt; border-bottom: 1pt dashed #D8E0DA; }
/* Callouts */
.callout { border-radius: 4pt; padding: 6pt 8pt; font-size: 8pt; margin-top: 7pt; border-left: 2.5pt solid #17876D; background: #E6F4F1; }
.callout.warn { background: #FEF3C7; border-color: #D97706; }
.callout strong { color: #17876D; }
.callout.warn strong { color: #D97706; }
/* Table */
table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 5pt; }
th { background: #F4F6F2; font-size: 7pt; letter-spacing: 1pt; text-transform: uppercase;
  color: #6C7A8E; text-align: left; padding: 4pt 6pt; border-bottom: 1.5pt solid #D8E0DA; }
td { padding: 4pt 6pt; border-bottom: 1pt solid #D8E0DA; vertical-align: top; }
tr:last-child td { border-bottom: none; }
td:first-child { font-weight: bold; color: #0D3B57; }
/* Misc */
.btn { display: inline-block; background: #17876D; color: #fff; font-size: 7pt; font-weight: bold; padding: 1.5pt 5pt; border-radius: 3pt; }
/* Footer */
.footer { text-align: center; font-size: 7.5pt; color: #6C7A8E; margin-top: 20pt; padding-top: 8pt; border-top: 1pt solid #D8E0DA; }
.footer strong { color: #17876D; }
</style>
</head>
<body>
<div class="page">

<div class="cover">
  <div class="cover-eye">Darasa Finance · Normal Accountant Edition</div>
  <h1>Finance System<br>User Guidebook</h1>
  <p>Step-by-step reference for everyday tasks — fee entry, receipts, expenses, reports, and more.</p>
  <span class="cover-badge">For Normal Accountants</span>
</div>

<div class="toc">
  <div class="toc-title">Contents</div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">01</span> Logging In</div><div class="toc-cell"><span class="toc-n">02</span> Dashboard</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">03</span> Fee Entry</div><div class="toc-cell"><span class="toc-n">04</span> Student Ledger</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">05</span> Overdue Payments</div><div class="toc-cell"><span class="toc-n">06</span> Advance Payments</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">07</span> Submit Expenses</div><div class="toc-cell"><span class="toc-n">08</span> Inventory</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">09</span> Send SMS</div><div class="toc-cell"><span class="toc-n">10</span> Download Reports</div></div>
  <div class="toc-row"><div class="toc-cell"><span class="toc-n">11</span> Tips &amp; Help</div><div class="toc-cell"></div></div>
</div>

{{-- 01 Login --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">01</div><div><h2>Logging In</h2><div class="sd">Access the system from any browser, any device.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Open your browser and go to the school finance link provided by your administrator.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Enter your <strong>email address</strong> and <strong>password</strong>.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">3</div></div><div class="step-c">Click <span class="btn">Log In</span>. You will land on the Dashboard.</div></div>
  <div class="callout warn"><strong>Forgot password?</strong> Contact your main accountant or email devs@olamtec.co.tz to reset it.</div>
</div>

{{-- 02 Dashboard --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">02</div><div><h2>The Dashboard</h2><div class="sd">Summary — what's collected, what's pending.</div></div></div>
  <table>
    <thead><tr><th>Card</th><th>What it shows</th></tr></thead>
    <tbody>
      <tr><td>Total Fees Expected</td><td>Sum of all fees assigned to students this year</td></tr>
      <tr><td>Total Collected</td><td>Payments received so far</td></tr>
      <tr><td>Outstanding Balance</td><td>Fees not yet paid</td></tr>
      <tr><td>Advance Balance</td><td>Money paid in excess, held for future fees</td></tr>
    </tbody>
  </table>
</div>

{{-- 03 Fee Entry --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">03</div><div><h2>Fee Entry</h2><div class="sd">Assign fees (Sales) and record payments (Receipts).</div></div></div>
  <div class="sub">Part A — Sales entry (billing a student)</div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Fee Entry</strong>. Search for and select the student.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">In the <strong>Sales</strong> tab, select the Particular, enter amount, choose academic year, click <span class="btn">Save Sales</span>.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">3</div></div><div class="step-c">Click <span class="btn">Add Another</span> to add more fee particulars for the same student, or <span class="btn">Done</span> to close.</div></div>
  <div class="sub">Part B — Receipt (recording a payment)</div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Switch to the <strong>Receipt</strong> tab. Search for and select the student.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Select the Particular. Enter amount paid and payment method.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">3</div></div><div class="step-c">Click <span class="btn">Save Receipt</span>. Note the voucher number for the physical receipt you hand to the parent.</div></div>
  <div class="callout warn"><strong>Double-check before saving.</strong> Only the main accountant can reverse a receipt.</div>
</div>

{{-- 04 Ledger --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">04</div><div><h2>Student Ledger</h2><div class="sd">Full financial history for any student.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Ledgers</strong> → Student Ledger tab. Search for the student.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">View all entries. Click <span class="btn">Download PDF</span> to print a statement for the parent.</div></div>
  <div class="callout"><strong>Tip:</strong> Negative balance = advance paid. Positive balance = still owes money.</div>
</div>

{{-- 05 Overdue --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">05</div><div><h2>Overdue Payments</h2><div class="sd">Students with fees past their deadline.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Overdue Payments</strong>. Filter by class if needed.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Export with <span class="btn">Download PDF</span> or <span class="btn">Download CSV</span> for follow-up.</div></div>
</div>

{{-- 06 Advance --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">06</div><div><h2>Advance Payments</h2><div class="sd">Apply credit balances to outstanding fees.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Advance Payments</strong>. Click <span class="btn">Assign to Fee</span> next to the student.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Select the fee Particular. Amount auto-fills to the lesser of advance balance or outstanding fee.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">3</div></div><div class="step-c">Adjust amount if needed, add a note, and click <span class="btn">Apply Advance</span>.</div></div>
</div>

{{-- 07 Expenses --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">07</div><div><h2>Submit Expenses</h2><div class="sd">Record school expenditure for approval.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Expenses</strong> → <span class="btn">New Submission</span>.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Select Expense Category, enter Transaction Date and description.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">3</div></div><div class="step-c">Add line items: click <span class="btn">+ Add Item</span>, enter name, quantity, unit price, select Good or Service.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">4</div></div><div class="step-c">Click <span class="btn">Submit</span>. Status is Pending until the main accountant approves.</div></div>
</div>

{{-- 08 Inventory --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">08</div><div><h2>Inventory</h2><div class="sd">Track school supplies and assets.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>Inventory</strong>. Click <span class="btn">Add Item</span> to register new stock.</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">To record movement, click <span class="btn">Movements</span> → <span class="btn">Record Movement</span>, choose Stock In or Stock Out.</div></div>
</div>

{{-- 09 SMS --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">09</div><div><h2>Send SMS</h2><div class="sd">Notify parents by text message.</div></div></div>
  <div class="step"><div class="step-n"><div class="step-circle">1</div></div><div class="step-c">Sidebar → <strong>SMS</strong>. Select recipients (all parents, a class, or individual students).</div></div>
  <div class="step"><div class="step-n"><div class="step-circle">2</div></div><div class="step-c">Type message and click <span class="btn">Send SMS</span>. For fee reminders, use the Fee Reminder tab.</div></div>
  <div class="callout warn"><strong>Check SMS credits</strong> before sending large batches. Balance is shown at the top of the SMS page.</div>
</div>

{{-- 10 Reports --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">10</div><div><h2>Download Reports</h2><div class="sd">Export data as PDF, CSV, or Excel.</div></div></div>
  <table>
    <thead><tr><th>Report</th><th>Contents</th><th>Format</th></tr></thead>
    <tbody>
      <tr><td>Fee Collection</td><td>Expected vs collected per particular, grouped by class</td><td>PDF</td></tr>
      <tr><td>Overdue List</td><td>Students with unpaid fees past deadline</td><td>PDF / CSV</td></tr>
      <tr><td>Student Statement</td><td>Full ledger for one student</td><td>PDF</td></tr>
      <tr><td>Excel Report</td><td>Sheet 1: fee collection. Sheet 2: budget + weekly expenses</td><td>Excel</td></tr>
    </tbody>
  </table>
  <div class="callout">For Excel: Reports → Download Excel report → select year → Download.</div>
</div>

{{-- 11 Tips --}}
<div class="sec">
  <div class="sec-hdr"><div class="sec-num">11</div><div><h2>Tips &amp; Help</h2><div class="sd">Common questions and contacts.</div></div></div>
  <div class="sub">Things only the main accountant can do</div>
  <table>
    <thead><tr><th>Action</th><th>Who</th></tr></thead>
    <tbody>
      <tr><td>Reverse a voucher</td><td>Main accountant</td></tr>
      <tr><td>Approve expense submissions</td><td>Main accountant</td></tr>
      <tr><td>Add or edit fee particulars</td><td>Main accountant</td></tr>
      <tr><td>Manage students &amp; classes</td><td>Main accountant</td></tr>
      <tr><td>Payroll / school settings</td><td>Main accountant</td></tr>
      <tr><td>Reset another user's password</td><td>Main accountant / administrator</td></tr>
    </tbody>
  </table>
  <div class="sub">Contact the administrator</div>
  <div class="callout">
    For any system issue or problem you cannot resolve:<br>
    <strong>Email:</strong> devs@olamtec.co.tz<br>
    <strong>System:</strong> Darasa Finance by Olam Technologies<br>
    Include: which page you were on, what you did, and the error message (if any).
  </div>
  <div class="callout warn" style="margin-top:7pt"><strong>Keep your login private.</strong> Every action is recorded against your account. Log out when leaving your workstation.</div>
</div>

<div class="footer">
  <p>Darasa Finance · <strong>Normal Accountant Guidebook</strong></p>
  <p>Support: devs@olamtec.co.tz &nbsp;|&nbsp; finance.darasa360.co.tz</p>
</div>

</div>
</body>
</html>
