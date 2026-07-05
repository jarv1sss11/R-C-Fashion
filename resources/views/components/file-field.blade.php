@props([
    'label',
    'name',
    'id' => null,
    'multiple' => false,
    'accept' => 'image/*',
])

@php
    $fieldId = $id ?? $name;
@endphp

<div class="input-field">
    <label for="{{ $fieldId }}" class="input-field-label">{{ $label }}</label>

    <input
        type="file"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        id="{{ $fieldId }}"
        accept="{{ $accept }}"
        @if ($multiple) multiple @endif
        {{ $attributes->merge(['class' => 'input-field-file']) }}
    >

    @error($name)
        <p class="input-field-error">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="input-field-error">{{ $message }}</p>
    @enderror
</div>
