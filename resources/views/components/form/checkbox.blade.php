{{-- Sends 0 when unchecked so boolean fields can be switched off. --}}
@props(['name', 'label', 'checked' => false])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="form-check">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
           @checked(old($key, $checked))
           {{ $attributes->except('id')->class(['form-check-input', 'is-invalid' => $errors->has($key)]) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @error($key)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
