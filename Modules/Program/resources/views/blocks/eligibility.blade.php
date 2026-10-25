{{-- Admission eligibility of a faculty: its programs by level with their eligibility. Contract: $data (faculty, links), $locale; $eligibility (Support\EligibilityPage) --}}
<div class="block block--program-eligibility">
    @if ($head = $eligibility->head())
        <h2>{{ $head['name'] }}</h2>
        @if ($head['overview'])<p>{{ $head['overview'] }}</p>@endif
    @endif
    @foreach ($eligibility->levels() as $level)
        <h3>{{ $level['label'] }} ({{ $level['count'] }})</h3>
        @foreach ($level['programs'] as $program)
            <details>
                <summary>{{ $program['name'] }}@if ($program['short']) — {{ $program['short'] }}@endif</summary>
                @if ($program['general'])<h4>{{ __('program::program.eligibility_block.general') }}</h4><div>{!! $program['general'] !!}</div>@endif
                @if ($program['english'])<h4>{{ __('program::program.eligibility_block.english') }}</h4><div>{!! $program['english'] !!}</div>@endif
                @if (! $program['general'] && ! $program['english'])<p>{{ __('program::program.eligibility_block.no_eligibility') }}</p>@endif
                <p><a href="{{ $program['url'] }}">{{ __('program::program.eligibility_block.program_page') }}</a>@foreach ($eligibility->links() as $link) · <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</p>
            </details>
        @endforeach
    @endforeach
</div>
