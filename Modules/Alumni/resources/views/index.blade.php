{{--
    /alumni and /{department|program}/alumni (FR-ALUM-02/05). Contract: $alumni (paginator), $departments, $programs (lists,
    empty on a microsite of that kind), $batches, $years, $layout (grid / cards / list), $base (microsite path or ''), $microsite (bool)
--}}
<x-theme::layout :title="__('alumni::alumni.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('alumni::alumni.plural') }}</h1>
        @unless ($microsite)
            <p><a href="{{ url('/alumni/submit') }}">{{ __('alumni::alumni.submit.invite') }}</a></p>
        @endunless

        <form method="get" class="alumni-filters" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            <label>{{ __('alumni::alumni.search') }}
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('alumni::alumni.search_placeholder') }}">
            </label>
            @if ($departments->isNotEmpty())
                <label>{{ __('alumni::alumni.fields.department') }}
                    <select name="department"><option value="">{{ __('alumni::alumni.all') }}</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected((string) request('department') === (string) $d->id)>{{ $d->short_name ?: $d->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($programs->isNotEmpty())
                <label>{{ __('alumni::alumni.fields.program') }}
                    <select name="program"><option value="">{{ __('alumni::alumni.all') }}</option>
                        @foreach ($programs as $p)<option value="{{ $p->id }}" @selected((string) request('program') === (string) $p->id)>{{ $p->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($batches->isNotEmpty())
                <label>{{ __('alumni::alumni.fields.batch') }}
                    <select name="batch"><option value="">{{ __('alumni::alumni.all') }}</option>
                        @foreach ($batches as $b)<option value="{{ $b }}" @selected(request('batch') === $b)>{{ $b }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($years->isNotEmpty())
                <label>{{ __('alumni::alumni.fields.passing_year') }}
                    <select name="year"><option value="">{{ __('alumni::alumni.all') }}</option>
                        @foreach ($years as $y)<option value="{{ $y }}" @selected((string) request('year') === (string) $y)>{{ $y }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label>{{ __('alumni::alumni.fields.job_type') }}
                <select name="job"><option value="">{{ __('alumni::alumni.all') }}</option>
                    @foreach (\Modules\Alumni\Models\Alumnus::JOB_TYPES as $j)<option value="{{ $j }}" @selected(request('job') === $j)>{{ __("alumni::alumni.job_types.{$j}") }}</option>@endforeach
                </select>
            </label>
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="btn">{{ __('alumni::alumni.search') }}</button>
        </form>

        <p style="margin-top:var(--space-3, 12px);color:var(--color-muted, #6b7280)">{{ trans_choice('alumni::alumni.count', $alumni->total(), ['count' => $alumni->total()]) }}</p>

        @if ($alumni->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('alumni::alumni.none') }}</p>
        @else
            <div style="display:grid;gap:var(--space-4, 16px);margin-top:var(--space-6, 24px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax('.($layout === 'cards' ? '300px' : '220px').',1fr))' }}">
                @foreach ($alumni as $alumnus)
                    @include('alumni::partials.card', ['alumnus' => $alumnus, 'layout' => $layout])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $alumni->links() }}</div>
        @endif
    </div>
</x-theme::layout>
