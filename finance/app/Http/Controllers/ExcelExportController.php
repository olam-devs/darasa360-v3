<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Particular;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ExcelExportController extends Controller
{
    private const HEADER_FILL   = 'FF1E3A5F';  // dark blue
    private const SUBHEAD_FILL  = 'FF2563EB';  // medium blue
    private const CLASS_FILL    = 'FFdbeafe';  // light blue
    private const ALT_FILL      = 'FFf8fafc';
    private const TOTAL_FILL    = 'FFfef9c3';  // yellow for totals
    private const BUDGET_FILL   = 'FF0f766e';  // teal for budget header
    private const WEEK_FILL     = 'FF6b7280';  // gray for week headers

    public function download(Request $request)
    {
        $year     = (int) $request->get('year', date('Y'));
        $settings = SchoolSetting::getSettings();
        $school   = $settings->school_name ?? 'School';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle("Financial Report {$year}")
            ->setCreator('Darasa Finance')
            ->setCompany($school);

        $this->buildFeeCollectionSheet($spreadsheet, $year, $school);
        $this->buildBudgetExpenseSheet($spreadsheet, $year, $school);

        $spreadsheet->setActiveSheetIndex(0);

        $writer   = new Xlsx($spreadsheet);
        $filename = "darasa-report-{$year}.xlsx";

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHEET 1: Fee Collection
    // ──────────────────────────────────────────────────────────────────────────
    private function buildFeeCollectionSheet(Spreadsheet $spreadsheet, int $year, string $school): void
    {
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Fee Collection');
        $ws->getDefaultColumnDimension()->setWidth(16);

        // All particulars (column headers)
        $particulars = Particular::orderBy('name')->get();
        $numParts    = $particulars->count();

        // Column layout:
        // A=S/N  B=Name  C=Reg No  D=Class  [E..E+2*n-1 = Part1_Expected, Part1_Paid ...]
        // then: Total Expected, Total Paid, Balance/Advance
        $firstPartCol = 5;                         // column E (1-indexed)
        $totalExpCol  = $firstPartCol + $numParts * 2;
        $totalPaidCol = $totalExpCol + 1;
        $balanceCol   = $totalPaidCol + 1;
        $advanceCol   = $balanceCol + 1;

        $colLetter = fn(int $n) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);

        // ── Row 1: School name banner ────────────────────────────────────────
        $ws->mergeCells("A1:{$colLetter($advanceCol)}1");
        $ws->setCellValue('A1', strtoupper($school) . " — FEE COLLECTION REPORT {$year}");
        $this->styleHeader($ws, "A1:{$colLetter($advanceCol)}1", self::HEADER_FILL, 13, true);
        $ws->getRowDimension(1)->setRowHeight(24);

        // ── Row 2: Column headers ────────────────────────────────────────────
        $ws->setCellValue('A2', 'S/N');
        $ws->setCellValue('B2', "STUDENT NAME");
        $ws->setCellValue('C2', 'REG NO');
        $ws->setCellValue('D2', 'CLASS');

        $col = $firstPartCol;
        foreach ($particulars as $p) {
            $ws->mergeCells("{$colLetter($col)}2:{$colLetter($col+1)}2");
            $ws->setCellValue("{$colLetter($col)}2", strtoupper($p->name));
            $ws->setCellValue("{$colLetter($col)}3", 'Expected');
            $ws->setCellValue("{$colLetter($col+1)}3", 'Collected');
            $col += 2;
        }
        $ws->mergeCells("{$colLetter($totalExpCol)}2:{$colLetter($totalExpCol)}3");
        $ws->setCellValue("{$colLetter($totalExpCol)}2", 'TOTAL EXPECTED');
        $ws->mergeCells("{$colLetter($totalPaidCol)}2:{$colLetter($totalPaidCol)}3");
        $ws->setCellValue("{$colLetter($totalPaidCol)}2", 'TOTAL COLLECTED');
        $ws->mergeCells("{$colLetter($balanceCol)}2:{$colLetter($balanceCol)}3");
        $ws->setCellValue("{$colLetter($balanceCol)}2", 'BALANCE');
        $ws->mergeCells("{$colLetter($advanceCol)}2:{$colLetter($advanceCol)}3");
        $ws->setCellValue("{$colLetter($advanceCol)}2", 'ADVANCE');

        $this->styleHeader($ws, "A2:{$colLetter($advanceCol)}3", self::SUBHEAD_FILL, 9, true);
        $ws->getRowDimension(2)->setRowHeight(18);
        $ws->getRowDimension(3)->setRowHeight(16);

        // ── Data rows ────────────────────────────────────────────────────────
        $classes  = SchoolClass::orderBy('name')->get();
        $row      = 4;
        $sn       = 0;
        $grandExpected = 0;
        $grandPaid     = 0;
        $grandBalance  = 0;
        $grandAdvance  = 0;

        foreach ($classes as $class) {
            $students = Student::where('class_id', $class->id)
                ->where('status', 'active')
                ->with('particulars')
                ->orderBy('name')
                ->get();

            if ($students->isEmpty()) continue;

            // Class banner row
            $ws->mergeCells("A{$row}:{$colLetter($advanceCol)}{$row}");
            $ws->setCellValue("A{$row}", strtoupper($class->name));
            $ws->getStyle("A{$row}:{$colLetter($advanceCol)}{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::CLASS_FILL]],
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF1e3a5f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
            ]);
            $row++;

            $classTotalExp  = 0;
            $classTotalPaid = 0;

            foreach ($students as $student) {
                $sn++;
                $partMap = [];
                foreach ($student->particulars as $p) {
                    $partMap[$p->id] = [
                        'sales'  => (float)$p->pivot->sales + (float)$p->pivot->debit,
                        'credit' => (float)$p->pivot->credit,
                    ];
                }

                $totalExp  = 0;
                $totalPaid = 0;

                $ws->setCellValue("A{$row}", $sn);
                $ws->setCellValue("B{$row}", $student->name);
                $ws->setCellValue("C{$row}", $student->student_reg_no);
                $ws->setCellValue("D{$row}", $class->name);

                $col = $firstPartCol;
                foreach ($particulars as $p) {
                    $data    = $partMap[$p->id] ?? null;
                    $exp     = $data ? $data['sales'] : null;
                    $paid    = $data ? $data['credit'] : null;
                    $totalExp  += $data ? $data['sales'] : 0;
                    $totalPaid += $data ? $data['credit'] : 0;
                    if ($exp !== null)  $ws->setCellValue("{$colLetter($col)}{$row}", $exp);
                    if ($paid !== null) $ws->setCellValue("{$colLetter($col+1)}{$row}", $paid);
                    $col += 2;
                }

                $balance = $totalExp - $totalPaid;
                $advance = (float)($student->advance_balance ?? 0);

                $ws->setCellValue("{$colLetter($totalExpCol)}{$row}", $totalExp);
                $ws->setCellValue("{$colLetter($totalPaidCol)}{$row}", $totalPaid);
                $ws->setCellValue("{$colLetter($balanceCol)}{$row}", $balance);
                if ($advance > 0) $ws->setCellValue("{$colLetter($advanceCol)}{$row}", $advance);

                $classTotalExp  += $totalExp;
                $classTotalPaid += $totalPaid;

                // Row background alternating
                if ($sn % 2 === 0) {
                    $ws->getStyle("A{$row}:{$colLetter($advanceCol)}{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ALT_FILL);
                }
                // Number format for numeric columns
                $numRange = "{$colLetter($firstPartCol)}{$row}:{$colLetter($advanceCol)}{$row}";
                $ws->getStyle($numRange)->getNumberFormat()->setFormatCode('#,##0');
                $ws->getStyle("A{$row}:{$colLetter($advanceCol)}{$row}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $ws->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $grandExpected += $totalExp;
                $grandPaid     += $totalPaid;
                $grandBalance  += $balance;
                $grandAdvance  += $advance;

                $row++;
            }

            // Class subtotal row
            $ws->setCellValue("A{$row}", '');
            $ws->mergeCells("A{$row}:D{$row}");
            $ws->setCellValue("A{$row}", "Sub-total — {$class->name}");
            $ws->setCellValue("{$colLetter($totalExpCol)}{$row}", $classTotalExp);
            $ws->setCellValue("{$colLetter($totalPaidCol)}{$row}", $classTotalPaid);
            $ws->setCellValue("{$colLetter($balanceCol)}{$row}", $classTotalExp - $classTotalPaid);
            $ws->getStyle("A{$row}:{$colLetter($advanceCol)}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::TOTAL_FILL]],
                'font' => ['bold' => true, 'size' => 8.5],
            ]);
            $ws->getStyle("{$colLetter($firstPartCol)}{$row}:{$colLetter($advanceCol)}{$row}")
                ->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // Grand total row
        $ws->mergeCells("A{$row}:D{$row}");
        $ws->setCellValue("A{$row}", 'GRAND TOTAL');
        $ws->setCellValue("{$colLetter($totalExpCol)}{$row}", $grandExpected);
        $ws->setCellValue("{$colLetter($totalPaidCol)}{$row}", $grandPaid);
        $ws->setCellValue("{$colLetter($balanceCol)}{$row}", $grandBalance);
        $ws->setCellValue("{$colLetter($advanceCol)}{$row}", $grandAdvance > 0 ? $grandAdvance : null);
        $this->styleHeader($ws, "A{$row}:{$colLetter($advanceCol)}{$row}", self::HEADER_FILL, 9, true);
        $ws->getStyle("{$colLetter($firstPartCol)}{$row}:{$colLetter($advanceCol)}{$row}")
            ->getNumberFormat()->setFormatCode('#,##0');

        // Column widths
        $ws->getColumnDimension('A')->setWidth(6);
        $ws->getColumnDimension('B')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(12);
        $ws->getColumnDimension('D')->setWidth(14);
        for ($c = $firstPartCol; $c <= $advanceCol; $c++) {
            $ws->getColumnDimension($colLetter($c))->setWidth(14);
        }

        // Freeze panes
        $ws->freezePane('E4');

        // Borders
        $ws->getStyle("A2:{$colLetter($advanceCol)}{$row}")->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFd1d5db');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHEET 2: Budget & Expenses
    // ──────────────────────────────────────────────────────────────────────────
    private function buildBudgetExpenseSheet(Spreadsheet $spreadsheet, int $year, string $school): void
    {
        $ws = $spreadsheet->createSheet();
        $ws->setTitle('Budget & Expenses');
        $ws->getDefaultColumnDimension()->setWidth(18);

        $startDate = "{$year}-01-01";
        $endDate   = "{$year}-12-31";

        // Approved submissions for the year (use total_amount cache on submission)
        $submissions = DB::connection('tenant')
            ->table('expense_submissions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'partially_approved'])
            ->select('expense_category_id', 'total_amount', 'transaction_date')
            ->get();

        // Budget plans whose from_date falls in this calendar year
        $budgetPlans = DB::connection('tenant')
            ->table('expense_category_plans')
            ->whereBetween('from_date', [$startDate, $endDate])
            ->select('expense_category_id', DB::raw('SUM(expected_amount) as total_budget'))
            ->groupBy('expense_category_id')
            ->pluck('total_budget', 'expense_category_id');

        // Build week labels: "W1 (Jan 1–7)", etc.
        $weeksWithData = [];
        foreach ($submissions as $sub) {
            $dt   = new \DateTime($sub->transaction_date);
            $week = (int)$dt->format('W');
            $weeksWithData[$week] = true;
        }
        ksort($weeksWithData);
        $weekNumbers = array_keys($weeksWithData);

        // Generate week date ranges for labels
        $weekLabels = [];
        foreach ($weekNumbers as $wn) {
            $dto = new \DateTime();
            $dto->setISODate($year, $wn, 1);
            $start = $dto->format('M j');
            $dto->modify('+6 days');
            $end = $dto->format('M j');
            $weekLabels[$wn] = "Wk{$wn} ({$start}–{$end})";
        }

        // Category totals per week
        $categories  = ExpenseCategory::orderBy('name')->get();
        $numWeeks    = count($weekNumbers);

        // Data: [category_id => [week_number => amount]]
        $expByWeek = [];
        $expTotal  = [];
        foreach ($submissions as $sub) {
            $dt   = new \DateTime($sub->transaction_date);
            $wn   = (int)$dt->format('W');
            $cid  = $sub->expense_category_id;
            $expByWeek[$cid][$wn]  = ($expByWeek[$cid][$wn]  ?? 0) + (float)$sub->total_amount;
            $expTotal[$cid]         = ($expTotal[$cid]         ?? 0) + (float)$sub->total_amount;
        }

        // ── Section 1: Budget vs Actual ──────────────────────────────────────
        $colLetter = fn(int $n) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);

        $lastBudgetCol = 4;
        $ws->mergeCells("A1:{$colLetter($lastBudgetCol)}1");
        $ws->setCellValue('A1', strtoupper($school) . " — BUDGET & EXPENSE REPORT {$year}");
        $this->styleHeader($ws, "A1:{$colLetter($lastBudgetCol)}1", self::HEADER_FILL, 13, true);
        $ws->getRowDimension(1)->setRowHeight(24);

        // Budget header row
        $ws->setCellValue('A2', 'S/N');
        $ws->setCellValue('B2', 'EXPENSE CATEGORY');
        $ws->setCellValue('C2', 'BUDGETED AMOUNT');
        $ws->setCellValue('D2', 'ACTUAL SPENT');
        $ws->setCellValue('E2', 'VARIANCE');
        $this->styleHeader($ws, 'A2:E2', self::BUDGET_FILL, 9, true);
        $ws->getRowDimension(2)->setRowHeight(18);

        $row     = 3;
        $sn      = 0;
        $totalBudget  = 0;
        $totalActual  = 0;

        $categoriesWithExpense = [];

        foreach ($categories as $cat) {
            $sn++;
            $budget = (float)($budgetPlans[$cat->id] ?? 0);
            $actual = (float)($expTotal[$cat->id] ?? 0);
            $var    = $budget - $actual;

            $ws->setCellValue("A{$row}", $sn);
            $ws->setCellValue("B{$row}", $cat->name);
            $ws->setCellValue("C{$row}", $budget > 0 ? $budget : null);
            $ws->setCellValue("D{$row}", $actual > 0 ? $actual : null);
            $ws->setCellValue("E{$row}", ($budget > 0 || $actual > 0) ? $var : null);

            $ws->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $ws->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($sn % 2 === 0) {
                $ws->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ALT_FILL);
            }

            $totalBudget += $budget;
            $totalActual += $actual;

            if ($actual > 0) $categoriesWithExpense[] = $cat;

            $row++;
        }

        // Totals
        $ws->setCellValue("A{$row}", '');
        $ws->mergeCells("A{$row}:B{$row}");
        $ws->setCellValue("A{$row}", 'TOTAL');
        $ws->setCellValue("C{$row}", $totalBudget > 0 ? $totalBudget : null);
        $ws->setCellValue("D{$row}", $totalActual);
        $ws->setCellValue("E{$row}", $totalBudget - $totalActual);
        $ws->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $this->styleHeader($ws, "A{$row}:E{$row}", self::HEADER_FILL, 9, true);
        $row += 2;

        // ── Section 2: Weekly Expense Breakdown ──────────────────────────────
        if (empty($weekNumbers)) {
            $ws->setCellValue("A{$row}", 'No approved expense submissions found for this year.');
            return;
        }

        $weekHeaderRow = $row;
        $numCatCols    = count($categoriesWithExpense);
        $lastWeekCol   = 2 + $numCatCols; // A=Week, B..B+n-1=categories, last=Total

        $ws->mergeCells("A{$weekHeaderRow}:{$colLetter($lastWeekCol + 1)}{$weekHeaderRow}");
        $ws->setCellValue("A{$weekHeaderRow}", "WEEKLY EXPENSE BREAKDOWN BY CATEGORY — {$year}");
        $this->styleHeader($ws, "A{$weekHeaderRow}:{$colLetter($lastWeekCol + 1)}{$weekHeaderRow}", self::WEEK_FILL, 11, true);
        $ws->getRowDimension($weekHeaderRow)->setRowHeight(20);
        $row++;

        // Column headers for weekly section
        $ws->setCellValue("A{$row}", 'WEEK');
        $col = 2;
        foreach ($categoriesWithExpense as $cat) {
            $ws->setCellValue("{$colLetter($col)}{$row}", $cat->name);
            $col++;
        }
        $ws->setCellValue("{$colLetter($col)}{$row}", 'WEEK TOTAL');
        $this->styleHeader($ws, "A{$row}:{$colLetter($col)}{$row}", self::SUBHEAD_FILL, 8.5, true);
        $ws->getRowDimension($row)->setRowHeight(18);
        $row++;

        $catTotals = array_fill(0, count($categoriesWithExpense), 0);
        $overallTotal = 0;
        $snRow = 0;

        foreach ($weekNumbers as $wn) {
            $snRow++;
            $weekTotal = 0;
            $ws->setCellValue("A{$row}", $weekLabels[$wn]);
            $col = 2;
            $ci  = 0;
            foreach ($categoriesWithExpense as $cat) {
                $amt = (float)($expByWeek[$cat->id][$wn] ?? 0);
                if ($amt > 0) {
                    $ws->setCellValue("{$colLetter($col)}{$row}", $amt);
                    $ws->getStyle("{$colLetter($col)}{$row}")->getNumberFormat()->setFormatCode('#,##0');
                }
                $weekTotal        += $amt;
                $catTotals[$ci++] += $amt;
                $col++;
            }
            $ws->setCellValue("{$colLetter($col)}{$row}", $weekTotal);
            $ws->getStyle("{$colLetter($col)}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $ws->getStyle("{$colLetter($col)}{$row}")->getFont()->setBold(true);
            $overallTotal += $weekTotal;

            if ($snRow % 2 === 0) {
                $ws->getStyle("A{$row}:{$colLetter($col)}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::ALT_FILL);
            }

            $ws->getStyle("A{$row}:{$colLetter($col)}{$row}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);

            $row++;
        }

        // Category totals row
        $ws->setCellValue("A{$row}", 'TOTAL');
        $col = 2;
        $ci  = 0;
        foreach ($categoriesWithExpense as $cat) {
            if ($catTotals[$ci] > 0) {
                $ws->setCellValue("{$colLetter($col)}{$row}", $catTotals[$ci]);
                $ws->getStyle("{$colLetter($col)}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            }
            $ci++; $col++;
        }
        $ws->setCellValue("{$colLetter($col)}{$row}", $overallTotal);
        $ws->getStyle("{$colLetter($col)}{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $this->styleHeader($ws, "A{$row}:{$colLetter($col)}{$row}", self::HEADER_FILL, 9, true);

        // Column widths for Sheet 2
        $ws->getColumnDimension('A')->setWidth(22);
        $ws->getColumnDimension('B')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(16);
        $ws->getColumnDimension('D')->setWidth(16);
        $ws->getColumnDimension('E')->setWidth(16);
        $col2 = 2;
        foreach ($categoriesWithExpense as $cat) {
            $ws->getColumnDimension($colLetter($col2))->setWidth(20);
            $col2++;
        }
        $ws->getColumnDimension($colLetter($col2))->setWidth(16);

        // Borders for section 1
        $ws->getStyle("A2:{$colLetter($lastBudgetCol + 1)}{$weekHeaderRow}")->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFd1d5db');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function styleHeader($ws, string $range, string $argb, float $size, bool $bold): void
    {
        $ws->getStyle($range)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]],
            'font'      => ['bold' => $bold, 'size' => $size, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);
    }
}
