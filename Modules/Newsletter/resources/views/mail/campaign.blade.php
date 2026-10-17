{{-- A campaign email (FR-NLT-05). Contract: $campaign, $unsubscribeUrl (null for a test), $footer --}}
<!doctype html>
<html lang="{{ $campaign->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7">
        <tr><td align="center" style="padding:24px 12px">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:8px">
                <tr><td style="padding:24px 28px;font-size:16px;line-height:1.6">
                    {!! $campaign->body !!}
                    @if ($campaign->source_url)
                        <p style="margin-top:20px"><a href="{{ $campaign->source_url }}" style="color:#1d4ed8">{{ $campaign->source_label ?: __('newsletter::newsletter.read_more', [], $campaign->locale) }}</a></p>
                    @endif
                </td></tr>
                <tr><td style="padding:16px 28px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280">
                    @if ($footer)<p style="margin:0 0 8px">{{ $footer }}</p>@endif
                    @if ($unsubscribeUrl)
                        <p style="margin:0">{{ __('newsletter::newsletter.mail_why', [], $campaign->locale) }} <a href="{{ $unsubscribeUrl }}" style="color:#6b7280">{{ __('newsletter::newsletter.unsubscribe.link', [], $campaign->locale) }}</a></p>
                    @endif
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
