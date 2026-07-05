<x-layouts.app :title="($title ?? 'Coming soon') . ' — R&C Fashion'">
    <x-navbar variant="minimal" />

    <main class="stub">
        <div class="container stub-inner">
            <h1 class="stub-title">{{ $title ?? 'This page is on its way' }}</h1>
            <p class="stub-text">
                {{ $message ?? 'This is a placeholder route only.' }}
            </p>
            <a href="{{ route('home') }}" class="btn btn-outline">Back to Home</a>
        </div>
    </main>
</x-layouts.app>
