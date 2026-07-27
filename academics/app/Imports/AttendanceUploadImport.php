<?php

namespace App\Imports;

/**
 * Intentionally implements no concerns - passed to Excel::toArray() purely to
 * get each sheet back as a raw, 0-indexed array of rows/cells matching the
 * physical layout AttendanceMonthSheetExport wrote, so the importer can find
 * the cutoff-date cell and header row at their known fixed positions.
 */
class AttendanceUploadImport
{
}
