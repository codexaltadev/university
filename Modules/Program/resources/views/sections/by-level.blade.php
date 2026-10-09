{{-- Homepage section "Programs by Level" (FR-PROG-16). Contract: $items (list of [level, programs]), $section, $settings --}}
@foreach ($items as $group)
    <div class="program-level" style="margin-bottom:var(--space-8, 32px)">
        <h3 style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem">
            <span>{{ $group['level']->name }}</span>
            <a href="{{ url('/programs').'?level='.urlencode($group['level']->slug()) }}" style="font-size:var(--text-sm, 16px);font-weight:400">{{ __('program::program.view_all') }}</a>
        </h3>
        <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.5rem;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr))">
            @foreach ($group['programs'] as $program)
                <li><a href="{{ $program->url() }}">{{ $program->name }}</a></li>
            @endforeach
        </ul>
    </div>
@endforeach
