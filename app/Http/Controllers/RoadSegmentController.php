<?php

namespace App\Http\Controllers;

use App\Models\RoadSegment;
use Illuminate\Http\Request;

class RoadSegmentController extends Controller
{
    // List segments
    public function index()
    {
        $segments = RoadSegment::paginate(50);
        $segmentsForMap = RoadSegment::all();

        return view('road_segments.index', compact('segments', 'segmentsForMap'));
    }

    // Show edit form
    public function edit(RoadSegment $roadSegment)
    {
        return view('road_segments.edit', compact('roadSegment'));
    }

    // Update via form
    public function update(Request $request, RoadSegment $roadSegment)
    {
        $data = $request->validate([
            'lat1'=>'required|numeric',
            'lon1'=>'required|numeric',
            'lat2'=>'required|numeric',
            'lon2'=>'required|numeric',
            'maxspeed'=>'required|integer|min:0|max:300'
        ]);

        $roadSegment->update($data);

        return redirect()->route('road-segments.index')->with('success','Road segment updated successfully.');
    }

    // Save batch changes via JS
    public function saveBatch(Request $request)
    {
        $segments = $request->input('segments', []);

        foreach($segments as $seg){
            RoadSegment::where('id',$seg['id'])->update([
                'maxspeed' => $seg['maxspeed'],
                'updated_at'=>now(),
            ]);
        }

        return response()->json(['success'=>true]);
    }
}
