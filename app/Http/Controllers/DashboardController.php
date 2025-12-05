<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusStop;

class DashboardController extends Controller
{
    // Main dashboard view
    public function user()
    {
        return view('dashboard.user');
    }

    // AJAX search endpoint
    public function searchStops(Request $request)
    {
        $query = $request->input('query', '');
        $page = $request->input('page', 1);

        $stops = BusStop::query()
            ->when($query !== '', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('stop_code', 'like', "%{$query}%")
                  ->orWhere('street', 'like', "%{$query}%")
                  ->orWhere('municipality', 'like', "%{$query}%")
                  ->orWhere('road_side', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->paginate(25, ['*'], 'page', $page);

        return response()->json($stops);
    }
}
