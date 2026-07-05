@props([
    'label',
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
])

@php
    $fieldId = $id ?? $name;
    $selected = old($name, $value);
@endphp

<div class="input-field">
    <label for="{{ $fieldId }}" class="input-field-label">{{ $label }}</label>

    <div class="input-field-control">
        <select
            name="{{ $name }}"
            id="{{ $fieldId }}"
            {{ $attributes->merge(['class' => 'input-field-input']) }}
        >
            @if ($placeholder)
                <option value="" @selected(!$selected)>{{ $placeholder }}</option>
            @endif

            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    </div>

    @error($name)
        <p class="input-field-error">{{ $message }}</p>
    @enderror
</div>
