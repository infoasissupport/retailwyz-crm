@props(['name', 'label' => null, 'value' => null, 'rows' => 3])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}"
              {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($key)]) }}>{{ old($key, $value) }}</textarea>
    @error($key)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
