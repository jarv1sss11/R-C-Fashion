@props([
    'label',
    'name',
    'checked' => false,
])

{{--
    Hidden "0" field ensures unchecking this box and submitting still sends
    a value for `name` — without it, an unchecked checkbox is simply absent
    from the request, so `$request->validated()` can't distinguish "user
    unchecked this" from "this field was never part of the form," which
    silently breaks toggling a boolean off on an edit form.
--}}
<input type="hidden" name="{{ $name }}" value="0">
<label for="{{ $name }}" class="checkbox">
    <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" class="checkbox-input" @checked($checked) {{ $attributes }}>
    <span class="checkbox-label">{{ $label }}</span>
</label>
