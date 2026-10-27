{{--
    Decorative trend line for a KPI tile: one series, one hue, 2px line, ~10% area wash.
    The tile's own text carries the numbers, so the SVG is hidden from assistive tech.
    <x-sparkline :values="[1, 4, 2]" class="..." />
--}}
@props(['values' => [], 'width' => 120, 'height' => 40])

@php
    $values = array_values($values);
    $count = count($values);
    $max = $count ? max($values) : 0;
    $min = $count ? min(0, min($values)) : 0;
    $span = ($max - $min) ?: 1;
    $pad = 3;
    $points = collect($values)->map(function ($v, $i) use ($count, $width, $height, $min, $span, $pad) {
        $x = $count > 1 ? $i / ($count - 1) * $width : $width / 2;
        $y = $height - $pad - (($v - $min) / $span) * ($height - $pad * 2);

        return round($x, 1).','.round($y, 1);
    });
    $line = $points->implode(' ');
    $area = $count > 1 ? 'M0,'.$height.' L'.$points->implode(' L').' L'.$width.','.$height.' Z' : '';
@endphp

@if ($count > 1)
    <svg {{ $attributes->class('fw-spark') }} viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="{{ $area }}" class="fw-spark-area" />
        <polyline points="{{ $line }}" class="fw-spark-line" vector-effect="non-scaling-stroke" />
    </svg>
@endif
