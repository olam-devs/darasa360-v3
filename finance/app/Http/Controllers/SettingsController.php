<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SchoolSetting::getSettings();

        return view('admin.accountant.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'po_box' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'office_whatsapp_number' => 'nullable|string|max:32',
            'parent_messenger_pin' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|max:2048',
            'show_logo_on_pdfs' => 'required|in:0,1',
        ]);

        $settings = SchoolSetting::first() ?? new SchoolSetting;

        $settings->school_name = $validated['school_name'];
        $settings->po_box = $validated['po_box'] ?? null;
        $settings->region = $validated['region'] ?? null;
        $settings->phone = $validated['phone'] ?? null;
        $settings->office_whatsapp_number = $validated['office_whatsapp_number'] ?? null;
        $settings->parent_messenger_pin = $validated['parent_messenger_pin'] ?? null;
        $settings->email = $validated['email'] ?? null;
        $settings->show_logo_on_pdfs = $validated['show_logo_on_pdfs'] === '1';

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo_path && Storage::exists($settings->logo_path)) {
                Storage::delete($settings->logo_path);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            $settings->logo_path = $logoPath;
        }

        $settings->save();

        return redirect()->route('accountant.settings')
            ->with('success', 'Settings updated successfully!');
    }
}
