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
    public function searchResults(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $query = $request->input('query');

        $stops = BusStop::where('name', 'like', "%{$query}%")
            ->orWhere('stop_code', 'like', "%{$query}%")
            ->orWhere('street', 'like', "%{$query}%")
            ->orWhere('municipality', 'like', "%{$query}%")
            ->get();

        return view('busstop.results', compact('stops', 'query'));
    }
}
