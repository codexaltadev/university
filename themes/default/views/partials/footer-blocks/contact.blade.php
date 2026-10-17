<address class="site-footer__contact">
    @if ($address = settings('contact.address'))
        <span><x-theme::icon name="map-pin" width="16" height="16" />{{ $address }}</span>
    @endif
    @if ($phone = settings('contact.phone'))
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"><x-theme::icon name="phone" width="16" height="16" />{{ $phone }}</a>
    @endif
    @if ($email = settings('contact.email'))
        <a href="mailto:{{ $email }}"><x-theme::icon name="mail" width="16" height="16" />{{ $email }}</a>
    @endif
</address>
