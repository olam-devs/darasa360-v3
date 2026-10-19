<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ManagementQuarterAnalytics
{
    public const LABELS = [1=>'Q1 · Jan–Mar', 2=>'Q2 · Apr–Jun', 3=>'Q3 · Jul–Sep', 4=>'Q4 · Oct–Dec'];

    /** Read the current tenant only; academic-year IDs differ between schools. */
    public function report(int $year, int $quarter): array
    {
        $db = DB::connection('tenant');
        $yearIds = $db->table('academic_years')->where('name', (string)$year)->pluck('id');
        $assignments = $db->table('particular_student')->whereIn('academic_year_id', $yearIds);
        $totals = (clone $assignments)->whereBetween('quarter', [1,4])->select('quarter')
            ->selectRaw('COUNT(*) as assignments, COUNT(DISTINCT student_id) as students, COALESCE(SUM(sales),0) as required_amount, COALESCE(SUM(credit),0) as paid, COALESCE(SUM(CASE WHEN sales > credit THEN sales-credit ELSE 0 END),0) as outstanding')
            ->groupBy('quarter')->get()->keyBy('quarter');
        $quarters = [];
        foreach (self::LABELS as $number => $label) {
            $row = $totals->get($number);
            $quarters[$number] = ['quarter'=>$number,'label'=>$label,'assignments'=>(int)($row->assignments ?? 0),
                'students'=>(int)($row->students ?? 0),'required'=>(float)($row->required_amount ?? 0),
                'paid'=>(float)($row->paid ?? 0),'outstanding'=>(float)($row->outstanding ?? 0),
                'rate'=>($row->required_amount ?? 0) > 0 ? 100 * (float)$row->paid / (float)$row->required_amount : null];
        }
        $particulars = $db->table('particular_student as ps')->leftJoin('particulars as p','p.id','=','ps.particular_id')
            ->whereIn('ps.academic_year_id',$yearIds)->where('ps.quarter',$quarter)
            ->select('ps.particular_id','p.name')->selectRaw('COUNT(*) as assignments, SUM(ps.sales) as required_amount, SUM(ps.credit) as paid, SUM(CASE WHEN ps.sales > ps.credit THEN ps.sales-ps.credit ELSE 0 END) as outstanding')
            ->groupBy('ps.particular_id','p.name')->orderBy('p.name')->get();
        return ['year_exists'=>$yearIds->isNotEmpty(),'quarters'=>$quarters,'selected'=>$quarters[$quarter],
            'particulars'=>$particulars,'unassigned'=>(clone $assignments)->where(function($q){$q->whereNull('quarter')->orWhereNotBetween('quarter',[1,4]);})->count()];
    }
}
