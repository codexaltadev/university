{{-- A person's round photo in DIU's administration list, or their initial on a soft circle. Contract: $row (name, photo), $size (classes) --}}
<div class="relative flex-shrink-0 {{ $size }}">
    @if ($row['photo'])
        <img src="{{ $row['photo'] }}" alt="{{ $row['name'] }}" loading="lazy" class="w-full h-full object-cover rounded-full border-2 border-gray-200">
    @else
        <span class="w-full h-full rounded-full border-2 border-gray-200 bg-blue-50 text-primary flex items-center justify-center" aria-hidden="true"><x-theme::icon name="user" class="w-1/2 h-1/2" /></span>
    @endif
</div>
