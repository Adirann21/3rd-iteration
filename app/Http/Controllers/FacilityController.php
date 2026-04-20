<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facility;

class FacilityController extends Controller
{
    /**
     * Return a JSON list of active facility names.
     * Used by the reservation form or frontend dropdowns when selecting a room.
     */
    public function list()
    {
        $facilities = Facility::active()->get(['name']);
        return response()->json($facilities);
    }
}
