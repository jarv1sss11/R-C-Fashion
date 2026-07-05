@props(['items'])

<nav class="breadcrumb" aria-label="Breadcrumb">
    @foreach ($items as $index => $item)
        @if (! $loop->last && ! empty($item['href']))
            <a href="{{ $item['href'] }}" class="breadcrumb-link">{{ $item['label'] }}</a>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
        @else
            <span class="breadcrumb-current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
