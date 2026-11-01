{{--
    Job listings block as DIU's /career "Available Job Listings" (DIUFronend career/page): Type and Level buttons and
    "Show expired jobs" (filtering in place), then a card per opening — logo, title, company, type, location, workplace,
    level, vacancies, deadline, "Expired" — with View & Apply on skill.jobs. Contract: $data['heading']; $jobs
    (JobsBlock::jobs) or null when SkillJobs could not be reached; $index (per job: type, level, expired); $types, $levels
--}}
<section class="not-prose" x-data="{ type: '', level: '', expired: false, items: @js($index),
    ok(i) { const [t, l, e] = this.items[i]; return (this.type === '' || t === this.type) && (this.level === '' || l === this.level) && (this.expired || ! e) },
    get count() { return this.items.filter((_, i) => this.ok(i)).length } }">
    @if (filled($data['heading'] ?? null))
        <h2 class="text-3xl font-bold text-gray-900 mb-8">{{ $data['heading'] }}</h2>
    @endif

    @if ($jobs === null)
        <div class="text-center py-12 border border-gray-200 bg-gray-50"><p class="text-red-600">{{ __('page::page.jobs.unreachable') }}</p></div>
    @else
        <div class="mb-8 flex flex-wrap gap-4 items-center">
            @foreach ([['type', __('page::page.jobs.type'), $types], ['level', __('page::page.jobs.level'), $levels]] as [$key, $label, $options])
                @if ($options !== [])
                    <div class="flex flex-wrap gap-2">
                        <span class="text-sm font-medium text-gray-700 self-center">{{ $label }}</span>
                        <button type="button" @click="{{ $key }} = ''" :class="{{ $key }} === '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
                                class="px-4 py-2 text-sm border transition-colors cursor-pointer">{{ __('page::page.jobs.all') }}</button>
                        @foreach ($options as $value)
                            <button type="button" @click="{{ $key }} = @js((string) $value)" :aria-pressed="{{ $key }} === @js((string) $value)"
                                    :class="{{ $key }} === @js((string) $value) ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
                                    class="px-4 py-2 text-sm border transition-colors cursor-pointer">{{ $value }}</button>
                        @endforeach
                    </div>
                @endif
            @endforeach
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="expired" class="w-4 h-4">
                <span class="text-sm text-gray-700">{{ __('page::page.jobs.expired') }}</span>
            </label>
        </div>

        <div class="space-y-4">
            @foreach ($jobs as $i => $job)
                <article x-show="ok({{ $i }})" @if ($job['expired']) x-cloak @endif
                         @class(['border p-6 transition-colors', 'border-gray-200 bg-gray-50 opacity-75' => $job['expired'], 'border-gray-200 hover:border-gray-400 bg-white' => ! $job['expired']])>
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4 min-w-0">
                            @if ($job['logo'])
                                <img src="{{ $job['logo'] }}" alt="{{ $job['company'] }}" loading="lazy" class="w-12 h-12 object-contain border border-gray-200 shrink-0">
                            @endif
                            <div class="min-w-0">
                                <h3 class="text-xl font-bold text-gray-900">{{ $job['title'] }}</h3>
                                @if ($job['company'])<p class="text-gray-600 text-sm mt-0.5">{{ $job['company'] }}</p>@endif
                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-sm text-gray-600">
                                    @foreach ([['briefcase', $job['type']], ['map-pin', $job['location']], ['building', $job['workplace']], ['trending-up', $job['level']]] as [$icon, $value])
                                        @if ($value)<span class="inline-flex items-center gap-1.5"><x-theme::icon :name="$icon" class="w-4 h-4 shrink-0" />{{ $value }}</span>@endif
                                    @endforeach
                                    @if ($job['vacancies'])<span class="inline-flex items-center gap-1.5"><x-theme::icon name="users" class="w-4 h-4 shrink-0" />{{ trans_choice('page::page.jobs.vacancy', $job['vacancies'], ['count' => $job['vacancies']]) }}</span>@endif
                                    @if ($job['deadline'])<span class="inline-flex items-center gap-1.5"><x-theme::icon name="calendar" class="w-4 h-4 shrink-0" />{{ __('page::page.jobs.deadline', ['date' => $job['deadline']]) }}</span>@endif
                                </div>
                                @if ($job['expired'])
                                    <span class="inline-block mt-3 px-3 py-1 text-xs font-medium bg-gray-200 text-gray-600">{{ __('page::page.jobs.expired_badge') }}</span>
                                @endif
                            </div>
                        </div>
                        @unless ($job['expired'])
                            <a href="{{ $job['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium bg-gray-900 text-white hover:bg-gray-700 transition-colors shrink-0">
                                {{ __('page::page.jobs.apply') }} <x-theme::icon name="chevron-right" class="w-4 h-4" />
                            </a>
                        @endunless
                    </div>
                </article>
            @endforeach

            <div x-show="count === 0" @if ($jobs !== []) x-cloak @endif class="text-center py-12 border border-gray-200 bg-gray-50">
                <x-theme::icon name="briefcase" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-600 mb-2">{{ __('page::page.jobs.none') }}</p>
                <p class="text-gray-500 text-sm">{{ __('page::page.jobs.none_text') }}</p>
            </div>
        </div>
    @endif
</section>
