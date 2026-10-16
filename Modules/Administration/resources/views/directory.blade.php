{{--
    /administration (FR-ADMN-04). Contract: $groups (list of [category, members]), $categories (public, in order),
    $groupOptions (groups in use), $offices (Office list; empty without the Office module), $officeNames (id => name),
    $total, $layout (grid / list / table), $intro (HTML or null)
--}}
<x-theme::layout :title="__('administration::administration.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('administration::administration.plural') }}</h1>
        @if ($intro)
            <div class="administration-intro prose">{!! \Illuminate\Support\Str::sanitizeHtml($intro) !!}</div>
        @endif

        <form method="get" class="administration-filters" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            <label>{{ __('administration::administration.search') }}
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('administration::administration.search_placeholder') }}">
            </label>
            @if ($groupOptions->count() > 1)
                <label>{{ __('administration::administration.fields.group') }}
                    <select name="group" onchange="this.form.submit()">
                        <option value="">{{ __('administration::administration.all') }}</option>
                        @foreach ($groupOptions as $group)
                            <option value="{{ $group }}" @selected(request('group') === $group)>{{ __("administration::administration.groups.{$group}") }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label>{{ __('administration::administration.category.singular') }}
                <select name="category" onchange="this.form.submit()">
                    <option value="">{{ __('administration::administration.all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            @if ($offices->isNotEmpty())
                <label>{{ __('administration::administration.fields.primary_office') }}
                    <select name="office" onchange="this.form.submit()">
                        <option value="">{{ __('administration::administration.all') }}</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected((string) request('office') === (string) $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="button">{{ __('administration::administration.search') }}</button>
        </form>

        <p style="margin-top:var(--space-3, 12px);color:var(--color-muted, #6b7280)">{{ trans_choice('administration::administration.count', $total, ['count' => $total]) }}</p>

        @forelse ($groups as $group)
            <section class="administration-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['category']->name }}</h2>
                @if ($layout === 'table')
                    <div style="overflow-x:auto">
                        <table class="table" style="width:100%">
                            <thead><tr>
                                <th scope="col">{{ __('administration::administration.fields.name') }}</th>
                                <th scope="col">{{ __('administration::administration.fields.designation') }}</th>
                                <th scope="col">{{ __('administration::administration.fields.primary_office') }}</th>
                                <th scope="col">{{ __('administration::administration.fields.phone') }}</th>
                                <th scope="col">{{ __('administration::administration.fields.email') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($group['members'] as $member)
                                    @php($person = $member->personPresenter())
                                    <tr>
                                        <td>@if ($url = $person->profileUrl())<a href="{{ $url }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</td>
                                        <td>{{ $person->designation() }}</td>
                                        <td>{{ $officeNames[$member->primary_office_id] ?? '' }}</td>
                                        <td>{{ $person->phone() }}@if ($person->extension()) ({{ __('administration::administration.ext') }} {{ $person->extension() }})@endif</td>
                                        <td>@if ($email = $person->email())<a href="mailto:{{ $email }}">{{ $email }}</a>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax(240px,1fr))' }}">
                        @foreach ($group['members'] as $member)
                            @include('administration::partials.card', ['member' => $member, 'office' => $officeNames[$member->primary_office_id] ?? null, 'row' => $layout === 'list'])
                        @endforeach
                    </div>
                @endif
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('administration::administration.none') }}</p>
        @endforelse
    </div>
</x-theme::layout>
