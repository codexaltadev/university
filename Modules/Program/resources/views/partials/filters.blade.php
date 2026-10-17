{{-- /programs filters (FR-PROG-12): Faculty, Department, Level, Shift, Mode, search. A plain GET form, works without JavaScript. Contract: $choices, $selected --}}
<form method="get" action="{{ url('/programs') }}" class="program-filters" role="search"
      style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));align-items:end;margin-block:var(--space-6, 24px)">
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.search') }}</span>
        <input type="search" name="q" value="{{ $selected['q'] }}" maxlength="100">
    </label>
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.fields.faculty') }}</span>
        <select name="faculty">
            <option value="">{{ __('program::program.any') }}</option>
            @foreach ($choices['faculties'] as $faculty)
                <option value="{{ $faculty->short_name }}" @selected($selected['faculty']?->is($faculty))>{{ $faculty->short_name }} — {{ $faculty->name }}</option>
            @endforeach
        </select>
    </label>
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.fields.department') }}</span>
        <select name="department">
            <option value="">{{ __('program::program.any') }}</option>
            @foreach ($choices['departments'] as $department)
                <option value="{{ $department->short_name }}" @selected($selected['department']?->is($department))>{{ $department->short_name }}</option>
            @endforeach
        </select>
    </label>
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.fields.level') }}</span>
        <select name="level">
            <option value="">{{ __('program::program.any') }}</option>
            @foreach ($choices['levels'] as $level)
                <option value="{{ $level->slug() }}" @selected($selected['level']?->is($level))>{{ $level->name }}</option>
            @endforeach
        </select>
    </label>
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.fields.shift') }}</span>
        <select name="shift">
            <option value="">{{ __('program::program.any') }}</option>
            @foreach (\Modules\Program\Models\Program::SHIFTS as $shift)
                <option value="{{ $shift }}" @selected($selected['shift'] === $shift)>{{ __("program::program.shifts.{$shift}") }}</option>
            @endforeach
        </select>
    </label>
    <label style="display:grid;gap:.25rem">
        <span>{{ __('program::program.fields.class_mode') }}</span>
        <select name="mode">
            <option value="">{{ __('program::program.any') }}</option>
            @foreach (\Modules\Program\Models\Program::CLASS_MODES as $mode)
                <option value="{{ $mode }}" @selected($selected['mode'] === $mode)>{{ __("program::program.class_modes.{$mode}") }}</option>
            @endforeach
        </select>
    </label>
    <div style="display:flex;gap:.5rem">
        <button type="submit" class="btn btn--primary">{{ __('program::program.filter') }}</button>
        <a href="{{ url('/programs') }}" class="btn">{{ __('program::program.reset') }}</a>
    </div>
</form>
