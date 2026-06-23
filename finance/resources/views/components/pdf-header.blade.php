{{-- School banner for DomPDF: optional logo left (height = header row), name + contact. Logo as data URI for reliable rendering. --}}
@php
    $hdr = isset($headerHeightPx) ? (int) $headerHeightPx : 56;
    if ($hdr < 40) {
        $hdr = 40;
    }
    if ($hdr > 96) {
        $hdr = 96;
    }

    $showPdfLogo = true;
    if (isset($school) && $school) {
        $showPdfLogo = (bool) ($school->show_logo_on_pdfs ?? true);
    }

    $logoDataUri = null;
    if ($showPdfLogo && isset($school) && $school && ! empty($school->logo_path)) {
        $rel = ltrim((string) $school->logo_path, '/');
        foreach ([public_path('storage/'.$rel), storage_path('app/public/'.$rel)] as $fullPath) {
            if (is_string($fullPath) && is_file($fullPath)) {
                $mime = @mime_content_type($fullPath) ?: 'image/png';
                if (is_string($mime) && str_starts_with($mime, 'image/')) {
                    $raw = @file_get_contents($fullPath);
                    if ($raw !== false && $raw !== '') {
                        $logoDataUri = 'data:'.$mime.';base64,'.base64_encode($raw);
                    }
                }
                break;
            }
        }
    }

    $schoolName = (isset($school) && $school && ! empty($school->school_name)) ? $school->school_name : 'Darasa Finance';
    $line2Parts = array_filter([
        isset($school) && $school ? ($school->po_box ?? null) : null,
        isset($school) && $school ? ($school->region ?? null) : null,
        (isset($school) && $school && ! empty($school->phone)) ? 'Tel: '.$school->phone : null,
    ], fn ($v) => $v !== null && $v !== '');
    $line2 = $line2Parts !== [] ? implode(' | ', $line2Parts) : '';
@endphp
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;border-bottom:2px solid #333;padding-bottom:8px;">
    <tr>
        @if($logoDataUri)
            <td style="vertical-align:middle;width:1px;padding:0 14px 0 0;height:{{ $hdr }}px;">
                <img src="{{ $logoDataUri }}" alt="" style="height:{{ $hdr }}px;width:auto;max-width:180px;object-fit:contain;display:block;" />
            </td>
        @endif
        <td style="vertical-align:middle;text-align:left;padding:0;">
            <div style="font-size:18px;font-weight:bold;line-height:1.15;color:#111;">{{ $schoolName }}</div>
            @if($line2 !== '')
                <div style="font-size:10px;color:#555;margin-top:4px;line-height:1.35;">{{ $line2 }}</div>
            @endif
        </td>
    </tr>
</table>
