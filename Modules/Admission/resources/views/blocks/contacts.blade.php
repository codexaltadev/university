{{-- Admission contacts block (FR-ADM-10). Contract: $data (groups), $locale --}}
@php($groups = \Modules\Admission\Models\AdmissionContactGroup::query()->frontendVisible()->when(! empty($data['groups']), fn ($q) => $q->whereKey(array_map('intval', (array) $data['groups'])))->ordered()->get())
<div class="block block--admission-contacts">
    @foreach ($groups as $group)
        @if (($people = $group->people(\Modules\Admission\Models\AdmissionContactGroup::ROLE))->isNotEmpty())
            <h3 style="margin-top:var(--space-6, 24px)">{{ $group->name }}</h3>
            <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                @foreach ($people as $person)
                    <li class="card" style="display:flex;gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                        @if ($photo = $person->photoUrl())
                            <img src="{{ $photo }}" alt="" loading="lazy" width="64" height="64" style="width:64px;height:64px;border-radius:50%;object-fit:cover">
                        @endif
                        <div>
                            <strong>{{ $person->name() }}</strong>
                            @if ($person->designation())<div style="color:var(--color-text-muted)">{{ $person->designation() }}</div>@endif
                            @if ($person->phone())<div><a href="tel:{{ preg_replace('/[^0-9+]/', '', $person->phone()) }}">{{ $person->phone() }}</a>@if ($person->extension()) ({{ __('admission::admission.ext') }} {{ $person->extension() }})@endif</div>@endif
                            @if ($person->email())<div><a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a></div>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
</div>
