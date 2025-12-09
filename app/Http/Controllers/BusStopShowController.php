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
     * Batch lookup: returns maxspeed for input segments.
     */
public function batchRoadSpeeds(Request $request)
{
    $segments = $request->input('segments', []);
    if (empty($segments)) return response()->json([]);

    $result = [];

    foreach ($segments as $i => $s) {
        $lat1 = round(floatval($s['lat1']), 7);
        $lon1 = round(floatval($s['lon1']), 7);
        $lat2 = round(floatval($s['lat2']), 7);
        $lon2 = round(floatval($s['lon2']), 7);

        if ( [$lat1,$lon1] > [$lat2,$lon2] ) {
            [$lat1,$lon1,$lat2,$lon2] = [$lat2,$lon2,$lat1,$lon1];
        }

        // numeric lookup in DB using rounded values
        $segment = RoadSegment::where(function($q) use ($lat1,$lon1,$lat2,$lon2){
            $q->where('lat1', $lat1)
              ->where('lon1', $lon1)
              ->where('lat2', $lat2)
              ->where('lon2', $lon2);
        })->first();

        $key = "$lat1|$lon1|$lat2|$lon2";
        $result[] = [
            'index' => $i,
            'key' => $key,
            'maxspeed' => $segment->maxspeed ?? null
        ];
    }

    return response()->json($result);
}


    /**
     * Save discovered segments in batch.
     */
    public function saveRoadSegmentBatch(Request $request)
    {
        $segments = $request->input('segments', []);
        if (empty($segments)) return response()->json(['saved' => 0]);

        $saved = 0;

        DB::transaction(function() use ($segments, &$saved) {
            foreach ($segments as $s) {
                if (!isset($s['lat1'], $s['lon1'], $s['lat2'], $s['lon2'], $s['maxspeed'])) continue;

                $lat1 = round(floatval($s['lat1']), 7);
                $lon1 = round(floatval($s['lon1']), 7);
                $lat2 = round(floatval($s['lat2']), 7);
                $lon2 = round(floatval($s['lon2']), 7);
                $maxspeed = intval($s['maxspeed']);

                if ( [$lat1, $lon1] > [$lat2, $lon2] ) {
                    [$lat1, $lon1, $lat2, $lon2] = [$lat2, $lon2, $lat1, $lon1];
                }

                $midLat = round(($lat1 + $lat2) / 2, 7);
                $midLon = round(($lon1 + $lon2) / 2, 7);

                $segment = RoadSegment::updateOrCreate(
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
