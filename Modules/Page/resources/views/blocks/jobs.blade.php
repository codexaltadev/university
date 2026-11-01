{{-- Job listings block. Contract: $data['heading']; $jobs (JobsBlock::jobs: title, company, type, location, level, vacancies, deadline, expired, url) or null when unreachable; $types, $levels --}}
<section class="block block--jobs">
    @if (filled($data['heading'] ?? null))<h2>{{ $data['heading'] }}</h2>@endif
    @if ($jobs === null)
        <p>{{ __('page::page.jobs.unreachable') }}</p>
    @elseif ($jobs === [])
        <p>{{ __('page::page.jobs.none') }}</p>
    @else
        <ul style="list-style:none;padding:0;display:grid;gap:var(--space-3, 12px)">
            @foreach ($jobs as $job)
                @unless ($job['expired'])
                    <li class="card" style="padding:var(--space-4, 16px);border:1px solid var(--color-border)">
                        <a href="{{ $job['url'] }}" target="_blank" rel="noopener"><strong>{{ $job['title'] }}</strong></a>
                        <p style="margin:.25rem 0 0">{{ collect([$job['type'], $job['location'], $job['level']])->filter()->implode(' · ') }}@if ($job['deadline']) · {{ __('page::page.jobs.deadline', ['date' => $job['deadline']]) }}@endif</p>
                    </li>
                @endunless
            @endforeach
        </ul>
    @endif
</section>
