{{--
    Admission eligibility as DIU's /admission/faculty/{slug} (AdmissionComponent, AdmissionProgramTable): "Faculty of …" and its
    overview, a round button per level with its count (Undergraduate Program (10), Post Graduate Program (4)), then the level's
    programs — full name and short tag — each opening its eligibility for general and English-medium students, the program's
    page and the block's links as DIU's Quick Links. Contract: $data (faculty, links), $locale; $eligibility (EligibilityPage)
--}}
@if ($head = $eligibility->head())
    <div>
        <h2 class="text-2xl md:text-[34px] font-semibold leading-tight text-neutral-800">{{ $head['name'] }}</h2>
        @if ($head['overview'])<p class="my-3 max-w-3xl text-lg text-slate-600 line-clamp-4">{{ $head['overview'] }}</p>@endif

        @if (($levels = $eligibility->levels()) === [])
            <p class="py-12 text-center text-slate-600">{{ __('program::program.eligibility_block.none', ['level' => '']) }}</p>
        @else
            <div x-data="{ level: @js($levels[0]['key']), open: null }">
                <div class="grid grid-cols-2 md:flex gap-2 md:gap-6 my-8 md:my-10" role="tablist">
                    @foreach ($levels as $level)
                        <button type="button" role="tab" x-on:click="level = @js($level['key']); open = null" x-bind:aria-selected="level === @js($level['key'])"
                                x-bind:class="level === @js($level['key']) ? 'text-white bg-gradient-to-r from-primary to-secondary border-transparent' : 'text-secondary bg-transparent border-neutral-800 hover:bg-gradient-to-r hover:from-primary hover:to-secondary hover:text-white'"
                                class="rounded-full border px-4 py-[10px] text-base md:text-lg font-medium transition-colors cursor-pointer">
                            {{ $level['label'] }} ({{ $level['count'] }})
                        </button>
                    @endforeach
                </div>

                @foreach ($levels as $level)
                    <div x-show="level === @js($level['key'])" @if (! $loop->first) x-cloak @endif role="tabpanel" class="overflow-hidden rounded-xl bg-white">
                        <div class="hidden md:grid grid-cols-12 px-4 py-3 border-b border-slate-200 text-xl font-semibold text-slate-600" role="row">
                            <div class="col-span-9" role="columnheader">{{ __('program::program.eligibility_block.program_name') }}</div>
                            <div class="col-span-3 text-right" role="columnheader">{{ __('program::program.eligibility_block.short') }}</div>
                        </div>
                        <ul class="divide-y divide-neutral-500" role="list">
                            @foreach ($level['programs'] as $program)
                                <li x-data="{ id: @js($level['key'].'-'.$loop->index) }">
                                    <button type="button" x-on:click="open = open === id ? null : id" x-bind:aria-expanded="open === id"
                                            class="w-full text-left grid grid-cols-12 items-center mt-4 md:mt-6 p-[10px] cursor-pointer hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                                        <span class="col-span-9 md:col-span-10 text-lg md:text-[24px] font-medium text-neutral-800 truncate">{{ $program['name'] }}</span>
                                        <span class="col-span-3 md:col-span-2 flex items-center justify-end gap-2 text-lg md:text-[24px] font-medium text-neutral-800">
                                            @if ($program['short'])<span>{{ $program['short'] }}</span>@endif
                                            <x-theme::icon name="chevron-down" class="w-8 h-6 shrink-0 transition-transform duration-200" x-bind:class="open === id && 'rotate-180'" />
                                        </span>
                                    </button>
                                    <div x-show="open === id" x-collapse x-cloak>
                                        <div class="mt-3 mb-6 rounded-lg bg-slate-50 border border-slate-200 px-4 py-8 md:p-8">
                                            @if ($program['general'])
                                                <div class="mb-3">
                                                    <h4 class="text-base md:text-xl font-bold mb-1">{{ __('program::program.eligibility_block.general') }}</h4>
                                                    <div class="text-base md:text-lg [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5">{!! $program['general'] !!}</div>
                                                </div>
                                            @endif
                                            @if ($program['english'])
                                                <div class="mb-3">
                                                    <h4 class="text-base md:text-xl font-bold mb-1">{{ __('program::program.eligibility_block.english') }}</h4>
                                                    <div class="text-base md:text-lg [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5">{!! $program['english'] !!}</div>
                                                </div>
                                            @endif
                                            @if (! $program['general'] && ! $program['english'])
                                                <p class="text-base md:text-lg text-slate-600">{{ __('program::program.eligibility_block.no_eligibility') }}</p>
                                            @endif
                                            <div class="mt-10 rounded-xl bg-white p-3">
                                                @include('theme::partials.diu.quick-links', ['links' => [['label' => __('program::program.eligibility_block.program_page'), 'url' => $program['url']], ...$eligibility->links()]])
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
