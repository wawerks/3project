<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\FoundItem;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Get counts
            $totalUsers = User::count();
            $lostItems = LostItem::count();
            $foundItems = FoundItem::count();
            $claims = Claim::count();

            // Get recent items
            $recentLostItems = LostItem::with('user')
                ->latest()
                ->take(5)
                ->get();

            $recentFoundItems = FoundItem::with('user')
                ->latest()
                ->take(5)
                ->get();

            $recentClaims = Claim::with(['user', 'foundItem'])
                ->latest()
                ->take(5)
                ->get();

            $data = [
                'totalUsers' => $totalUsers,
                'lostItems' => $lostItems,
                'foundItems' => $foundItems,
                'claims' => $claims,
                'recentLostItems' => $recentLostItems,
                'recentFoundItems' => $recentFoundItems,
                'recentClaims' => $recentClaims,
            ];

            if (request()->wantsJson()) {
                return response()->json($data);
            }

            return Inertia::render('Dashboard', $data);

        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Failed to fetch dashboard data'], 500);
            }

            return back()->with('error', 'Failed to load dashboard data');
        }
    }
}
