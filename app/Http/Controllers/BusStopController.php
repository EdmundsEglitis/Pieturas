<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusStop;

class BusStopController extends Controller
{
    // Show the search form
    public function searchForm()
    {
        return view('busstop.search');
    }

    // Handle search results
public function searchStops(Request $request)
{
    $query = $request->input('query', '');
    $sortBy = $request->input('sort_by', 'name');
    $sortOrder = $request->input('sort_order', 'asc');
    $page = $request->input('page', 1);
    $expanded = $request->boolean('expanded', false);

    $stopsQuery = BusStop::query()
        ->when($query !== '', function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('stop_code', 'like', "%{$query}%")
              ->orWhere('street', 'like', "%{$query}%")
              ->orWhere('municipality', 'like', "%{$query}%")
              ->orWhere('road_side', 'like', "%{$query}%");
        })
        ->orderBy($sortBy, $sortOrder);

    if ($expanded) {
        $stops = $stopsQuery->get(); // ALL rows for expanded table
    } else {
        $stops = $stopsQuery->paginate(25, ['*'], 'page', $page);
    }

    return response()->json($stops);
}

}
