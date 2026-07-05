@props(['name'])

@php
    $paths = [
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'hanger' => '<path d="M12 3a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V8l8 5c1 .6 1 2-.2 2.4L12 18l-8.8-2.6C2 15 2 13.4 3 12.8l8-5V6.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2Z"/><line x1="4" y1="20" x2="20" y2="20"/>',
        'badge-check' => '<path d="M12 2 14.5 4.5 18 4 18.5 7.5 21.5 9 20 12.3 21.5 15.6 18.5 17 18 20.5 14.5 20 12 22.5 9.5 20 6 20.5 5.5 17 2.5 15.6 4 12.3 2.5 9 5.5 7.5 6 4 9.5 4.5 12 2Z"/><polyline points="8.5 12 11 14.5 15.5 9.5"/>',
        'shield' => '<path d="M12 2 4 5v6c0 5 3.4 8.7 8 11 4.6-2.3 8-6 8-11V5l-8-3Z"/><polyline points="8.5 12 11 14.5 15.5 9.5"/>',
        'truck' => '<rect x="1.5" y="7" width="13" height="9"/><path d="M14.5 10.5H18l3.5 3.5V16h-7z"/><circle cx="5.5" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/>',
        'mail' => '<rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="m3 6 9 6.5L21 6"/>',
        'eye' => '<path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18"/><path d="M10.6 5.2A10.4 10.4 0 0 1 12 5c7 0 10.5 7 10.5 7a15.6 15.6 0 0 1-3.4 4.3M6.6 6.6C3.4 8.6 1.5 12 1.5 12s3.5 7 10.5 7a10.7 10.7 0 0 0 4.6-1"/><path d="M9.5 9.8a3 3 0 0 0 4.2 4.2"/>',
        'person-plus' => '<circle cx="9.5" cy="7.5" r="3.5"/><path d="M2.5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/>',
        'shopping-bag' => '<path d="M6 8V6.5a5 5 0 0 1 10 0V8"/><rect x="3.5" y="8" width="17" height="13" rx="2"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'storefront' => '<path d="M3.5 9 5 4h13.5L20 9"/><path d="M4 9.5v10h16v-10"/><path d="M4 9.5a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M10 19.5v-5.5h4v5.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.3" y2="16.3"/>',
        'heart' => '<path d="M12 20.5s-7.5-4.7-10-9.3C.4 8 2 4.5 5.5 4.5c2 0 3.5 1 6.5 4 3-3 4.5-4 6.5-4C22 4.5 23.6 8 22 11.2c-2.5 4.6-10 9.3-10 9.3Z"/>',
    ];
    $path = $paths[$name] ?? '';
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    {!! $path !!}
</svg>
