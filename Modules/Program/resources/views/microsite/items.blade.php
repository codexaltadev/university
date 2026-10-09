{{--
    Program sub-item sections on the program page (FR-PROG-04..09, FR-PROG-13): majors grouped by
    category, program options, career paths and industries, facilitators, learning materials,
    memberships. Only active items; empty parts are left out. Each part shows its intro (section_intros).
    Contract: $entity (Program), $part = majors|variants|careers|people
--}}
@php
    $section = fn (string $anchor, string $title, ?string $style = null) => new \Illuminate\Support\Fluent(['anchor' => $anchor, 'title' => $title, 'subheading' => $entity->intro($anchor === 'careers' ? 'career_path' : $anchor), 'style' => $style]);
@endphp

@if ($part === 'majors')
    @php($majors = $entity->majors()->frontendVisible()->with('category')->get())
    @if ($majors->isNotEmpty())
        <x-theme::section :section="$section('majors', __('program::program.items.majors'))">
            @foreach ($majors->groupBy(fn ($m) => $m->category?->name ?? '') as $category => $group)
                @if ($category !== '') <h3>{{ $category }}</h3> @endif
                <div class="program-majors" style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));margin-bottom:var(--space-6, 24px)">
                    @foreach ($group as $major)
                        <div class="program-major" style="border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);padding:var(--space-4, 16px)">
                            <strong>{{ $major->title }}</strong>
                            @if (filled($major->short_description)) <p style="margin:.25rem 0 0;color:var(--color-text-muted)">{{ $major->short_description }}</p> @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </x-theme::section>
    @endif
@endif

@if ($part === 'variants')
    @php($variants = $entity->variants()->frontendVisible()->with('relatedProgram')->get())
    @if ($variants->isNotEmpty())
        <x-theme::section :section="$section('variants', __('program::program.items.variants'), 'alt')">
            <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(280px, 1fr))">
                @foreach ($variants as $variant)
                    @php($image = $variant->firstMedia('image'))
                    <article class="program-variant" style="background:var(--color-bg);border-radius:var(--radius-lg, 16px);padding:var(--space-4, 16px)">
                        @if ($image) <img src="{{ $image->url('medium') }}" alt="" loading="lazy" style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-md, 8px)"> @endif
                        <h3>{{ $variant->title }}</h3>
                        <p>{{ $variant->short_description }}</p>
                        @if (filled($variant->details)) <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $variant->details) !!}</div> @endif
                        @if ($href = $variant->buttonHref())
                            <a href="{{ $href }}" class="btn">{{ filled($variant->button_text) ? $variant->button_text : __('program::program.learn_more') }}</a>
                        @endif
                    </article>
                @endforeach
            </div>
        </x-theme::section>
    @endif
@endif

@if ($part === 'careers')
    @php($lists = $entity->listItems()->frontendVisible()->get()->groupBy('list_type'))
    @if ($lists->isNotEmpty())
        <x-theme::section :section="$section('careers', __('program::program.items.career_path'))">
            <div style="display:grid;gap:var(--space-8, 32px);grid-template-columns:repeat(auto-fit, minmax(280px, 1fr))">
                @foreach (\Modules\Program\Models\ProgramListItem::TYPES as $type)
                    @if ($lists->has($type))
                        <div class="program-list program-list--{{ $type }}">
                            <h3>{{ __("program::program.items.{$type}") }}</h3>
                            @if ($type === 'industry' && ($intro = $entity->intro('industry'))) <p>{{ $intro }}</p> @endif
                            <ul>
                                @foreach ($lists[$type] as $item)
                                    <li>{{ $item->title }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </x-theme::section>
    @endif
@endif

@if ($part === 'people')
    @php($facilitators = $entity->facilitators()->frontendVisible()->get())
    @if ($facilitators->isNotEmpty())
        <x-theme::section :section="$section('facilitators', __('program::program.items.facilitators'), 'alt')">
            <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(200px, 1fr))">
                @foreach ($facilitators as $person)
                    @php($photo = $person->firstMedia('photo'))
                    <div class="program-facilitator" style="text-align:center">
                        @if ($photo) <img src="{{ $photo->url('thumb') }}" alt="{{ $person->name }}" loading="lazy" style="width:120px;height:120px;object-fit:cover;border-radius:50%"> @endif
                        <strong style="display:block;margin-top:.5rem">
                            @if ($person->profile_url) <a href="{{ $person->profile_url }}" target="_blank" rel="noopener">{{ $person->name }}</a> @else {{ $person->name }} @endif
                        </strong>
                        @if (filled($person->designation)) <span style="display:block">{{ $person->designation }}</span> @endif
                        @if (filled($person->organization)) <span style="display:block;color:var(--color-text-muted)">{{ $person->organization }}</span> @endif
                        @if ($person->linkedin_url) <a href="{{ $person->linkedin_url }}" target="_blank" rel="noopener">LinkedIn</a> @endif
                    </div>
                @endforeach
            </div>
        </x-theme::section>
    @endif

    @php($materials = $entity->materials()->frontendVisible()->get()->filter(fn ($m) => $m->href()))
    @if ($materials->isNotEmpty())
        <x-theme::section :section="$section('materials', __('program::program.items.materials'))">
            <ul class="program-materials">
                @foreach ($materials as $material)
                    <li><a href="{{ $material->href() }}" target="_blank" rel="noopener">{{ $material->title }}</a></li>
                @endforeach
            </ul>
        </x-theme::section>
    @endif

    @php($memberships = $entity->memberships()->frontendVisible()->get())
    @if ($memberships->isNotEmpty())
        <x-theme::section :section="$section('memberships', __('program::program.items.memberships'), 'alt')">
            <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(220px, 1fr))">
                @foreach ($memberships as $membership)
                    @php($logo = $membership->firstMedia('logo'))
                    <div class="program-membership">
                        @if ($logo) <img src="{{ $logo->url('thumb') }}" alt="{{ $membership->title }}" loading="lazy" style="max-height:64px"> @endif
                        <strong style="display:block">{{ $membership->title }}</strong>
                        @if (filled($membership->short_description)) <p style="margin:.25rem 0 0;color:var(--color-text-muted)">{{ $membership->short_description }}</p> @endif
                    </div>
                @endforeach
            </div>
        </x-theme::section>
    @endif
@endif
