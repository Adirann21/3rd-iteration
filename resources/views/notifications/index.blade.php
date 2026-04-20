@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-73px)] bg-slate-50 py-10 px-4 md:px-8 xl:px-16">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Notifications</h1>
                <p class="mt-2 text-sm text-slate-600">Review your recent alerts and mark them as read or remove selected messages.</p>
            </div>
            <!-- Actions for notification batch processing: mark all read or remove selected. -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <label class="inline-flex items-center text-sm text-slate-600">
                    <input id="selectAllNotifications" type="checkbox" class="mr-2 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                    Select all
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('notifications.markRead') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                            Mark all as read
                        </button>
                    </form>
                    <button type="submit" form="notificationsDeleteForm" class="inline-flex items-center justify-center rounded-full bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Remove selected
                    </button>
                </div>
            </div>
        </div>

        @if($notifications->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600 shadow-sm">
                <p class="text-lg font-medium">No notifications yet.</p>
                <p class="mt-2 text-sm">You will receive reservation approval or rejection updates here.</p>
            </div>
        @else
            <form id="notificationsDeleteForm" method="POST" action="{{ route('notifications.destroySelected') }}">
                @csrf
                <div class="space-y-4">
                    @foreach($notifications as $notification)
                        <div class="rounded-3xl border p-5 shadow-sm {{ is_null($notification->read_at) ? 'border-indigo-200 bg-white' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                @php $isApprovalRequest = $notification->type === \App\Notifications\ReservationPendingApproval::class; @endphp
                                <div class="flex items-start gap-3">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="selected_notifications[]" value="{{ $notification->id }}" data-approval-request="{{ $isApprovalRequest ? '1' : '0' }}" class="notification-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                    </label>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                            @if($isApprovalRequest)
                                                <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] uppercase tracking-[0.2em] text-amber-900">Request</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $notification->data['message'] ?? '' }}</p>
                                    </div>
                                </div>
                            <div class="flex flex-col items-start gap-2 text-right text-xs text-slate-500 md:items-end">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                @if(isset($notification->data['status']))
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] uppercase tracking-[0.2em] text-slate-700">
                                        {{ ucfirst($notification->data['status']) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($notification->data['action_url']))
                            <div class="mt-4">
                                <a href="{{ $notification->data['action_url'] }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800">View details</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Notification page JavaScript to handle select-all, checkbox sync, and deletion confirmation.
    document.addEventListener('DOMContentLoaded', function() {
        var selectAll = document.getElementById('selectAllNotifications');
        var checkboxes = document.querySelectorAll('.notification-checkbox');
        var deleteForm = document.getElementById('notificationsDeleteForm');

        if (!deleteForm || checkboxes.length === 0) {
            return;
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        }

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (selectAll) {
                    selectAll.checked = Array.from(checkboxes).every(function(box) {
                        return box.checked;
                    });
                }
            });
        });

        deleteForm.addEventListener('submit', function(event) {
            var selectedCheckboxes = Array.from(checkboxes).filter(function(checkbox) {
                return checkbox.checked;
            });

            if (selectedCheckboxes.length === 0) {
                return;
            }

            var selectedRequestNotifications = selectedCheckboxes.filter(function(checkbox) {
                return checkbox.dataset.approvalRequest === '1';
            });

            var confirmationMessage = selectedRequestNotifications.length > 0
                ? 'You are deleting approval request notification(s). This may clear pending reservation requests. Do you want to continue?'
                : 'Delete selected notification(s)?';

            if (!confirm(confirmationMessage)) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush
@endsection
