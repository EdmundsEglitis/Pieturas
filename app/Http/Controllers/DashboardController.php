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
        $sort_by = $request->input('sort_by', 'id');
        $sort_order = $request->input('sort_order', 'asc');

        // Validate sort column
        $allowedSorts = [
            'id','name','stop_code','unified_code','latitude','longitude','road_side',
            'road_number','street','municipality','parish','village','created_at','updated_at'
        ];

        if (!in_array($sort_by, $allowedSorts)) {
            $sort_by = 'id';
        }
        $sort_order = $sort_order === 'desc' ? 'desc' : 'asc';

        $stops = BusStop::query()
            ->when($query !== '', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('stop_code', 'like', "%{$query}%")
                  ->orWhere('unified_code', 'like', "%{$query}%")
                  ->orWhere('latitude', 'like', "%{$query}%")
                  ->orWhere('longitude', 'like', "%{$query}%")
                  ->orWhere('road_side', 'like', "%{$query}%")
                  ->orWhere('road_number', 'like', "%{$query}%")
                  ->orWhere('street', 'like', "%{$query}%")
                  ->orWhere('municipality', 'like', "%{$query}%")
                  ->orWhere('parish', 'like', "%{$query}%")
                  ->orWhere('village', 'like', "%{$query}%");
            })
            ->orderBy($sort_by, $sort_order)
            ->paginate(25, ['*'], 'page', $page);

        return response()->json($stops);
    }
}
