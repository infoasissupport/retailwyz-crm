{{-- <x-form.select name="category_id" label="Category" :options="$categories" :value="$product->category_id" placeholder="Select..." /> --}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $selected = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($key)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($key)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
