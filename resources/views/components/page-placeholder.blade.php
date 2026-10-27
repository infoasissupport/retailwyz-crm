{{-- Marks a region whose final design is pending. Remove when the page is designed. --}}
@props(['title' => 'Design pending'])

<div {{ $attributes->class(['border border-2 border-dashed rounded p-4 text-center text-muted']) }}>
    <p class="mb-1 fw-semibold">{{ $title }}</p>
    {{ $slot }}
</div>
