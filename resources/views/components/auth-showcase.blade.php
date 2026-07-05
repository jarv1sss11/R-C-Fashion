@php
    $images = ['login-register-1.jpg', 'login-register-2.jpg', 'login-register-3.jpg'];
@endphp

<div class="auth-showcase" aria-hidden="true">
    @foreach ($images as $index => $image)
        @php
            $imagePath = public_path('images/editorial/auth/'.$image);
            $hasImage = file_exists($imagePath);
            $tone = $index + 1;
        @endphp

        <div class="auth-showcase-item {{ ! $hasImage ? 'editorial-card--tone-'.$tone : '' }}">
            @if ($hasImage)
                <img src="{{ asset('images/editorial/auth/'.$image) }}" alt="" class="editorial-card-image">
            @endif
        </div>
    @endforeach
</div>
