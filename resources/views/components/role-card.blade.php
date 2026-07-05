@props([
    'role',
    'variant' => 'light',
    'icon',
    'title',
    'sublabel',
])

<button
    type="button"
    class="role-card role-card--{{ $variant }}"
    data-role-card="{{ $role }}"
>
    <span class="role-card-icon">
        <x-icon :name="$icon" />
    </span>
    <span class="role-card-title">{{ $title }}</span>
    <span class="role-card-sublabel">{{ $sublabel }}</span>
</button>
