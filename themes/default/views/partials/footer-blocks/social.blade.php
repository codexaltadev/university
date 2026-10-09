<ul class="site-footer__social">
    @foreach ((array) settings('social.links', []) as $link)
        <li><a href="{{ $link['url'] ?? '#' }}" rel="noopener" target="_blank">{{ $link['label'] ?? ucfirst($link['platform'] ?? '') }}</a></li>
    @endforeach
</ul>
