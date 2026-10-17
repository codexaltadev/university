{{-- One lab item card. Contract: $item (LabItem) --}}
@php($image = $item->firstMedia('image'))
@php($href = $item->url())
<article class="lab-item lab-item--{{ $item->item_type }}" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden;height:100%">
    @if ($image)<img src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $image->alt_text ?: $item->title }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover">@endif
    <div style="padding:var(--space-4, 16px)">
        <h3 style="margin:0 0 4px;font-size:1.05rem">@if ($href)<a href="{{ $href }}">{{ $item->title }}</a>@else{{ $item->title }}@endif</h3>
        @if ($item->item_type === 'equipment' && $item->quantity)<p style="margin:0;font-size:.85rem;opacity:.8">{{ __('lab::lab.quantity', ['count' => $item->quantity]) }}</p>@endif
        @if ($item->item_type === 'project' && filled($item->student_names))<p style="margin:0;font-size:.85rem;opacity:.8">{{ $item->student_names }}</p>@endif
        @if (filled($item->description) && ! $href)<div class="prose" style="font-size:.9rem">{!! \Illuminate\Support\Str::sanitizeHtml((string) $item->description) !!}</div>@endif
    </div>
</article>
