{{-- Homepage section "Faculty Members" (FR-FM-05). Contract: $items (['members' => FacultyMember list, 'url' => the place's directory]), $section, $settings (count, designations) --}}
@if (($items['members'] ?? collect())->isNotEmpty())
    <div class="faculty-members-section" style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
        @foreach ($items['members'] as $member)
            @include('facultymember::partials.card', ['member' => $member, 'layout' => 'grid'])
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($items['url']) }}" class="btn">{{ __('facultymember::facultymember.all_members') }}</a></p>
@endif
