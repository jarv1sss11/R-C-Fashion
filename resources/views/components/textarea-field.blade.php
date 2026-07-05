@props([
    'label',
    'name',
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 4,
])

@php
    $fieldId = $id ?? $name;
@endphp

<div class="input-field">
    <label for="{{ $fieldId }}" class="input-field-label">{{ $label }}</label>

    <div class="input-field-control">
        <textarea
            name="{{ $name }}"
            id="{{ $fieldId }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'input-field-input input-field-textarea']) }}
        >{{ old($name, $value) }}</textarea>
    </div>

    @error($name)
        <p class="input-field-error">{{ $message }}</p>
    @enderror
</div>
