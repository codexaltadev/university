{{--
    /support-contacts as DIU's /support-contact (DIUFronend app/(public)/support-contact, components/contact): hero "Support
    Contact Directory"; a white box with the search and the Purpose / Office filters (a search sets the filters aside, chips
    for what is on); then a card per person (name, designation, office, where, phone, emails, extension, the purpose chip).
    Filtering in place with Alpine; ?q, ?purpose, ?office open the page filtered. Title, text and picture from Settings › Offices.
    Contract: $page (SupportDirectory)
--}}
<x-theme::layout :title="$page->title()">
    @include('theme::partials.diu.hero', [
        'title' => $page->title(),
        'subtitle' => $page->subtitle() ? e($page->subtitle()) : null,
        'image' => $page->image() ?? theme_image('page_hero_image', 'images/page-hero.webp'),
        'breadcrumbs' => [['label' => __('theme::theme.home'), 'url' => url('/')], ['label' => $page->title(), 'url' => url('/support-contacts')]],
    ])

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50"
         x-data="{
            ...@js($page->filters()),
            open: false,
            items: @js($page->filterIndex()),
            offices: @js($page->officeNames()),
            ok(i) {
                const [s, p, o] = this.items[i], q = this.q.trim().toLowerCase();
                return q !== '' ? s.includes(q) : (this.purpose === '' || p === this.purpose) && (this.office === '' || o === this.office);
            },
            get count() { return this.items.filter((_, i) => this.ok(i)).length },
         }">
        <div class="container mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-lg border border-gray-100 p-6 shadow-sm mb-8">
                <div class="relative mb-4">
                    <x-theme::icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
                    <input type="search" x-model="q" placeholder="{{ __('office::office.directory.search') }}" aria-label="{{ __('office::office.directory.search') }}"
                           class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-colors">
                    <button type="button" x-show="q" x-cloak @click="q = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" aria-label="{{ __('office::office.directory.clear') }}">
                        <x-theme::icon name="close" class="w-5 h-5" />
                    </button>
                </div>

                <button type="button" @click="open = ! open" class="md:hidden w-full flex items-center justify-center gap-2 py-2 text-primary font-medium" :aria-expanded="open">
                    <x-theme::icon name="filter" class="w-4 h-4" />
                    <span x-text="open ? @js(__('office::office.directory.hide_filters')) : @js(__('office::office.directory.show_filters'))">{{ __('office::office.directory.show_filters') }}</span>
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 md:mt-0" :class="{ 'max-md:hidden': ! open }">
                    <div>
                        <label for="support-purpose" class="block text-sm font-medium text-gray-700 mb-2">{{ __('office::office.directory.purpose') }}</label>
                        <select id="support-purpose" x-model="purpose" :disabled="q.trim() !== ''"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">{{ __('office::office.directory.all_purposes') }}</option>
                            @foreach ($page->purposes() as $purpose)
                                <option value="{{ $purpose }}">{{ $purpose }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="support-office" class="block text-sm font-medium text-gray-700 mb-2">{{ __('office::office.directory.office') }}</label>
                        <select id="support-office" x-model="office" :disabled="q.trim() !== ''"
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">{{ __('office::office.directory.all_offices') }}</option>
                            @foreach ($page->offices() as $office)
                                <option value="{{ $office['id'] }}">{{ $office['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="q || purpose || office" x-cloak class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                    <span x-show="q" class="inline-flex items-center gap-1 px-3 py-1 bg-secondary/10 text-secondary text-sm rounded-full">
                        {{ __('office::office.directory.searching') }}: "<span x-text="q"></span>"
                        <button type="button" @click="q = ''" aria-label="{{ __('office::office.directory.clear') }}"><x-theme::icon name="close" class="w-3 h-3" /></button>
                    </span>
                    <span x-show="purpose && ! q" class="inline-flex items-center gap-1 px-3 py-1 bg-primary/10 text-primary text-sm rounded-full">
                        {{ __('office::office.directory.purpose') }}: <span x-text="purpose"></span>
                        <button type="button" @click="purpose = ''" aria-label="{{ __('office::office.directory.clear') }}"><x-theme::icon name="close" class="w-3 h-3" /></button>
                    </span>
                    <span x-show="office && ! q" class="inline-flex items-center gap-1 px-3 py-1 bg-primary/10 text-primary text-sm rounded-full">
                        {{ __('office::office.directory.office') }}: <span x-text="offices[office]"></span>
                        <button type="button" @click="office = ''" aria-label="{{ __('office::office.directory.clear') }}"><x-theme::icon name="close" class="w-3 h-3" /></button>
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-secondary">{{ __('office::office.directory.heading') }}</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="count > 0">
                @foreach ($page->cards() as $i => $card)
                    <article x-show="ok({{ $i }})" class="bg-white rounded-lg border border-gray-100 p-6 shadow-sm hover:shadow-md transition-all duration-300 hover:border-secondary/20">
                        <h3 class="text-lg font-semibold text-secondary mb-1">{{ $card['name'] }}</h3>
                        @if ($card['designation'])
                            <p class="text-primary font-medium mb-2">{{ $card['designation'] }}</p>
                        @endif
                        <div class="space-y-2 text-base text-gray-600">
                            <div class="flex items-center gap-2"><x-theme::icon name="building" class="w-4 h-4 shrink-0 text-primary" /><span>{{ $card['office'] }}</span></div>
                            @if ($card['location'])
                                <div class="flex items-center gap-2"><x-theme::icon name="map-pin" class="w-4 h-4 shrink-0 text-primary" /><span>{{ $card['location'] }}</span></div>
                            @endif
                            @if ($card['phone'])
                                <div class="flex items-center gap-2"><x-theme::icon name="phone" class="w-4 h-4 shrink-0 text-primary" /><a href="tel:{{ $card['tel'] }}" class="hover:text-secondary transition-colors">{{ $card['phone'] }}</a></div>
                            @endif
                            @if ($card['emails'])
                                <div class="flex items-start gap-2"><x-theme::icon name="mail" class="w-4 h-4 mt-1 shrink-0 text-primary" />
                                    <div class="flex flex-col min-w-0">
                                        @foreach ($card['emails'] as $email)
                                            <a href="mailto:{{ $email }}" class="hover:text-secondary transition-colors [overflow-wrap:anywhere]">{{ $email }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if ($card['extension'])
                                <div class="flex items-center gap-2"><x-theme::icon name="phone" class="w-4 h-4 shrink-0 text-primary" /><span>{{ __('office::office.ext') }} {{ $card['extension'] }}</span></div>
                            @endif
                        </div>
                        @if ($card['purpose'])
                            <div class="mt-3"><span class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs rounded-full font-medium">{{ $card['purpose'] }}</span></div>
                        @endif
                    </article>
                @endforeach
            </div>

            <div x-show="count === 0" @if ($page->cards() !== []) x-cloak @endif class="text-center py-12">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4"><x-theme::icon name="search" class="w-8 h-8 text-primary" /></div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('office::office.directory.none') }}</h3>
                <p class="text-gray-600">{{ __('office::office.directory.none_text') }}</p>
            </div>
        </div>
    </div>
</x-theme::layout>
