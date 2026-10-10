{{-- /tuition-fees: category tabs and filters (FR-FEE-03); hidden when printed. Contract: $category, $categories, $choices, $selected --}}
<div class="tuition-filters no-print">
    <nav aria-label="{{ __('tuition::tuition.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:.75rem;margin-block:var(--space-4, 16px)">
        @foreach ($categories as $item)
            <a href="{{ url('/tuition-fees').'?category='.urlencode($item->code) }}" @if ($category?->is($item)) aria-current="page" style="font-weight:700" @endif>{{ $item->name }}</a>
        @endforeach
    </nav>
    <form method="get" action="{{ url('/tuition-fees') }}" style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));align-items:end">
        <input type="hidden" name="category" value="{{ $category?->code }}">
        @foreach (['faculty' => 'faculties', 'department' => 'departments'] as $key => $list)
            <label style="display:grid;gap:.25rem">
                <span>{{ __("tuition::tuition.fields.{$key}") }}</span>
                <select name="{{ $key }}">
                    <option value="">{{ __('tuition::tuition.any') }}</option>
                    @foreach ($choices[$list] as $option)
                        <option value="{{ $option->short_name }}" @selected($selected[$key]?->is($option))>{{ $option->short_name }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
        <label style="display:grid;gap:.25rem">
            <span>{{ __('tuition::tuition.fields.level') }}</span>
            <select name="level">
                <option value="">{{ __('tuition::tuition.any') }}</option>
                @foreach ($choices['levels'] as $level)
                    <option value="{{ $level->slug() }}" @selected($selected['level']?->is($level))>{{ $level->name }}</option>
                @endforeach
            </select>
        </label>
        <div style="display:flex;gap:.5rem">
            <button type="submit" class="btn btn-primary">{{ __('tuition::tuition.filter') }}</button>
            <button type="button" class="btn" onclick="window.print()">{{ __('tuition::tuition.print') }}</button>
        </div>
    </form>
</div>
<style>@media print { .no-print, header, footer, .site-header, .site-footer { display: none !important } .tuition-table { font-size: 11pt } }</style>
