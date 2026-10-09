<address class="site-footer__contact">
    @if ($address = settings('contact.address'))
        <span>{{ $address }}</span>
    @endif
    @if ($phone = settings('contact.phone'))
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
    @endif
    @if ($email = settings('contact.email'))
        <a href="mailto:{{ $email }}">{{ $email }}</a>
    @endif
</address>
