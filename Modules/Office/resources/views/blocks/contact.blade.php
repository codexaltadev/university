{{-- "Office contact" page block: the office's facts, its campus and map, who to contact. Contract: $data (intro), $locale; $office (OfficePage or null) --}}
@if ($office)
    @php($contact = $office->contact())
    <div class="block block--office-contact">
        @if (filled($data['intro'] ?? null))<p>{{ $data['intro'] }}</p>@endif
        <dl>
            @foreach ($contact['facts'] as $key => $value)
                <dt>{{ __('office::office.page.'.$key) }}</dt><dd>{{ $value }}</dd>
            @endforeach
            @if ($contact['campus']['address'] ?? null)<dt>{{ $contact['campus']['name'] }}</dt><dd>{{ $contact['campus']['address'] }}</dd>@endif
        </dl>
        @if ($contact['campus']['map'] ?? null)
            <iframe src="{{ $contact['campus']['map'] }}" title="{{ __('office::office.page.find_us') }}" loading="lazy" style="width:100%;height:320px;border:0"></iframe>
        @endif
        @foreach ($contact['contacts'] as $item)
            <p><strong>{{ $item['purpose'] }}</strong>@if ($item['location']) — {{ $item['location'] }}@endif
                @foreach ($item['people'] as $person)<br>{{ $person->name() }}@if ($person->phone()) · {{ $person->phone() }}@endif @if ($person->email()) · {{ $person->email() }}@endif @endforeach
            </p>
        @endforeach
    </div>
@endif
