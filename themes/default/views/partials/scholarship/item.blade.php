{{--
    One scholarship as DIU's ScholarshipAccordion item: its title on a grey bar that opens its text (DIU's article look), the
    last date, and "Details" to its page when it has more. Contract: $item (ScholarshipPage::item), inside x-data { open }
--}}
<div class="border border-gray-200 rounded-lg overflow-hidden">
    <button type="button" x-on:click="open = open === @js($item['id']) ? null : @js($item['id'])" x-bind:aria-expanded="open === @js($item['id'])"
            class="w-full px-6 py-4 text-left bg-gray-50 hover:bg-gray-100 transition-colors duration-200 flex items-center justify-between gap-4 cursor-pointer">
        <span>
            <span class="block font-semibold text-gray-800 text-lg">{{ $item['title'] }}</span>
            @if ($item['calls'])<span class="block text-sm text-gray-500">{{ $item['calls'] }}</span>@endif
        </span>
        <x-theme::icon name="chevron-down" class="w-5 h-5 shrink-0 text-gray-600 transition-transform duration-200" x-bind:class="open === @js($item['id']) && 'rotate-180'" />
    </button>
    <div x-show="open === @js($item['id'])" x-collapse x-cloak>
        <div class="px-6 py-4 bg-white border-t border-gray-200 space-y-3">
            @if ($item['summary'])<p class="text-gray-700">{{ $item['summary'] }}</p>@endif
            @if ($item['body'])@include('theme::partials.page.body', ['body' => $item['body']])@endif
            @if ($item['deadline'])
                <p class="text-sm text-gray-600"><span class="font-semibold">{{ __('scholarship::scholarship.page.deadline') }}:</span> {{ $item['deadline'] }}
                    <span @class(['ml-2 rounded-full px-2 py-0.5 text-xs font-medium', 'bg-green-100 text-green-700' => ! $item['closed'], 'bg-gray-100 text-gray-500' => $item['closed']])>{{ __($item['closed'] ? 'scholarship::scholarship.closed' : 'scholarship::scholarship.open') }}</span></p>
            @endif
            @if ($item['more'])
                <x-theme::link-button :href="$item['url']" variant="primary" class="!text-base">{{ __('scholarship::scholarship.page.details') }}</x-theme::link-button>
            @endif
        </div>
    </div>
</div>
