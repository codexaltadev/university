{{-- A project's or product's page (FR-LAB-10). Contract: $lab, $item --}}
<x-theme::layout :title="$item->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ rtrim((string) $lab->url(), '/').'/'.\Modules\Lab\Models\LabItem::SEGMENTS[$item->item_type] }}">← {{ __('lab::lab.types_plural.'.$item->item_type) }} — {{ $lab->name }}</a></p>
        <h1>{{ $item->title }}</h1>
        @if ($category = $item->termsIn(\Modules\Lab\Models\LabItem::vocabulary($item->item_type))->first())<p><span class="badge">{{ $category->name }}</span></p>@endif
        @if ($image = $item->firstMedia('image'))<img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: $item->title }}" style="width:100%;border-radius:var(--radius, 8px)">@endif
        @if (filled($item->student_names))<p style="margin-top:var(--space-4, 16px)"><strong>{{ __('lab::lab.fields.student_names') }}:</strong> {{ $item->student_names }}@if ($item->student_ids) ({{ $item->student_ids }})@endif</p>@endif
        <div class="prose" style="margin-top:var(--space-4, 16px)">{!! \Illuminate\Support\Str::sanitizeHtml((string) $item->description) !!}</div>
    </article>
</x-theme::layout>
