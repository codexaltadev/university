{{--
    DIU Department Notices: blue band — title, line, five notices (date, title), "View All Notices".
    Contract: $section (title, subheading, content: heading, subheading, button_label, button_url), $items (Notice)
--}}
<div class="bg-primary py-8 md:py-16">
    <div class="container mx-auto px-4">
        <div class="mb-6 md:mb-8">
            <x-theme::heading tag="h2" size="md" color="white">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
            <p class="text-blue-100 text-base md:text-lg">{{ $section->subheading ?: $section->content['subheading'] }}</p>
        </div>
        <div class="space-y-0">
            @foreach (collect($items ?? [])->take(5) as $notice)
                <div class="flex flex-col sm:flex-row sm:items-center py-4 md:py-6 border-b border-blue-400 last:border-b-0 gap-2 sm:gap-0">
                    <div class="w-full sm:w-44 flex-shrink-0">
                        <span class="text-white text-base md:text-[24px] font-medium">{{ ($notice->active_at ?? $notice->created_at)?->format('j M, Y') }}</span>
                    </div>
                    <div class="flex-1">
                        <a href="{{ url($notice->url()) }}" class="text-white hover:underline hover:text-blue-300 text-base md:text-[24px] font-medium">{{ $notice->title }}</a>
                    </div>
                    @if ($file = $notice->firstMedia('attachments'))
                        <div class="flex-shrink-0 ml-4 hidden md:block">
                            <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="block p-2 text-white hover:text-blue-300 transition-colors" title="{{ $notice->title }}">
                                <x-theme::icon name="eye" class="w-6 h-6" /><span class="sr-only">{{ __('theme::theme.view_file') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if (collect($items ?? [])->count() > 5 && filled($section->content['button_url']))
            <div class="mt-8 text-center">
                <a href="{{ $section->content['button_url'] }}" class="inline-flex items-center px-6 py-3 bg-white text-primary font-medium text-lg rounded-lg hover:bg-blue-50 hover:no-underline transition-colors duration-200 shadow-sm hover:shadow-md">
                    {{ $section->content['button_label'] }}<x-theme::icon name="chevron-right" class="ml-2 w-5 h-5" />
                </a>
            </div>
        @endif
    </div>
</div>
