<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BusStopImportController extends Controller
{
    /**
     * Show the import form
     */
    public function showForm()
    {
        return view('admin.busstop-import');
    }

    /**
     * Import bus stops from Excel
     */
    public function importExcel(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // max 10MB
        ]);

        $file = $request->file('file');

        // Load the Excel file
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet   = $spreadsheet->getActiveSheet();
        $rows        = $worksheet->toArray();

        // Remove the header row
        unset($rows[0]);

        // Split into chunks to avoid memory or MySQL limits
        $chunks = array_chunk($rows, 1000);

        foreach ($chunks as $chunk) {
            $withUnified = [];
            $withoutUnified = [];

            foreach ($chunk as $row) {
                $record = [
                    'name'          => trim($row[1] ?? null),
                    'stop_code'     => trim($row[2] ?? null),
                    'unified_code'  => isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null,
                    'latitude'      => $row[4] ?? null,
                    'longitude'     => $row[5] ?? null,
                    'road_side'     => trim($row[6] ?? 'unknown'),
                    'road_number'   => trim($row[7] ?? null),
                    'street'        => trim($row[8] ?? null),
                    'municipality'  => trim($row[9] ?? null),
                    'parish'        => trim($row[10] ?? null),
                    'village'       => trim($row[11] ?? null),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                if ($record['unified_code']) {
                    $withUnified[] = $record;
                } else {
                    $withoutUnified[] = $record;
                }
            }

            // Bulk upsert for rows with unified_code
            if (!empty($withUnified)) {
                DB::table('bus_stops')->upsert(
                    $withUnified,
                    ['unified_code', 'road_side'],
                    [
                        'name',
                        'stop_code',
                        'latitude',
                        'longitude',
                        'road_number',
                        'street',
                        'municipality',
                        'parish',
                        'village',
                        'updated_at',
                    ]
                );
            }

            // Bulk upsert for rows without unified_code
            if (!empty($withoutUnified)) {
                DB::table('bus_stops')->upsert(
                    $withoutUnified,
                    ['stop_code', 'road_side'],
                    [
                        'name',
                        'latitude',
                        'longitude',
                        'road_number',
                        'street',
                        'municipality',
                        'parish',
                        'village',
                        'updated_at',
                    ]
                );
            }
        }

        return redirect()->back()->with(
            'success',
            '✅ Excel import completed successfully: new rows inserted, existing rows updated.'
        );
    }
}
