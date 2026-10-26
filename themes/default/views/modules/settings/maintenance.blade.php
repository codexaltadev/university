{{--
    The maintenance page (503) as DIU's /maintenance: the site's name in a pill, a turning gear, "Under Maintenance", the message,
    and two cards — how long it lasts and the email for help — when Settings › Maintenance gives them. No menus: nothing else
    of the website answers while it is under maintenance.
    Contract (Settings module): $message, $siteName, $duration, $email
--}}
<x-theme::bare :title="$siteName">
    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-10">
        <div class="max-w-2xl w-full text-center">
            <p class="inline-block px-4 py-1.5 bg-white border border-gray-200 rounded-full text-sm mb-8 text-gray-700 font-medium">{{ $siteName }}</p>
            <div class="mb-8 flex justify-center">
                <span class="grid place-items-center w-24 h-24 bg-white border-2 border-gray-200 rounded-2xl">
                    <svg class="w-12 h-12 text-gray-600 motion-safe:animate-spin [animation-duration:3s]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
            </div>
            <h1 class="text-5xl md:text-6xl font-bold mb-6 tracking-tight text-gray-900">{{ __('theme::theme.errors.maintenance') }}</h1>
            <p class="text-xl text-gray-600 mb-12 max-w-lg mx-auto leading-relaxed">{{ $message }}</p>

            @if (($duration ?? null) || ($email ?? null))
                <div @class(['grid gap-4 mb-12 text-left', 'md:grid-cols-2' => ($duration ?? null) && ($email ?? null)])>
                    @if ($duration ?? null)
                        <div class="bg-white border border-gray-200 rounded-xl p-6 flex items-start gap-4">
                            <span class="grid place-items-center w-10 h-10 bg-gray-100 rounded-lg shrink-0"><x-theme::icon name="clock" class="w-5 h-5 text-gray-600" /></span>
                            <div><h2 class="font-semibold mb-1 text-gray-900">{{ __('theme::theme.errors.duration') }}</h2><p class="text-sm text-gray-600">{{ $duration }}</p></div>
                        </div>
                    @endif
                    @if ($email ?? null)
                        <div class="bg-white border border-gray-200 rounded-xl p-6 flex items-start gap-4">
                            <span class="grid place-items-center w-10 h-10 bg-gray-100 rounded-lg shrink-0"><x-theme::icon name="mail" class="w-5 h-5 text-gray-600" /></span>
                            <div><h2 class="font-semibold mb-1 text-gray-900">{{ __('theme::theme.errors.help') }}</h2><a href="mailto:{{ $email }}" class="text-sm text-gray-600 hover:text-gray-900">{{ $email }}</a></div>
                        </div>
                    @endif
                </div>
            @endif
            <p class="text-sm text-gray-500">{{ __('theme::theme.errors.thanks') }}</p>
        </div>
    </div>
</x-theme::bare>
