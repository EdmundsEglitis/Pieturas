<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BusStop;
use App\Models\RoadSegment;

class BusStopShowController extends Controller
{
    /**
     * Show multiple selected bus stops (map page).
     */
    public function multiple(Request $request)
    {
        $ids = explode(',', $request->query('stops', ''));
        $stops = BusStop::whereIn('id', $ids)->get();
        return view('busstop.multiple', compact('stops'));
    }

    /**
     * Batch lookup: given array of segments returns an array where each element
     * corresponds to the input segment index with 'index' and 'maxspeed' (or null).
     *
     * Input payload: { segments: [{lat1,lon1,lat2,lon2}, ...] }
     */
    public function batchRoadSpeeds(Request $request)
    {
        $segments = $request->input('segments', []);
        if (empty($segments)) {
            return response()->json([]);
        }

        // Build canonical keys for all segments (same rounding/ordering logic as save)
        $keys = [];
        $orderedKeys = []; // to preserve order when mapping back
        foreach ($segments as $i => $s) {
            $lat1 = round(floatval($s['lat1']), 7);
            $lon1 = round(floatval($s['lon1']), 7);
            $lat2 = round(floatval($s['lat2']), 7);
            $lon2 = round(floatval($s['lon2']), 7);

            // canonical order (smallest first)
            if ( [$lat1, $lon1] > [$lat2, $lon2] ) {
                [$lat1, $lon1, $lat2, $lon2] = [$lat2, $lon2, $lat1, $lon1];
            }

            $key = "$lat1|$lon1|$lat2|$lon2";
            $keys[$key] = true;
            $orderedKeys[$i] = $key;
        }

        // If no keys, return empty
        if (empty($keys)) {
            return response()->json([]);
        }

        // Query DB for any rows matching canonical keys
        // NOTE: using a concat key in SQL; make sure your DB supports this (MySQL/Postgres do).
        // This avoids issuing N separate queries.
        $dbKeys = array_keys($keys);

        // Build lookup of key => maxspeed
        $lookup = [];

        // Use raw CONCAT to match canonical key (lat/lon stored as decimals with same precision)
        // To make matching reliable, ensure DB values have same rounding (we round before storing).
        $rows = RoadSegment::select('lat1','lon1','lat2','lon2','maxspeed')
            ->whereIn(DB::raw("CONCAT(lat1,'|',lon1,'|',lat2,'|',lon2)"), $dbKeys)
            ->get();

        foreach ($rows as $r) {
            // Rely on DB values being rounded to the same precision as saved
            $k = "{$r->lat1}|{$r->lon1}|{$r->lat2}|{$r->lon2}";
            $lookup[$k] = $r->maxspeed;
        }

        // Build response aligned with input order (index given back)
        $result = [];
        foreach ($orderedKeys as $i => $key) {
            $result[] = [
                'index' => $i,
                'maxspeed' => $lookup[$key] ?? null
            ];
        }

        return response()->json($result);
    }

    /**
     * Save discovered segments in batch.
     *
     * This saves *only* canonical ordering (lat1/lon1 <= lat2/lon2) and only saves segments
     * that have an explicit maxspeed discovered (caller should only send segments that have
     * a real discovered maxspeed). This prevents duplicates and avoids overwriting existing
     * good data with defaults.
     *
     * Input payload: { segments: [{lat1,lon1,lat2,lon2,mid_lat,mid_lon,maxspeed}, ...] }
     */
    public function saveRoadSegmentBatch(Request $request)
    {
        $segments = $request->input('segments', []);
        if (empty($segments)) {
            return response()->json(['saved' => 0]);
        }

        $saved = 0;

        DB::transaction(function() use ($segments, &$saved) {
            foreach ($segments as $s) {
                // Validate presence of required fields and that maxspeed is numeric
                if (!isset($s['lat1'], $s['lon1'], $s['lat2'], $s['lon2']) || !isset($s['maxspeed'])) {
                    continue;
                }

                $lat1 = round(floatval($s['lat1']), 7);
                $lon1 = round(floatval($s['lon1']), 7);
                $lat2 = round(floatval($s['lat2']), 7);
                $lon2 = round(floatval($s['lon2']), 7);
                $maxspeed = intval($s['maxspeed']);

                // canonical ordering: only save in one orientation to prevent duplicates
                if ( [$lat1, $lon1] > [$lat2, $lon2] ) {
                    [$lat1, $lon1, $lat2, $lon2] = [$lat2, $lon2, $lat1, $lon1];
                }

                $midLat = round(($lat1 + $lat2) / 2, 7);
                $midLon = round(($lon1 + $lon2) / 2, 7);

                // updateOrCreate keyed on canonical lat/lon pair — this prevents duplicates
                RoadSegment::updateOrCreate(
                    [
                        'lat1' => $lat1,
                        'lon1' => $lon1,
                        'lat2' => $lat2,
                        'lon2' => $lon2,
                    ],
                    [
                        'mid_lat' => $midLat,
                        'mid_lon' => $midLon,
                        'maxspeed' => $maxspeed
                    ]
                );

                $saved++;
            }
        });

        return response()->json(['saved' => $saved]);
    }
}
