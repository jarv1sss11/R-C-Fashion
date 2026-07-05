@props(['title', 'message' => null])

<div class="empty-state">
    <p class="empty-state-title">{{ $title }}</p>
    @if ($message)
        <p class="empty-state-message">{{ $message }}</p>
    @endif
    @isset($slot)
        @if (trim($slot))
            <div class="empty-state-action">{{ $slot }}</div>
        @endif
    @endisset
</div>
