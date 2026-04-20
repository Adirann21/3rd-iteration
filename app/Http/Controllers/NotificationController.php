<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        // Load the current user's notifications, paginated for display.
        $notifications = auth()->user()->notifications()->latest()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request)
    {
        // Mark all unread notifications as read for the current user.
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function destroySelected(Request $request)
    {
        // Remove the selected notifications from the database.
        // If a notification refers to a pending reservation approval request,
        // also delete the associated reservation to keep state consistent.
        $request->validate([
            'selected_notifications' => 'array',
            'selected_notifications.*' => 'string',
        ]);

        $selectedIds = $request->input('selected_notifications', []);
        $notifications = auth()->user()->notifications()->whereIn('id', $selectedIds)->get();

        foreach ($notifications as $notification) {
            if ($notification->type === \App\Notifications\ReservationPendingApproval::class
                && ! empty($notification->data['reservation_id'])) {
                $reservation = Reservation::where('id', $notification->data['reservation_id'])
                    ->where('user_id', auth()->id())
                    ->first();

                if ($reservation && in_array($reservation->status, ['pending', 'approved', 'rejected'])) {
                    $reservation->delete();
                }
            }

            $notification->delete();
        }

        return back()->with('success', 'Selected notifications were removed successfully. Pending reservation requests were also cleared.');
    }
}
