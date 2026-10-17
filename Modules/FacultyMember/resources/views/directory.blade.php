{{--
    /faculty-members and {faculty|department}/faculty-members (FR-FM-03/05). Contract: $members (paginator, designation order),
    $faculties (main site only), $departments (choices; empty on a Department microsite), $designations (in use, ladder order),
    $layout (grid / list / cards), $intro (HTML or null)
--}}
<x-theme::layout :title="__('facultymember::facultymember.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('facultymember::facultymember.plural') }}</h1>
        @if ($intro)
            <div class="faculty-member-intro prose">{!! \Illuminate\Support\Str::sanitizeHtml($intro) !!}</div>
        @endif

        <form method="get" class="faculty-member-filters" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            <label>{{ __('facultymember::facultymember.search') }}
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('facultymember::facultymember.search_placeholder') }}">
            </label>
            @if ($faculties->isNotEmpty())
                <label>{{ __('facultymember::facultymember.fields.faculty') }}
                    <select name="faculty" onchange="this.form.submit()">
                        <option value="">{{ __('facultymember::facultymember.all') }}</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->short_name }}" @selected(strcasecmp((string) request('faculty'), (string) $faculty->short_name) === 0)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($departments->isNotEmpty())
                <label>{{ __('facultymember::facultymember.fields.department') }}
                    <select name="department" onchange="this.form.submit()">
                        <option value="">{{ __('facultymember::facultymember.all') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->short_name }}" @selected(strcasecmp((string) request('department'), (string) $department->short_name) === 0)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if ($designations->count() > 1)
                <label>{{ __('facultymember::facultymember.fields.designation') }}
                    <select name="designation" onchange="this.form.submit()">
                        <option value="">{{ __('facultymember::facultymember.all') }}</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}" @selected((string) request('designation') === (string) $designation->id)>{{ $designation->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="btn">{{ __('facultymember::facultymember.search') }}</button>
        </form>

        <p style="margin-top:var(--space-3, 12px);color:var(--color-muted, #6b7280)">{{ trans_choice('facultymember::facultymember.count', $members->total(), ['count' => $members->total()]) }}</p>

        @if ($members->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('facultymember::facultymember.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : ($layout === 'cards' ? 'repeat(auto-fill,minmax(280px,1fr))' : 'repeat(auto-fill,minmax(220px,1fr))') }}">
                @foreach ($members as $member)
                    @include('facultymember::partials.card', ['member' => $member, 'layout' => $layout])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $members->links() }}</div>
        @endif
    </div>
</x-theme::layout>
