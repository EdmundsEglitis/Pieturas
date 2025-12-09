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

    public function getRoadSpeed(Request $request)
    {
        $lat1 = $request->query('lat1');
        $lon1 = $request->query('lon1');
        $lat2 = $request->query('lat2');
        $lon2 = $request->query('lon2');

        $segment = RoadSegment::where([['lat1',$lat1],['lon1',$lon1],['lat2',$lat2],['lon2',$lon2]])
            ->orWhere(fn($q) => $q->where([['lat1',$lat2],['lon1',$lon2],['lat2',$lat1],['lon2',$lon1]]))
            ->first();

        return response()->json(['maxspeed' => $segment?->maxspeed]);
    }

    public function saveRoadSegment(Request $request)
    {
        $segment = RoadSegment::updateOrCreate([
            'lat1'=>$request->lat1, 'lon1'=>$request->lon1,
            'lat2'=>$request->lat2, 'lon2'=>$request->lon2,
        ], ['maxspeed'=>$request->maxspeed]);

        return response()->json($segment);
    }

    /**
     * Batch lookup of many tiny route segments.
     * Accepts JSON: { segments: [ {lat1,lon1,lat2,lon2}, ... ] }
     * Returns an array in input order: [ { index: 0, maxspeed: 50 }, ... ]
     */
    public function batchRoadSpeeds(Request $request)
    {
        $segments = $request->input('segments', []);
        if (empty($segments)) {
            return response()->json([]);
        }

        // Build canonical and reversed tuple lists to query in a single DB call.
        // We also keep original indices to return results in input order.
        $canonicalTuples = [];
        $reversedTuples = [];
        $bindingsCanonical = [];
        $bindingsReversed = [];

        foreach ($segments as $i => $s) {
            // Normalize floats as strings to match DB stored values formatting
            $aLat = (string)($s['lat1']);
            $aLon = (string)($s['lon1']);
            $bLat = (string)($s['lat2']);
            $bLon = (string)($s['lon2']);

            // Canonical ordering: lexicographic compare ([lat, lon])
            $aKey = [$aLat, $aLon];
            $bKey = [$bLat, $bLon];
            if ($aKey > $bKey) {
                [$aLat, $aLon, $bLat, $bLon] = [$bLat, $bLon, $aLat, $aLon];
            }

            // push to canonical tuple list (for WHERE (lat1,lon1,lat2,lon2) IN (...))
            $canonicalTuples[] = "(?, ?, ?, ?)";
            array_push($bindingsCanonical, $aLat, $aLon, $bLat, $bLon);

            // Also add reversed tuple — because DB may have stored either orientation
            $reversedTuples[] = "(?, ?, ?, ?)";
            array_push($bindingsReversed, $bLat, $bLon, $aLat, $aLon);
        }

        // Build raw SQL IN lists
        $inCanonical = implode(',', $canonicalTuples);
        $inReversed = implode(',', $reversedTuples);

        // Construct raw where clause:
        // (lat1, lon1, lat2, lon2) IN (...) OR (lat1, lon1, lat2, lon2) IN (...)
        $whereSql = " (lat1, lon1, lat2, lon2) IN ({$inCanonical}) OR (lat1, lon1, lat2, lon2) IN ({$inReversed}) ";

        // Flatten bindings (canonical then reversed)
        $bindings = array_merge($bindingsCanonical, $bindingsReversed);

        $dbRows = RoadSegment::whereRaw($whereSql, $bindings)->get();

        // Build a quick lookup by exact tuple (both orientations) for matching.
        $lookup = [];
        foreach ($dbRows as $r) {
            $k1 = "{$r->lat1},{$r->lon1},{$r->lat2},{$r->lon2}";
            $k2 = "{$r->lat2},{$r->lon2},{$r->lat1},{$r->lon1}";
            $lookup[$k1] = $r;
            $lookup[$k2] = $r;
        }

        // Prepare output in same order as input segments
        $result = [];
        foreach ($segments as $i => $s) {
            $keyA = "{$s['lat1']},{$s['lon1']},{$s['lat2']},{$s['lon2']}";
            $keyB = "{$s['lat2']},{$s['lon2']},{$s['lat1']},{$s['lon1']}";
            $found = $lookup[$keyA] ?? $lookup[$keyB] ?? null;

            $result[] = [
                'index' => $i,
                'maxspeed' => $found?->maxspeed ?? null
            ];
        }

        return response()->json($result);
    }

    /**
     * Save many segments in a single request.
     * Accepts JSON: { segments: [ { lat1, lon1, lat2, lon2, maxspeed }, ... ] }
     */
    public function saveRoadSegmentBatch(Request $request)
    {
        $rows = $request->input('segments', []);
        if (empty($rows)) {
            return response()->json(['saved' => 0]);
        }

        // We'll do updateOrCreate inside a transaction to avoid partial writes.
        DB::transaction(function () use ($rows) {
            foreach ($rows as $s) {
                // Cast to expected precision / types
                $lat1 = $s['lat1'];
                $lon1 = $s['lon1'];
                $lat2 = $s['lat2'];
                $lon2 = $s['lon2'];
                $maxspeed = isset($s['maxspeed']) ? $s['maxspeed'] : null;

                // Save canonical (attempt both orientations)
                RoadSegment::updateOrCreate([
                    'lat1' => $lat1, 'lon1' => $lon1,
                    'lat2' => $lat2, 'lon2' => $lon2,
                ], ['maxspeed' => $maxspeed]);

                // Also ensure the reversed orientation exists/updated (optional)
                // Some callers may query either orientation; to reduce misses we also write reversed.
                RoadSegment::updateOrCreate([
                    'lat1' => $lat2, 'lon1' => $lon2,
                    'lat2' => $lat1, 'lon2' => $lon1,
                ], ['maxspeed' => $maxspeed]);
            }
        });

        return response()->json(['saved' => count($rows)]);
    }
}
