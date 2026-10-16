{{--
    /achievements and /{entity}/achievements (FR-ACH-02). Contract: $achievements (paginator, newest first by date achieved),
    $categories (top level), $category, $level, $year, $years, $layout (cards / timeline / grid), $base (microsite path or '')
--}}
<x-theme::layout :title="__('achievements::achievements.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('achievements::achievements.plural') }}</h1>

        <form method="get" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            <label>{{ __('achievements::achievements.fields.level') }}
                <select name="level"><option value="">{{ __('achievements::achievements.all') }}</option>
                    @foreach (\Modules\Achievements\Models\Achievement::LEVELS as $l)<option value="{{ $l }}" @selected($level === $l)>{{ __("achievements::achievements.levels.{$l}") }}</option>@endforeach
                </select>
            </label>
            @if ($years !== [])
                <label>{{ __('achievements::achievements.year') }}
                    <select name="year"><option value="">{{ __('achievements::achievements.all') }}</option>
                        @foreach ($years as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($categories->isNotEmpty())
                <label>{{ __('achievements::achievements.fields.category') }}
                    <select name="category"><option value="">{{ __('achievements::achievements.all') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->slug() }}" @selected($category?->is($c))>{{ $c->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="button">{{ __('achievements::achievements.filter') }}</button>
        </form>

        @if ($achievements->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('achievements::achievements.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px);{{ $layout === 'timeline' ? 'display:grid;gap:0' : 'display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax('.($layout === 'grid' ? '200px' : '280px').',1fr))' }}">
                @foreach ($achievements as $achievement)
                    @include('achievements::partials.card', ['achievement' => $achievement, 'base' => $base, 'layout' => $layout])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $achievements->links() }}</div>
        @endif
    </div>
</x-theme::layout>
