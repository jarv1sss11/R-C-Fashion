<x-layouts.app title="Notifications — R&C Fashion">
    <x-navbar variant="full" />

    <main class="catalog">
        <div class="container catalog-inner" style="max-width:760px;">
            <x-breadcrumb :items="[
                ['label' => 'Home', 'href' => route('home')],
                ['label' => 'Notifications'],
            ]" />

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                <h1 class="catalog-heading" style="margin:0;">Notifications</h1>
                @if(auth()->user()->unreadNotifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-secondary btn-sm">Mark all read</button>
                </form>
                @endif
            </div>

            <x-flash-status />

            @forelse($notifications as $notification)
            @php $data = $notification->data; $isUnread = is_null($notification->read_at); @endphp
            <div class="notification-item {{ $isUnread ? 'notification-item--unread' : '' }}">
                <div class="notification-body">
                    <p class="notification-title">{{ $data['title'] ?? 'Notification' }}</p>
                    <p class="notification-message">{{ $data['message'] ?? '' }}</p>
                    <p class="notification-time">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <div class="notification-actions">
                    @if(isset($data['url']))
                    <a href="{{ $data['url'] }}" class="btn btn-secondary btn-sm">View</a>
                    @endif
                    @if($isUnread)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost btn-sm">Mark read</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <x-empty-state title="No notifications yet" message="You'll see order updates, payment confirmations, and delivery alerts here." />
            @endforelse

            <x-pagination :paginator="$notifications" />
        </div>
    </main>
</x-layouts.app>
