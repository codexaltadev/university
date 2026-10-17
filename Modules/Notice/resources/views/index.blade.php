{{--
    Notice Board /notices and /{entity}/notices (FR-NOT-02..04). Contract: $notices (paginator), $categories, $category (?Term),
    $entities (key => name; main board only), $entity (?key), $archive (bool), $layout (table / list / cards), $base (place path or '')
--}}
<x-theme::layout :title="__('notice::notice.plural')">
    @php($listing = url($base.'/'.\Modules\Notice\Models\Notice::PATH))
    @php($href = fn ($notice) => url($base.'/'.\Modules\Notice\Models\Notice::PATH.'/'.$notice->slug()))
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ $archive ? __('notice::notice.archive') : __('notice::notice.plural') }}</h1>

        <nav style="display:flex;gap:var(--space-2, 8px);margin-top:var(--space-3, 12px)">
            <a href="{{ $listing }}" @class(['chip', 'chip-active' => ! $archive])>{{ __('notice::notice.on_board') }}</a>
            <a href="{{ $listing.'?archive=1' }}" @class(['chip', 'chip-active' => $archive])>{{ __('notice::notice.archive') }}</a>
        </nav>

        <form method="get" class="notice-filters" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            @if ($archive)<input type="hidden" name="archive" value="1">@endif
            <label>{{ __('notice::notice.search') }}
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('notice::notice.search_placeholder') }}">
            </label>
            @if ($categories->isNotEmpty())
                <label>{{ __('notice::notice.fields.category') }}
                    <select name="category"><option value="">{{ __('notice::notice.all') }}</option>
                        @foreach ($categories as $item)<option value="{{ $item->slug() }}" @selected($category?->is($item))>{{ $item->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($entities->isNotEmpty())
                <label>{{ __('notice::notice.entity') }}
                    <select name="entity"><option value="">{{ __('notice::notice.all') }}</option>
                        @foreach ($entities as $key => $name)<option value="{{ $key }}" @selected($entity === $key)>{{ $name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label>{{ __('notice::notice.from') }} <input type="date" name="from" value="{{ request('from') }}"></label>
            <label>{{ __('notice::notice.to') }} <input type="date" name="to" value="{{ request('to') }}"></label>
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="btn">{{ __('notice::notice.search') }}</button>
        </form>

        @if ($notices->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('notice::notice.none') }}</p>
        @elseif ($layout === 'table')
            <div style="overflow-x:auto;margin-top:var(--space-6, 24px)">
                <table class="table notice-table" style="width:100%">
                    <thead><tr>
                        <th scope="col">{{ __('notice::notice.fields.date') }}</th>
                        <th scope="col">{{ __('notice::notice.fields.title') }}</th>
                        <th scope="col">{{ __('notice::notice.fields.reference_no') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($notices as $notice)
                            <tr @style(['background:var(--color-danger-soft, #FEF2F2)' => $notice->is_urgent])>
                                <td style="white-space:nowrap">{{ $notice->active_at?->translatedFormat('d M Y') }}</td>
                                <td><a href="{{ $href($notice) }}">{{ $notice->title }}</a> @include('notice::partials.badges', ['notice' => $notice])</td>
                                <td>{{ $notice->reference_no }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="display:grid;gap:var(--space-3, 12px);margin-top:var(--space-6, 24px);{{ $layout === 'cards' ? 'grid-template-columns:repeat(auto-fill,minmax(280px,1fr))' : '' }}">
                @foreach ($notices as $notice)
                    <article style="padding:var(--space-4, 16px);border:1px solid {{ $notice->is_urgent ? 'var(--color-danger, #DC2626)' : 'var(--color-border)' }};border-radius:var(--radius-md, 8px)">
                        <p style="margin:0;font-size:.85em;color:var(--color-muted, #6b7280)">{{ $notice->active_at?->translatedFormat('d M Y') }}@if ($notice->reference_no) · {{ $notice->reference_no }}@endif</p>
                        <h3 style="margin:4px 0;font-size:1.05em"><a href="{{ $href($notice) }}">{{ $notice->title }}</a></h3>
                        <p style="margin:0">@include('notice::partials.badges', ['notice' => $notice])</p>
                    </article>
                @endforeach
            </div>
        @endif
        <div style="margin-top:var(--space-6, 24px)">{{ $notices->links() }}</div>
    </div>
</x-theme::layout>
