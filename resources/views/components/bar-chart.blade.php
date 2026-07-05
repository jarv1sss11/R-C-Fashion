@props(['title', 'data' => [], 'unit' => null])

@php
    $max = max(array_merge([1], array_values($data)));
@endphp

<div class="bar-chart">
    <h3 class="bar-chart-title">{{ $title }}</h3>

    @if (empty($data))
        <p class="bar-chart-empty">No data yet.</p>
    @else
        <div class="bar-chart-bars">
            @foreach ($data as $label => $value)
                <div class="bar-chart-bar-wrap">
                    <span class="bar-chart-value">{{ $unit }}{{ number_format($value, $value == floor($value) ? 0 : 2) }}</span>
                    <div class="bar-chart-bar-track">
                        <div class="bar-chart-bar" style="--bar-height: {{ $max > 0 ? max(2, round($value / $max * 100)) : 0 }}%"></div>
                    </div>
                    <span class="bar-chart-label">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
