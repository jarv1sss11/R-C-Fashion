@props([
    'label',
    'type' => 'text',
    'name',
    'id' => null,
    'icon' => null,
    'placeholder' => null,
    'value' => null,
])

@php
    $isPassword = $type === 'password';
    $fieldId = $id ?? $name;
@endphp

<div class="input-field">
    <label for="{{ $fieldId }}" class="input-field-label">{{ $label }}</label>

    <div class="input-field-control">
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            placeholder="{{ $placeholder }}"
            value="{{ old($name, $value) }}"
            {{ $attributes->merge(['class' => 'input-field-input']) }}
        >

        @if ($isPassword)
            <button type="button" class="input-field-toggle" data-password-toggle aria-label="Show password">
                <x-icon name="eye" class="input-field-icon input-field-icon--show" />
                <x-icon name="eye-off" class="input-field-icon input-field-icon--hide" />
            </button>
        @elseif ($icon)
            <x-icon :name="$icon" class="input-field-icon input-field-icon--static" />
        @endif
    </div>

    @error($name)
        <p class="input-field-error">{{ $message }}</p>
    @enderror
</div>
