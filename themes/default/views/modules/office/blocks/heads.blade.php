{{--
    Honour Board of an office (DIU /registrar-office/honour-board, there a typed table): the head now in a blue card — photo,
    name, title, since when and how long — then the former heads on a timeline, latest first, each with their term.
    Contract: $data (intro), $locale; $office (OfficePage or null: the block is on a page outside an office's site)
--}}
@if ($office && ($heads = $office->heads()) !== [])
    <div class="not-prose space-y-10">
        @if (filled($data['intro'] ?? null))<p class="text-gray-600 md:text-lg leading-relaxed">{{ $data['intro'] }}</p>@endif

        @foreach (collect($heads)->where('current', true) as $head)
            <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary to-secondary text-white p-6 sm:p-8 shadow-lg">
                <x-theme::icon name="award" class="absolute -right-6 -bottom-8 w-48 h-48 text-white/10" />
                <div class="relative flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                    @if ($head['photo'])
                        <img src="{{ $head['photo'] }}" alt="{{ $head['name'] }}" class="w-32 h-32 rounded-full object-cover ring-4 ring-white/30 shrink-0 bg-white">
                    @else
                        <span class="w-32 h-32 rounded-full ring-4 ring-white/30 bg-white/15 flex items-center justify-center text-4xl font-bold shrink-0">{{ $head['initials'] }}</span>
                    @endif
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide bg-white/20 px-3 py-1 rounded-full">
                            <x-theme::icon name="star" class="w-3.5 h-3.5" />{{ __('office::office.page.serving') }}
                        </span>
                        <h3 class="mt-3 text-2xl md:text-3xl font-bold">{{ $head['name'] }}</h3>
                        <p class="mt-1 text-white/85 text-lg">{{ $head['label'] }}</p>
                        @if ($head['from'])
                            <p class="mt-4 inline-flex items-center gap-2 text-sm text-white/85"><x-theme::icon name="calendar" class="w-4 h-4" />{{ __('office::office.page.since', ['date' => $head['from']]) }}@if ($head['years']) · {{ $head['years'] }}@endif</p>
                        @endif
                    </div>
                </div>
            </section>
        @endforeach

        @if (($former = collect($heads)->where('current', false)->values())->isNotEmpty())
            <section aria-labelledby="office-former-heads">
                <h3 id="office-former-heads" class="flex items-center gap-3 text-xl md:text-2xl font-bold text-neutral-800 mb-6">
                    <span class="p-2 bg-primary/10 rounded-lg text-primary"><x-theme::icon name="award" class="w-5 h-5" /></span>
                    {{ __('office::office.page.former_heads') }} <span class="text-gray-400 font-medium">({{ str_pad((string) $former->count(), 2, '0', STR_PAD_LEFT) }})</span>
                </h3>
                <ol class="relative border-l-2 border-gray-200 ml-5 space-y-5" role="list">
                    @foreach ($former as $head)
                        <li class="relative pl-8">
                            <span class="absolute -left-[11px] top-7 w-5 h-5 rounded-full bg-white border-4 border-primary" aria-hidden="true"></span>
                            <div class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md hover:border-primary/30 transition flex flex-col sm:flex-row sm:items-center gap-4">
                                @if ($head['photo'])
                                    <img src="{{ $head['photo'] }}" alt="{{ $head['name'] }}" loading="lazy" class="w-14 h-14 rounded-full object-cover shrink-0 border border-gray-200">
                                @else
                                    <span class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center text-lg font-bold shrink-0" aria-hidden="true">{{ $head['initials'] }}</span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-lg font-semibold text-gray-900">{{ $head['name'] }}</p>
                                    <span class="inline-block mt-1 text-xs font-semibold bg-blue-50 text-primary px-2.5 py-1 rounded-full">{{ $head['label'] }}</span>
                                </div>
                                @if ($head['from'] || $head['to'])
                                    <div class="sm:text-right shrink-0">
                                        <p class="inline-flex items-center gap-2 text-sm font-medium text-gray-700"><x-theme::icon name="calendar" class="w-4 h-4 text-gray-400" />{{ $head['from'] }} – {{ $head['to'] }}</p>
                                        @if ($head['years'])<p class="text-xs text-gray-500 mt-1">{{ $head['years'] }}</p>@endif
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </div>
@endif
