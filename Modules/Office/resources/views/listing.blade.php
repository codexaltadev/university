{{-- /offices (FR-OFF-09). Contract: $groups (list of [type, offices]), $layout (grid|list|directory) --}}
<x-theme::layout :title="__('office::office.plural')">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <h1>{{ __('office::office.plural') }}</h1>
        <p style="display:flex;gap:var(--space-4, 16px)">
            <a href="{{ url('/services') }}">{{ __('office::office.services.plural') }}</a>
            <a href="{{ url('/support-contacts') }}">{{ __('office::office.support.plural') }}</a>
        </p>
        @foreach ($groups as $group)
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['type']?->name ?? __('office::office.other') }}</h2>
                @if ($layout === 'directory')
                    <table class="table" style="width:100%">
                        <thead><tr><th>{{ __('office::office.fields.name') }}</th><th>{{ __('office::office.fields.location') }}</th><th>{{ __('office::office.fields.phone') }}</th><th>{{ __('office::office.fields.email') }}</th></tr></thead>
                        <tbody>
                            @foreach ($group['offices'] as $office)
                                <tr>
                                    <td>@if ($url = $office->url())<a href="{{ $url }}">{{ $office->name }}</a>@else{{ $office->name }}@endif</td>
                                    <td>{{ $office->location }}</td>
                                    <td>{{ $office->phone }}@if ($office->extension) ({{ $office->extension }})@endif</td>
                                    <td>@if ($office->email)<a href="mailto:{{ $office->email }}">{{ $office->email }}</a>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax(280px,1fr))' }}">
                        @foreach ($group['offices'] as $office)
                            @include('office::partials.card', ['office' => $office])
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</x-theme::layout>
