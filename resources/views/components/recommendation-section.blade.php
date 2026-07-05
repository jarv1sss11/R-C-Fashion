@props(['title', 'results', 'module' => 'recommendations', 'emptyMessage' => null])

@if (count($results) > 0)
    <section class="recommendation-section">
        <h2 class="recommendation-section-title">{{ $title }}</h2>

        <div class="recommendation-grid">
            @foreach ($results as $result)
                <x-recommendation-card :result="$result" :module="$module" />
            @endforeach
        </div>
    </section>
@elseif ($emptyMessage)
    <section class="recommendation-section">
        <h2 class="recommendation-section-title">{{ $title }}</h2>
        <p class="recommendation-section-empty">{{ $emptyMessage }}</p>
    </section>
@endif
