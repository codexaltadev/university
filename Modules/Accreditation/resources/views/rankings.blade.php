{{-- /rankings (FR-ACR-04). Contract: $latest (each result's latest year), $trends (agency → ['name','key','rows']), $base --}}
<x-theme::layout :title="__('accreditation::accreditation.rankings')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('accreditation::accreditation.rankings') }}</h1>
        @if ($latest->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('accreditation::accreditation.none') }}</p>
        @else
            <h2 style="margin-top:var(--space-6, 24px)">{{ __('accreditation::accreditation.latest') }}</h2>
            <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
                @foreach ($latest as $ranking)
                    @include('accreditation::partials.ranking', ['ranking' => $ranking])
                @endforeach
            </div>
        @endif
        @if ($trends->isNotEmpty())
            <h2 style="margin-top:var(--space-10, 40px)">{{ __('accreditation::accreditation.trend') }}</h2>
            @foreach ($trends as $trend)
                <section id="trend-{{ $trend['key'] }}" style="margin-top:var(--space-6, 24px)">
                    <h3>{{ $trend['name'] }}</h3>
                    <div style="overflow-x:auto">
                        <table class="table" style="width:100%;border-collapse:collapse">
                            <thead><tr>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid var(--color-border)">{{ __('accreditation::accreditation.fields.year') }}</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid var(--color-border)">{{ __('accreditation::accreditation.fields.scope') }}</th>
                                <th style="text-align:left;padding:8px;border-bottom:2px solid var(--color-border)">{{ __('accreditation::accreditation.fields.rank_label') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($trend['rows'] as $row)
                                    <tr>
                                        <td style="padding:8px;border-bottom:1px solid var(--color-border)">{{ $row->year }}</td>
                                        <td style="padding:8px;border-bottom:1px solid var(--color-border)">{{ $row->scope === 'overall' ? __('accreditation::accreditation.scopes.overall') : $row->subject }}</td>
                                        <td style="padding:8px;border-bottom:1px solid var(--color-border)"><strong>{{ $row->rank_label }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        @endif
        <p style="margin-top:var(--space-8, 32px)"><a class="button button--outline" href="{{ url($base.'/'.\Modules\Accreditation\Models\Accreditation::PATH) }}">{{ __('accreditation::accreditation.plural') }}</a></p>
    </div>
</x-theme::layout>
