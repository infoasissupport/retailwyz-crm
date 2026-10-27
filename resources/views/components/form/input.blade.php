{{-- <x-form.input name="sku" label="SKU" :value="$product->sku" required /> --}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           value="{{ $type === 'password' ? '' : old($key, $value) }}"
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($key)]) }}>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($key)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
