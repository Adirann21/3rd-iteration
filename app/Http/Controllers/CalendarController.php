<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\ReservationPendingApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CalendarController extends Controller
{
    // Fetch reservations as events for FullCalendar
    public function events(Request $request)
    {
        // This endpoint returns JSON event objects for the calendar UI.
        // It optionally filters by facility name if provided in the query string.
        $facilityName = $request->query('facility');
        $query = Reservation::with('facility');
        if ($facilityName) {
            $query->whereHas('facility', function ($q) use ($facilityName) {
                $q->where('name', $facilityName);
            });
        }
        $reservations = $query->get();
        $events = $reservations->map(function ($reservation) {
            // Ensure proper ISO 8601 format for FullCalendar
            $dateStr = $reservation->reservation_date->format('Y-m-d');
            // Get the time values properly - format from the datetime casts
            $startTime = $reservation->start_time->format('H:i:00');
            $endTime = $reservation->end_time->format('H:i:00');
            $start = $dateStr . 'T' . $startTime;
            $end = $dateStr . 'T' . $endTime;
            return [
                'id' => $reservation->id,
                'title' => $reservation->facility->name,
                'start' => $start,
                'end' => $end,
                'color' => $this->getReservationColor($reservation->id),
                'extendedProps' => [
                    'purpose' => $reservation->purpose,
                    'status' => $reservation->status,
                    'room' => $reservation->facility->name,
                    'date' => $dateStr,
                ],
            ];
        });
        return response()->json($events);
    }

    // Store a new reservation
    public function store(Request $request)
    {
        // Validate the reservation details submitted from the frontend.
        // If validation passes, the reservation is stored as pending and admins are notified.
        $request->validate([
            'facility' => 'required|string',
            'purpose' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        $facility = Facility::where('name', $request->facility)->firstOrFail();
        if (!$facility->isAvailable($request->date, $request->start_time, $request->end_time)) {
            return response()->json(['error' => 'Time slot not available'], 409);
        }
        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'facility_id' => $facility->id,
            'reservation_date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'status' => 'pending',
        ]);

        $admins = User::where('is_admin', true)
            ->orWhere('is_super_admin', true)
            ->get();

        Notification::send($admins, new ReservationPendingApproval($reservation));

        return response()->json(['success' => true, 'reservation' => $reservation]);
    }

    // Generate a unique, distinct color for each reservation based on its ID
    private function getReservationColor($reservationId)
    {
        // Palette of 24 distinct, non-duplicating colors with good contrast
        $colors = [
            '#FF6B6B',  // Vibrant Red
            '#4ECDC4',  // Turquoise
            '#45B7D1',  // Sky Blue
            '#FFA07A',  // Light Salmon
            '#98D8C8',  // Mint
            '#F7DC6F',  // Golden Yellow
            '#BB8FCE',  // Purple
            '#85C1E2',  // Cornflower Blue
            '#F8B88B',  // Peach
            '#A8E6CF',  // Light Green
            '#FFD3B6',  // Light Orange
            '#FF8B94',  // Rose
            '#A8D8EA',  // Powder Blue
            '#AA96DA',  // Lavender
            '#FCBAD3',  // Pink
            '#B4A7D6',  // Soft Purple
            '#73A580',  // Sage Green
            '#F0A202',  // Deep Gold
            '#E76F51',  // Burnt Orange
            '#2A9D8F',  // Teal
            '#E9C46A',  // Sandy Yellow
            '#F4A261',  // Orange
            '#D62828',  // Deep Red
            '#1D3557',  // Navy Blue
        ];
        
        // Use modulo to cycle through colors based on reservation ID
        $colorIndex = (intval($reservationId) % count($colors));
        return $colors[$colorIndex];
    }
}

