{{-- "Honour Board" page block: the office's head now and the former heads with their terms. Contract: $data (intro), $locale; $office (OfficePage or null) --}}
@if ($office && ($heads = $office->heads()) !== [])
    <div class="block block--office-heads">
        @if (filled($data['intro'] ?? null))<p>{{ $data['intro'] }}</p>@endif
        <table class="table" style="width:100%">
            <tbody>
                @foreach ($heads as $head)
                    <tr>
                        <td>{{ $head['name'] }}@if ($head['current']) <strong>({{ __('office::office.page.serving') }})</strong>@endif</td>
                        <td>{{ $head['label'] }}</td>
                        <td style="white-space:nowrap">{{ $head['from'] }} – {{ $head['to'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
