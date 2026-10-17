{{-- DIU's Counter: the number counts up once it is seen; without JavaScript it is simply shown. <x-theme::counter :value="5600" suffix="+" /> --}}
@props(['value' => 0, 'suffix' => ''])
<span x-data="counter({{ (int) $value }}, @js((string) $suffix))" x-intersect.once="run()" x-text="text">{{ number_format((int) $value) }}{{ $suffix }}</span>
