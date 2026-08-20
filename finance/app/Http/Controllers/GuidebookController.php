<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuidebookController extends Controller
{
    public function normal()
    {
        $settings = SchoolSetting::getSettings();
        return view('admin.accountant.guidebooks.normal', compact('settings'));
    }

    public function normalPdf()
    {
        $settings = SchoolSetting::getSettings();
        $pdf = Pdf::loadView('admin.accountant.guidebooks.normal-pdf', compact('settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
                'dpi'                  => 96,
            ]);
        return $pdf->download('darasa-finance-normal-accountant-guidebook.pdf');
    }

    public function main()
    {
        $settings = SchoolSetting::getSettings();
        return view('admin.accountant.guidebooks.main', compact('settings'));
    }

    public function mainPdf()
    {
        $settings = SchoolSetting::getSettings();
        $pdf = Pdf::loadView('admin.accountant.guidebooks.main-pdf', compact('settings'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
                'dpi'                  => 96,
            ]);
        return $pdf->download('darasa-finance-main-accountant-guidebook.pdf');
    }
}
