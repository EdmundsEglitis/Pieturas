<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BusStop;
use App\Models\RoadSegment;

class BusStopShowController extends Controller
{
    public function multiple(Request $request)
    {
        $ids = explode(',', $request->query('stops', ''));
        $stops = BusStop::whereIn('id', $ids)->get();
        return view('busstop.multiple', compact('stops'));
    }

    public function batchRoadSpeeds(Request $request)
    {
        $segments = $request->input('segments', []);
        if (empty($segments)) return response()->json([]);

        $lookup = [];
        $placeholders = [];
        $bindings = [];

        foreach ($segments as $i => $s) {
            $lat1 = (string)$s['lat1'];
            $lon1 = (string)$s['lon1'];
            $lat2 = (string)$s['lat2'];
            $lon2 = (string)$s['lon2'];

            // Canonical ordering to handle reversed segments
            if ([$lat1,$lon1] > [$lat2,$lon2]) {
                [$lat1,$lon1,$lat2,$lon2] = [$lat2,$lon2,$lat1,$lon1];
            }

            $placeholders[] = "(?, ?, ?, ?)";
            array_push($bindings, $lat1, $lon1, $lat2, $lon2);
        }

        $inClause = implode(',', $placeholders);
        $rows = RoadSegment::whereRaw("(lat1, lon1, lat2, lon2) IN ($inClause)", $bindings)->get();

        foreach ($rows as $r) {
            $key1 = "{$r->lat1},{$r->lon1},{$r->lat2},{$r->lon2}";
            $key2 = "{$r->lat2},{$r->lon2},{$r->lat1},{$r->lon1}";
            $lookup[$key1] = $r->maxspeed;
            $lookup[$key2] = $r->maxspeed;
        }

        $result = [];
        foreach ($segments as $i => $s) {
            $key1 = "{$s['lat1']},{$s['lon1']},{$s['lat2']},{$s['lon2']}";
            $key2 = "{$s['lat2']},{$s['lon2']},{$s['lat1']},{$s['lon1']}";
            $result[] = [
                'index' => $i,
                'maxspeed' => $lookup[$key1] ?? $lookup[$key2] ?? null
            ];
        }

        return response()->json($result);
    }

public function saveRoadSegmentBatch(Request $request)
{
    $segments = $request->input('segments', []);
    \Log::info('Received segments for saving', $segments); // DEBUG

    if (empty($segments)) {
        \Log::info('No segments to save.');
        return response()->json(['saved' => 0]);
    }

    $savedCount = 0;

    DB::transaction(function() use ($segments, &$savedCount) {
        foreach ($segments as $s) {
            $lat1 = round($s['lat1'], 7);
            $lon1 = round($s['lon1'], 7);
            $lat2 = round($s['lat2'], 7);
            $lon2 = round($s['lon2'], 7);
            $maxspeed = $s['maxspeed'] ?? 50;

            $midLat = round(($lat1 + $lat2) / 2, 7);
            $midLon = round(($lon1 + $lon2) / 2, 7);

            $data = [
                'lat1'=>$lat1, 'lon1'=>$lon1, 'lat2'=>$lat2, 'lon2'=>$lon2,
                'mid_lat'=>$midLat, 'mid_lon'=>$midLon, 'maxspeed'=>$maxspeed
            ];

            \Log::info('Saving segment', $data);

            RoadSegment::updateOrCreate(
                ['lat1'=>$lat1,'lon1'=>$lon1,'lat2'=>$lat2,'lon2'=>$lon2],
                $data
            );

            $revData = [
                'lat1'=>$lat2,'lon1'=>$lon2,'lat2'=>$lat1,'lon2'=>$lon1,
                'mid_lat'=>$midLat,'mid_lon'=>$midLon,'maxspeed'=>$maxspeed
            ];

            \Log::info('Saving reversed segment', $revData);

            RoadSegment::updateOrCreate(
                ['lat1'=>$lat2,'lon1'=>$lon2,'lat2'=>$lat1,'lon2'=>$lon1],
                $revData
            );

            $savedCount++;
        }
    });

    \Log::info("Saved segments count: $savedCount");

    return response()->json(['saved' => $savedCount]);
}

}
