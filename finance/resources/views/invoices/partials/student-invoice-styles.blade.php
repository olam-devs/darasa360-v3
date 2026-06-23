@page { margin: 10mm 8mm; }
body { font-family: Arial, sans-serif; font-size: 9px; margin: 0; padding: 0; }
.student-invoice-page {
    page-break-after: always;
    page-break-inside: avoid;
    break-inside: avoid-page;
}
.student-invoice-page:last-child { page-break-after: auto; }
.student-invoice-page .fees-table th { padding: 3px; font-size: 8px; }
.student-invoice-page .fees-table td { padding: 2px 4px; font-size: 8px; }
.student-invoice-page .balance-box { padding: 6px; margin: 6px 0; }
.student-invoice-page .balance-amount { font-size: 14px; }
.student-invoice-page .student-info { padding: 6px; margin-bottom: 6px; font-size: 8px; }
.student-invoice-page .year-header { padding: 4px 8px; font-size: 9px; }
.student-invoice-page .year-block { margin-bottom: 8px; }
.invoice-title { font-size: 14px; font-weight: bold; margin: 8px 0; text-align: center; }
.student-info { background-color: #f5f5f5; padding: 8px; margin-bottom: 10px; border-left: 3px solid #2196f3; font-size: 9px; line-height: 1.4; }
.fees-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.fees-table th { background-color: #2196f3; color: white; padding: 5px; font-size: 9px; text-align: left; }
.fees-table td { border: 1px solid #ddd; padding: 4px 5px; font-size: 9px; }
.amount { text-align: right; font-weight: bold; }
.total-row { background-color: #f9f9f9; font-weight: bold; font-size: 10px; }
.balance-box { background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; text-align: center; }
.balance-amount { font-size: 18px; font-weight: bold; color: #d9534f; }
.overdue { background-color: #f8d7da; color: #721c24; }
.paid-full { background-color: #d4edda; color: #155724; }
.footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #999; font-size: 8px; text-align: center; color: #666; }
.bank-section { font-size: 9px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #999; }
.bank-option { margin-bottom: 4px; padding: 4px; background-color: #f9f9f9; border-left: 2px solid #2196f3; }
.scholarship-badge { display: inline-block; background: #ffc107; color: #000; padding: 1px 4px; border-radius: 3px; font-size: 7px; font-weight: bold; margin-left: 3px; }
.scholarship-row { background-color: #fff8e1; }
.scholarship-summary { background-color: #fff3cd; border: 1px solid #ffc107; padding: 8px; margin: 8px 0; border-radius: 4px; }
.original-amount { text-decoration: line-through; color: #999; font-size: 8px; }
.year-block { margin-bottom: 12px; page-break-inside: avoid; }
.year-header { background-color: #2196f3; color: white; padding: 6px 10px; font-weight: bold; font-size: 11px; border-radius: 3px 3px 0 0; }
.year-badge { float: right; padding: 2px 8px; border-radius: 3px; font-size: 9px; }
.year-badge-due { background: #f44336; }
.year-badge-paid { background: #4caf50; }
