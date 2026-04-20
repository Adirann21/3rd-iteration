<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Notifications\ReservationStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    private function requireAdmin()
    {
        // Ensure the current user is logged in and has admin privileges.
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403, 'Admin access only.');
        }
    }

    private function requireSuperAdmin()
    {
        // Ensure the current user is the head admin before allowing super-admin actions.
        if (! Auth::check() || ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Head admin access only.');
        }
    }

    public function dashboard(Request $request)
    {
        // Admin dashboard shows pending approvals, recent actions, and user search.
        $this->requireAdmin();

        $pendingReservations = Reservation::with(['user', 'facility'])
            ->where('status', 'pending')
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->get();

        $recentReservations = Reservation::with(['user', 'facility'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();

        $auditLogs = AuditLog::with(['user', 'reservation'])
            ->latest()
            ->take(25)
            ->get();

        $search = trim($request->get('search', ''));
        $users = \App\Models\User::query();

        if ($search !== '') {
            $users->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");

                if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
                    $query->orWhere('email_hash', hash('sha256', strtolower($search)));
                }
            });
        }

        $users = $users->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.dashboard', compact('pendingReservations', 'recentReservations', 'auditLogs', 'users', 'search'));
    }

    public function approve(Request $request, Reservation $reservation)
    {
        // Approve a pending reservation and notify the requestor.
        $this->requireAdmin();

        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Only pending reservations can be approved.');
        }

        $reservation->update([
            'status' => 'approved',
            'admin_remarks' => $request->input('remarks', 'Approved by admin.'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'approved',
            'details' => 'Reservation approved by admin',
        ]);

        if ($reservation->user) {
            $reservation->user->notify(new ReservationStatusChanged($reservation));
        }

        return back()->with('status', 'Reservation approved successfully.');
    }

    public function reject(Request $request, Reservation $reservation)
    {
        // Reject a pending reservation and notify the user of the rejection.
        $this->requireAdmin();

        if ($reservation->status !== 'pending') {
            return back()->with('error', 'Only pending reservations can be rejected.');
        }

        $reservation->update([
            'status' => 'rejected',
            'admin_remarks' => $request->input('remarks', 'Rejected by admin.'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'rejected',
            'details' => $request->input('remarks', 'Reservation rejected by admin'),
        ]);

        if ($reservation->user) {
            $reservation->user->notify(new ReservationStatusChanged($reservation));
        }

        return back()->with('status', 'Reservation rejected successfully.');
    }

    public function destroyReservation(Request $request, Reservation $reservation)
    {
        $this->requireAdmin();

        $reservation->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => $reservation->id,
            'action' => 'deleted',
            'details' => 'Reservation deleted by admin',
        ]);

        return back()->with('status', 'Reservation deleted successfully.');
    }

    public function updateUserRole(Request $request, \App\Models\User $user)
    {
        // Only the head admin can change user roles, including admin promotion/demotion.
        $this->requireSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot change head admin role.');
        }

        $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user->update([
            'is_admin' => $request->input('role') === 'admin',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'role_changed',
            'details' => sprintf('Changed role for user %s to %s', $user->email, $request->input('role')),
        ]);

        return back()->with('status', 'User role updated successfully.');
    }

    public function destroyUser(Request $request, \App\Models\User $user)
    {
        // Delete a user account. Only the head admin may perform this action.
        $this->requireSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Cannot delete the head admin account.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account here.');
        }

        $user->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'reservation_id' => null,
            'action' => 'user_deleted',
            'details' => sprintf('Deleted user %s', $user->email),
        ]);

        return back()->with('status', 'User deleted successfully.');
    }
}
