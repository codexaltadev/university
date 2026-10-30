{{--
    /downloads and /{entity}/downloads (FR-DOC-04). Contract: $documents (paginator, current, newest first), $types (types present),
    $type, $categories (top level), $category, $year, $years, $q, $layout (table / cards / list), $base (microsite path or '')
--}}
<x-theme::layout :title="__('documents::documents.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('documents::documents.plural') }}</h1>

        <form method="get" style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);align-items:end;margin-top:var(--space-4, 16px)">
            <label>{{ __('documents::documents.search') }}
                <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('documents::documents.search_placeholder') }}">
            </label>
            @if (count($types) > 1)
                <label>{{ __('documents::documents.fields.type') }}
                    <select name="type"><option value="">{{ __('documents::documents.all') }}</option>
                        @foreach ($types as $t)<option value="{{ $t }}" @selected($type === $t)>{{ __("documents::documents.types.{$t}") }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if ($categories->isNotEmpty())
                <label>{{ __('documents::documents.fields.category') }}
                    <select name="category"><option value="">{{ __('documents::documents.all') }}</option>
                        @foreach ($categories as $c)<option value="{{ $c->slug() }}" @selected($category?->is($c))>{{ $c->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if (count($years) > 1)
                <label>{{ __('documents::documents.year') }}
                    <select name="year"><option value="">{{ __('documents::documents.all') }}</option>
                        @foreach ($years as $y)<option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>@endforeach
                    </select>
                </label>
            @endif
            @if (request('view'))<input type="hidden" name="view" value="{{ request('view') }}">@endif
            <button type="submit" class="btn">{{ __('documents::documents.search') }}</button>
        </form>

        @if ($documents->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('documents::documents.none') }}</p>
        @elseif ($layout === 'table')
            <div tabindex="0" style="overflow-x:auto;margin-top:var(--space-6, 24px)">
                <table class="documents-table" style="width:100%;border-collapse:collapse">
                    <thead><tr style="text-align:start;border-bottom:2px solid var(--color-border)">
                        <th style="padding:8px">{{ __('documents::documents.fields.title') }}</th><th style="padding:8px">{{ __('documents::documents.fields.type') }}</th>
                        <th style="padding:8px">{{ __('documents::documents.date') }}</th><th style="padding:8px">{{ __('documents::documents.file') }}</th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($documents as $document)
                            @php($href = url($base !== '' ? $base.'/'.\Modules\Documents\Models\Document::PATH.'/'.$document->slug() : $document->url()))
                            <tr style="border-bottom:1px solid var(--color-border)">
                                <td style="padding:8px"><a href="{{ $href }}">{{ $document->title }}</a>@if ($document->version_label) <small>({{ $document->version_label }})</small>@endif</td>
                                <td style="padding:8px">{{ __("documents::documents.types.{$document->document_type}") }}</td>
                                <td style="padding:8px;white-space:nowrap">{{ ($document->approved_on ?? $document->shownAt())?->translatedFormat('d M Y') }}</td>
                                <td style="padding:8px;white-space:nowrap"><small>{{ $document->fileFacts() }}</small></td>
                                <td style="padding:8px;white-space:nowrap;text-align:end">
                                    @if ($document->flipbook())<a href="{{ $href }}">{{ __('documents::documents.read') }}</a>@endif
                                    @if ($document->allow_download && ($document->fileItem() || $document->external_url)) <a href="{{ $href }}/file">{{ __('documents::documents.download') }}</a>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $documents->links() }}</div>
        @else
            <div style="margin-top:var(--space-6, 24px);{{ $layout === 'cards' ? 'display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))' : '' }}">
                @foreach ($documents as $document)
                    @include('documents::partials.item', ['document' => $document, 'base' => $base, 'layout' => $layout])
                @endforeach
            </div>
            <div style="margin-top:var(--space-6, 24px)">{{ $documents->links() }}</div>
        @endif
    </div>
</x-theme::layout>
