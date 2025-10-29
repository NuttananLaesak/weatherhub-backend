<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Location;

class LocationController extends Controller
{
    public function index()
    {
        return response()->json(Location::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'timezone' => 'required|string'
        ]);

        $location = Location::create($request->all());

        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);
        $location->update($request->all());
        return response()->json($location);
    }
}
