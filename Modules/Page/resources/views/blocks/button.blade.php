{{-- Button block. Contract: $data['label'], $data['url'] (http(s) or /path — checked on save), $data['style'] --}}
<p class="block block--button">
    <a href="{{ $data['url'] ?? '#' }}" @class(['btn', 'btn--primary' => ($data['style'] ?? 'primary') === 'primary', 'btn--secondary' => ($data['style'] ?? null) === 'secondary'])>{{ $data['label'] ?? '' }}</a>
</p>
