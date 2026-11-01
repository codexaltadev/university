{{--
    Link cards block as DIU's /students, /teachers (DIUFronend students/page, teachers/page): the heading, then cards of
    72px — icon, label — on DIU's light blue-grey, 2–4 to a row; style "boxes" as /career's "DIU Values": bordered, the
    icon beside an uppercase title and its text, a link only when given.
    Contract: $data['heading'], $data['columns'], $data['style'] (tiles|boxes), $data['items'] (label, url, text, icon, new_tab)
--}}
<section class="mb-10 not-prose">
    @if (filled($data['heading'] ?? null))
        <h2 @class(['mb-4 text-[22px] font-medium leading-none text-gray-500' => ($data['style'] ?? 'tiles') !== 'boxes', 'mb-8 text-3xl font-bold text-gray-900' => ($data['style'] ?? 'tiles') === 'boxes'])>{{ $data['heading'] }}</h2>
    @endif
    <div @class(['grid grid-cols-1 md:grid-cols-2', 'gap-4' => ($data['style'] ?? 'tiles') !== 'boxes', 'gap-6' => ($data['style'] ?? 'tiles') === 'boxes', 'lg:grid-cols-3' => (int) ($data['columns'] ?? 3) === 3, 'lg:grid-cols-4' => (int) ($data['columns'] ?? 3) === 4])>
        @foreach ((array) ($data['items'] ?? []) as $item)
            @if (($data['style'] ?? 'tiles') === 'boxes')
                <div class="border border-gray-200 p-6 hover:border-gray-400 transition-colors">
                    <div class="flex items-start gap-4">
                        @if (filled($item['icon'] ?? null))<x-theme::icon :name="$item['icon']" class="w-6 h-6 shrink-0 text-gray-900" />@endif
                        <div>
                            <h3 class="font-bold text-gray-900 mb-2 uppercase text-sm tracking-wide">
                                @if (filled($item['url'] ?? null))<a href="{{ $item['url'] }}" @if ($item['new_tab'] ?? false) target="_blank" rel="noopener" @endif class="hover:underline">{{ $item['label'] ?? '' }}</a>@else{{ $item['label'] ?? '' }}@endif
                            </h3>
                            @if (filled($item['text'] ?? null))<p class="text-gray-600 text-sm leading-relaxed">{{ $item['text'] }}</p>@endif
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ filled($item['url'] ?? null) ? $item['url'] : '#' }}" @if ($item['new_tab'] ?? false) target="_blank" rel="noopener" @endif
                   class="flex min-h-[72px] w-full items-center gap-[10px] rounded-[10px] bg-slate-100 px-4 py-[10px] text-left no-underline transition-all hover:bg-slate-200">
                    <x-theme::icon :name="filled($item['icon'] ?? null) ? $item['icon'] : 'arrow-right'" class="h-5 w-5 shrink-0 text-secondary" />
                    <span class="text-[18px] font-normal text-gray-600">{{ $item['label'] ?? '' }}</span>
                    @if ($item['new_tab'] ?? false)<x-theme::icon name="arrow-up-right" class="ml-auto h-4 w-4 shrink-0 text-gray-400" />@endif
                </a>
            @endif
        @endforeach
    </div>
</section>
