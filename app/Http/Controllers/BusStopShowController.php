<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

    public function batchRoadSpeeds(Request $request)
    {
        $segments = $request->input('segments', []);
        $result = [];

        if(empty($segments)) return response()->json($result);

        $dbSegments = RoadSegment::query();
        foreach($segments as $seg){
            $dbSegments->orWhere(function($q) use($seg){
                $q->where([['lat1',$seg['lat1']],['lon1',$seg['lon1']],['lat2',$seg['lat2']],['lon2',$seg['lon2']]])
                  ->orWhere(fn($q2) => $q2->where([['lat1',$seg['lat2']],['lon1',$seg['lon2']],['lat2',$seg['lat1']],['lon2',$seg['lon1']]]));
            });
        }
        $dbSegments = $dbSegments->get();

        foreach($segments as $seg){
            $found = $dbSegments->first(fn($r) => 
                ($r->lat1==$seg['lat1'] && $r->lon1==$seg['lon1'] && $r->lat2==$seg['lat2'] && $r->lon2==$seg['lon2'])
                || ($r->lat1==$seg['lat2'] && $r->lon1==$seg['lon2'] && $r->lat2==$seg['lat1'] && $r->lon2==$seg['lon1'])
            );
            $result[] = ['lat1'=>$seg['lat1'],'lon1'=>$seg['lon1'],'lat2'=>$seg['lat2'],'lon2'=>$seg['lon2'],'maxspeed'=>$found?->maxspeed];
        }

        return response()->json($result);
    }
}
