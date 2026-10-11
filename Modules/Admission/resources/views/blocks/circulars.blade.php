{{-- Admission circulars block (FR-ADM-15). Contract: $data (running_only, limit), $locale --}}
@php($circulars = \Modules\Admission\Models\Circular::query()->frontendVisible()->when($data['running_only'] ?? true, fn ($q) => $q->running())->ordered()->get()->sortBy(fn ($c) => $c->isClosed() ? 1 : 0)->take((int) ($data['limit'] ?? 3) ?: 3))
<div class="block block--admission-circulars" style="display:grid;gap:var(--space-6, 24px)">
    @forelse ($circulars as $circular)
        @include('admission::partials.circular-card', ['circular' => $circular])
    @empty
        <p>{{ __('admission::admission.no_circulars') }}</p>
    @endforelse
    <p><a href="{{ url('/'.\Modules\Admission\Models\Circular::PATH) }}">{{ __('admission::admission.all_circulars') }}</a></p>
</div>
