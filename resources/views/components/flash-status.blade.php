@if (session('status'))
    <p class="flash-status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <div class="flash-status flash-status--error">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
