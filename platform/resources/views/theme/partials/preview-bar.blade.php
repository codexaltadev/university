{{-- FR-THEME-03: only the admin who previews sees this --}}
<div role="status" style="background:#111827;color:#fff;padding:.5rem 1rem;font:14px/1.4 system-ui,sans-serif;text-align:center">
    {{ __('platform::theme.previewing', ['theme' => $theme->name]) }}
    <a href="{{ route('theme.preview.stop', ['back' => request()->getRequestUri()]) }}" style="color:#fff;text-decoration:underline;margin-inline-start:.75rem">{{ __('platform::theme.stop_preview') }}</a>
</div>
