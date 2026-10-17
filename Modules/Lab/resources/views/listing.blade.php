{{-- /labs and /{faculty}/{department}/labs (FR-LAB-05). Contract: $labs, $types (Term list), $type, $departments, $department (id), $q, $layout (grid / list / cards) --}}
<x-theme::layout :title="__('lab::lab.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('lab::lab.plural') }}</h1>
        <form method="get" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('lab::lab.search') }}" aria-label="{{ __('lab::lab.search') }}" style="flex:1 1 220px;padding:8px 12px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
            @if ($types->count() > 1)
                <select name="type" aria-label="{{ __('lab::lab.fields.type') }}" style="padding:8px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                    <option value="">{{ __('lab::lab.all_types') }}</option>
                    @foreach ($types as $t)<option value="{{ $t->slug() }}" @selected($type?->is($t))>{{ $t->name }}</option>@endforeach
                </select>
            @endif
            @if ($departments->count() > 1)
                <select name="department" aria-label="{{ __('lab::lab.fields.department') }}" style="padding:8px;border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                    <option value="">{{ __('lab::lab.all_departments') }}</option>
                    @foreach ($departments as $d)<option value="{{ $d->getKey() }}" @selected($department === $d->getKey())>{{ $d->short_name ?: $d->name }}</option>@endforeach
                </select>
            @endif
            <input type="hidden" name="view" value="{{ $layout }}">
            <button class="button" type="submit">{{ __('lab::lab.go') }}</button>
        </form>
        @if ($labs->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('lab::lab.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax('.($layout === 'cards' ? '300px' : '240px').',1fr))' }}">
                @foreach ($labs as $lab)
                    @php($image = $layout === 'cards' ? ($lab->firstMedia('cover') ?? $lab->firstMedia('logo')) : $lab->firstMedia('logo'))
                    <a href="{{ $lab->url() }}" class="lab-card lab-card--{{ $layout }}" style="display:{{ $layout === 'list' ? 'flex' : 'block' }};gap:var(--space-3, 12px);align-items:center;border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden;color:inherit;text-decoration:none;{{ $layout === 'list' ? 'padding:var(--space-3, 12px)' : '' }}">
                        @if ($image)<img src="{{ $image->url($layout === 'cards' ? 'medium' : 'thumb') ?? $image->url() }}" alt="" loading="lazy" style="{{ $layout === 'cards' ? 'width:100%;aspect-ratio:16/9;object-fit:cover' : ($layout === 'list' ? 'width:48px;height:48px;object-fit:contain' : 'width:64px;height:64px;object-fit:contain;margin:var(--space-4, 16px) var(--space-4, 16px) 0') }}">@endif
                        <div style="{{ $layout === 'list' ? '' : 'padding:var(--space-4, 16px)' }}">
                            <h2 style="margin:0 0 4px;font-size:1.1rem">{{ $lab->name }}</h2>
                            @if ($t = $lab->termsIn(\Modules\Lab\Models\Lab::TYPE)->first())<span style="font-size:.8rem;opacity:.75">{{ $t->name }}</span>@endif
                            @if (filled($lab->summary) && $layout !== 'list')<p style="margin:4px 0 0">{{ \Illuminate\Support\Str::limit((string) $lab->summary, 140) }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
