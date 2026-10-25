{{--
    Waiver Calculator in DIU's colours (/tuition-fee-calculator), made shorter (owner 2026-10-25): one card with three
    numbered steps — ① program (tuition category, program type, a program search grouped by faculty, its duration /
    credits / total), ② waiver category (one-line choices, the first few and "Show all"), ③ results (SSC / HSC with Golden
    GPA once the GPA is 5, CGPA, board, gender) — and "Calculate My Fees"; on the right DIU's result panel (Congratulations!,
    the waiver %, Program, Fee Summary, Payable During Admission, total after waiver, Print / Reset), on phones scrolled to.
    Contract (Waiver module): wire:model feeCategoryId, levelId, tuitionId (entangled), waiverCategoryId, boardId, gender, ssc,
    sscGolden, hsc, hscGolden, cgpa; wire:click calculate, clear; $feeCategories, $levels, $boards, $genders, $fields, $diploma,
    $feeCategoryId, $levelId; $calc (CalculatorForm)
--}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start">
    <form wire:submit="calculate" class="lg:col-span-2 rounded-lg border border-slate-200 bg-white shadow-sm divide-y divide-slate-200 no-print">
        {{-- ① Program --}}
        <section class="p-5 sm:p-6" aria-labelledby="waiver-step-1">
            <h2 id="waiver-step-1" class="flex items-center gap-3 text-lg font-bold text-slate-900 mb-5">
                <span class="grid place-items-center size-7 shrink-0 rounded-full bg-primary text-white text-sm">1</span>{{ __('waiver::waiver.calculator.step_program') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1.5">
                    <span class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.tuition_category') }}</span>
                    <select wire:model.live="feeCategoryId" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors">
                        @foreach ($feeCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block space-y-1.5">
                    <span class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.program_type') }}</span>
                    <select wire:model.live="levelId" @disabled($levels->count() < 2) class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors disabled:bg-slate-50">
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="sm:col-span-2 space-y-1.5" wire:key="waiver-programs-{{ $feeCategoryId }}-{{ $levelId }}"
                     x-data="{
                        items: @js($calc->programs()), id: $wire.entangle('tuitionId').live, q: @js((string) $calc->programLabel()), open: false, active: 0,
                        label() { const i = this.items.find(i => i.id == this.id); return i ? i.label : '' },
                        get list() { const t = this.q.trim().toLowerCase(); return ! t || t === this.label().toLowerCase() ? this.items : this.items.filter(i => t.split(/\s+/).every(w => i.terms.includes(w))) },
                        show() { this.open = true; this.active = Math.max(0, this.list.findIndex(i => i.id == this.id)) },
                        pick(i) { this.id = i.id; this.q = i.label; this.open = false },
                        close() { this.open = false; this.q = this.label() },
                        move(d) { if (! this.open) return this.show(); this.active = Math.max(0, Math.min(this.list.length - 1, this.active + d)); this.$nextTick(() => this.$refs.list.querySelector('[data-active]')?.scrollIntoView({ block: 'nearest' })) },
                     }" x-on:click.outside="close()">
                    <label for="waiver-program" class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.program') }}<span class="text-red-600 ml-1">*</span></label>
                    <div class="relative">
                        <x-theme::icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400 pointer-events-none" />
                        <input id="waiver-program" type="text" x-model="q" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="waiver-program-list" x-bind:aria-expanded="open"
                               placeholder="{{ __('waiver::waiver.calculator.search_program') }}"
                               x-on:focus="$el.select(); show()" x-on:input="open = true; active = 0"
                               x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                               x-on:keydown.enter.prevent="list[active] && pick(list[active])" x-on:keydown.escape.prevent="close(); $el.blur()" x-on:keydown.tab="close()"
                               @class(['w-full rounded-lg border bg-white pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors', 'border-red-300' => $errors->has('tuitionId'), 'border-slate-300' => ! $errors->has('tuitionId')])>
                        <x-theme::icon name="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400 pointer-events-none" />
                        <ul id="waiver-program-list" x-ref="list" role="listbox" x-show="open" x-cloak x-transition.opacity
                            class="absolute z-30 mt-1 w-full max-h-72 overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl">
                            <template x-for="(item, n) in list" :key="item.id">
                                <li>
                                    <div x-show="item.group && (n === 0 || list[n - 1].group !== item.group)" x-text="item.group"
                                         class="px-4 pt-2.5 pb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400"></div>
                                    <div role="option" x-bind:aria-selected="item.id == id" x-bind:data-active="n === active ? true : null"
                                         x-on:mousedown.prevent="pick(item)" x-on:mousemove="active = n"
                                         x-bind:class="n === active ? 'bg-blue-50 text-blue-900' : 'text-slate-700'"
                                         class="flex items-center justify-between gap-3 px-4 py-2 text-sm cursor-pointer">
                                        <span x-text="item.label"></span>
                                        <x-theme::icon name="check" class="size-4 text-blue-600 shrink-0" x-show="item.id == id" />
                                    </div>
                                </li>
                            </template>
                            <li x-show="list.length === 0" class="px-4 py-3 text-sm text-slate-500">{{ __('waiver::waiver.calculator.no_program') }}</li>
                        </ul>
                    </div>
                    @error('tuitionId') <p class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</p> @enderror
                    @if ($facts = $calc->program())
                        <dl class="grid grid-cols-3 gap-2 pt-1.5">
                            @foreach ([['clock', $facts['duration'], 'fields.duration'], ['book-open', $facts['credits'], 'fields.credits'], ['banknote', $facts['total'], 'calculator.total_fees']] as [$icon, $value, $label])
                                <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 border border-slate-200 min-w-0">
                                    <x-theme::icon :name="$icon" class="size-4 shrink-0 text-slate-400 max-sm:hidden" />
                                    <div class="min-w-0">
                                        <dt class="text-[11px] text-slate-500 leading-tight">{{ __('waiver::waiver.'.$label) }}</dt>
                                        <dd class="font-semibold text-slate-900 text-xs truncate">{{ $value }}</dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </section>

        {{-- ② Waiver category --}}
        <section class="p-5 sm:p-6" aria-labelledby="waiver-step-2" x-data="{ all: false }">
            <h2 id="waiver-step-2" class="flex items-center gap-3 text-lg font-bold text-slate-900 mb-5">
                <span class="grid place-items-center size-7 shrink-0 rounded-full bg-primary text-white text-sm">2</span>{{ __('waiver::waiver.calculator.step_category') }}
            </h2>
            <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-labelledby="waiver-step-2">
                @foreach ($tiles = $calc->tiles() as $tile)
                    <label wire:key="waiver-tile-{{ $tile['id'] }}" @if ($loop->index >= $calc->visibleTiles()) x-show="all" x-cloak @endif
                           @class(['relative flex items-start gap-2.5 rounded-lg border-2 px-3.5 py-3 cursor-pointer transition-colors has-focus-visible:ring-2 has-focus-visible:ring-blue-500/40', 'border-blue-500 bg-blue-50' => $tile['selected'], 'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50' => ! $tile['selected']])>
                        <input type="radio" name="waiver-category" value="{{ $tile['id'] }}" wire:model.live="waiverCategoryId" class="sr-only">
                        <x-theme::icon :name="$tile['icon']" @class(['size-5 shrink-0 mt-px', 'text-blue-600' => $tile['selected'], 'text-slate-400' => ! $tile['selected']]) />
                        <span class="min-w-0 flex-1">
                            <span @class(['block text-sm font-semibold leading-snug line-clamp-2', 'text-blue-900' => $tile['selected'], 'text-slate-800' => ! $tile['selected']]) title="{{ $tile['name'] }}">{{ $tile['name'] }}</span>
                            @if ($tile['text'])<span @class(['block mt-0.5 text-xs line-clamp-2', 'text-blue-700' => $tile['selected'], 'text-slate-500' => ! $tile['selected']])>{{ $tile['text'] }}</span>@endif
                        </span>
                        @if ($tile['selected'])
                            <span class="absolute -top-2 -right-2 rounded-full bg-blue-600 p-1 shadow border-2 border-white"><x-theme::icon name="check" class="size-3 text-white" /></span>
                        @endif
                    </label>
                @endforeach
            </div>
            @if ($calc->visibleTiles() < count($tiles))
                <button type="button" x-on:click="all = ! all" x-bind:aria-expanded="all" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-800 cursor-pointer">
                    <span x-show="! all">{{ __('waiver::waiver.calculator.show_all', ['count' => count($tiles)]) }}</span>
                    <span x-show="all" x-cloak>{{ __('waiver::waiver.calculator.show_less') }}</span>
                    <x-theme::icon name="chevron-down" class="size-4 transition-transform" x-bind:class="all && 'rotate-180'" />
                </button>
            @endif
            @error('waiverCategoryId') <p class="mt-3 flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</p> @enderror
        </section>

        {{-- ③ Results and the rest --}}
        <section class="p-5 sm:p-6" aria-labelledby="waiver-step-3">
            <h2 id="waiver-step-3" class="flex items-center gap-3 text-lg font-bold text-slate-900 mb-5">
                <span class="grid place-items-center size-7 shrink-0 rounded-full bg-primary text-white text-sm">3</span>{{ __('waiver::waiver.calculator.step_results') }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($fields['school'])
                    <div class="space-y-1.5">
                        <label for="waiver-ssc" class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.ssc_long') }}<span class="text-red-600 ml-1">*</span></label>
                        <input id="waiver-ssc" type="number" step="0.01" min="0" max="5" inputmode="decimal" wire:model.live.blur="ssc" placeholder="{{ __('waiver::waiver.calculator.gpa_of', ['max' => '5.00']) }}"
                               @class(['w-full rounded-lg border px-4 py-2.5 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors', 'border-red-300' => $errors->has('ssc'), 'border-slate-300' => ! $errors->has('ssc')])>
                        @if ($calc->goldenSsc())
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" wire:model.live="sscGolden" class="size-4 rounded border-slate-300 text-blue-600 bg-white">
                                <x-theme::icon name="star" class="size-4 text-amber-500" /><span class="text-slate-700">{{ __('waiver::waiver.calculator.golden_gpa') }}</span>
                            </label>
                        @endif
                        @error('ssc') <p class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="waiver-hsc" class="block text-sm font-semibold text-slate-900">{{ $diploma ? __('waiver::waiver.calculator.diploma') : __('waiver::waiver.calculator.hsc_long') }}<span class="text-red-600 ml-1">*</span></label>
                        <input id="waiver-hsc" type="number" step="0.01" min="0" max="{{ $diploma ? 4 : 5 }}" inputmode="decimal" wire:model.live.blur="hsc"
                               placeholder="{{ $diploma ? __('waiver::waiver.calculator.cgpa_of', ['max' => '4.00']) : __('waiver::waiver.calculator.gpa_of', ['max' => '5.00']) }}"
                               @class(['w-full rounded-lg border px-4 py-2.5 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors', 'border-red-300' => $errors->has('hsc'), 'border-slate-300' => ! $errors->has('hsc')])>
                        @if ($calc->goldenHsc())
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" wire:model.live="hscGolden" class="size-4 rounded border-slate-300 text-blue-600 bg-white">
                                <x-theme::icon name="star" class="size-4 text-amber-500" /><span class="text-slate-700">{{ __('waiver::waiver.calculator.golden_gpa') }}</span>
                            </label>
                        @endif
                        @error('hsc') <p class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($fields['cgpa'])
                    <div class="space-y-1.5">
                        <label for="waiver-cgpa" class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.cgpa') }}<span class="text-red-600 ml-1">*</span></label>
                        <input id="waiver-cgpa" type="number" step="0.01" min="0" max="4" inputmode="decimal" wire:model.live.blur="cgpa" placeholder="{{ __('waiver::waiver.calculator.cgpa_of', ['max' => '4.00']) }}"
                               @class(['w-full rounded-lg border px-4 py-2.5 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors', 'border-red-300' => $errors->has('cgpa'), 'border-slate-300' => ! $errors->has('cgpa')])>
                        @error('cgpa') <p class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($boards->isNotEmpty())
                    <label class="block space-y-1.5">
                        <span class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.board_long') }}<span class="text-red-600 ml-1">*</span></span>
                        <select wire:model.live="boardId" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors">
                            <option value="">{{ __('waiver::waiver.calculator.choose') }}</option>
                            @foreach ($boards as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('boardId') <span class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</span> @enderror
                    </label>
                @endif
                <div class="space-y-1.5">
                    <span id="waiver-gender" class="block text-sm font-semibold text-slate-900">{{ __('waiver::waiver.calculator.gender') }}<span class="text-red-600 ml-1">*</span></span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="waiver-gender">
                        @foreach ($genders as $item)
                            <label class="flex-1 min-w-24 cursor-pointer">
                                <input type="radio" name="waiver-gender" value="{{ $item }}" wire:model.live="gender" class="peer sr-only">
                                <span class="block rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm text-slate-700 transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-900 peer-checked:font-semibold peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500/40 hover:border-blue-300">{{ __("waiver::waiver.genders.{$item}") }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('gender') <span class="flex items-center gap-2 text-xs text-red-600"><x-theme::icon name="alert-circle" class="size-3.5" /> {{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <div class="p-5 sm:p-6 bg-slate-50 rounded-b-lg">
            <button type="submit" wire:loading.attr="disabled" wire:target="calculate"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-xl cursor-pointer">
                <x-theme::icon name="zap" class="size-5" />
                <span wire:loading.remove wire:target="calculate">{{ __('waiver::waiver.calculator.calculate_mine') }}</span>
                <span wire:loading wire:target="calculate">{{ __('waiver::waiver.calculator.calculating') }}</span>
                <x-theme::icon name="arrow-right" class="size-5" />
            </button>
        </div>
    </form>

    <div class="lg:col-span-1 lg:sticky lg:top-24" aria-live="polite">
        @if ($result = $calc->result())
            <div class="rounded-lg border border-slate-200 bg-white shadow-lg overflow-hidden scroll-mt-24" x-init="window.innerWidth < 1024 && $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                <div class="bg-gradient-to-br from-primary to-blue-700 px-6 pt-8 pb-12 text-center">
                    <h3 class="text-2xl font-bold text-white">{{ __('waiver::waiver.calculator.congratulations') }}</h3>
                    <p class="mt-2 text-blue-100 text-sm">{{ $result['message'] }}</p>
                </div>
                <div class="relative -mt-6 flex justify-center px-4">
                    <div class="rounded-xl bg-gradient-to-br from-blue-500 to-primary px-8 py-6 shadow-lg border border-blue-400/30 text-center">
                        <div class="text-5xl font-bold text-white">{{ $result['percent'] }}</div>
                        <div class="text-sm text-blue-100 mt-3 font-semibold">{{ $result['category'] }}</div>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <div class="space-y-3 border-b border-slate-200">
                        <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2"><x-theme::icon name="graduation-cap" class="size-4 text-blue-600" /> {{ __('waiver::waiver.calculator.program') }}</h4>
                        <div class="text-sm text-slate-700">
                            <p class="py-2 border-b border-slate-200">{{ $result['program'] }}</p>
                            @if ($result['duration'])<div class="pt-2 pb-1 grid grid-cols-2"><p>{{ __('waiver::waiver.fields.duration') }}</p><p class="text-right">{{ $result['duration'] }}</p></div>@endif
                            @if ($result['credits'])<div class="pb-2 grid grid-cols-2"><p>{{ __('waiver::waiver.calculator.total_credit_hour') }}</p><p class="text-right">{{ $result['credits'] }}</p></div>@endif
                        </div>
                    </div>

                    <div class="space-y-3 pb-5 border-b border-slate-200">
                        <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2"><x-theme::icon name="banknote" class="size-4 text-blue-600" /> {{ __('waiver::waiver.calculator.fee_summary') }}</h4>
                        <dl class="space-y-2 text-sm">
                            @foreach ([['calculator.total_cost', $result['total']], ['calculator.tuition_fees', $result['tuition']], ['calculator.other_fees', $result['other']]] as [$label, $value])
                                <div class="flex items-center justify-between gap-3"><dt class="text-slate-600 font-medium">{{ __('waiver::waiver.'.$label) }}</dt><dd class="font-semibold text-slate-900 text-right">{{ $value }}</dd></div>
                            @endforeach
                            <div class="flex items-center justify-between gap-3"><dt class="text-slate-600 font-medium">{{ __('waiver::waiver.calculator.waiver_amount') }}</dt><dd class="font-semibold text-green-600 text-right">− {{ $result['waiver'] }}</dd></div>
                        </dl>
                    </div>

                    @if ($result['admission'])
                        <div class="rounded-lg bg-blue-50 p-4 border border-blue-200 text-center">
                            <div class="text-xs text-blue-700 font-semibold mb-1">{{ __('waiver::waiver.calculator.payable_admission') }}</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $result['admission'] }}</div>
                            <p class="text-xs text-blue-700 font-semibold mb-1">{{ __('waiver::waiver.calculator.from_first_semester') }}</p>
                        </div>
                    @endif

                    <div class="rounded-lg bg-green-50 p-4 text-center border border-green-200">
                        <div class="text-xs text-green-700 font-semibold mb-1">{{ __('waiver::waiver.calculator.after_waiver_total') }}</div>
                        <div class="text-2xl font-bold text-green-600">{{ $result['final'] }}</div>
                    </div>

                    @if ($result['range'])<p class="text-xs text-slate-500">{{ __('waiver::waiver.calculator.range_note') }}</p>@endif
                    @if ($result['condition'])<p class="text-xs text-slate-600 rounded-lg bg-amber-50 border border-amber-200 p-3">{{ $result['condition'] }}</p>@endif

                    <div class="flex gap-3 pt-4 border-t border-slate-200 no-print">
                        <button type="button" onclick="window.print()" class="flex-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 cursor-pointer"><x-theme::icon name="download" class="size-4" /> {{ __('waiver::waiver.print') }}</button>
                        <button type="button" wire:click="clear" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2 cursor-pointer"><x-theme::icon name="home" class="size-4" /> {{ __('waiver::waiver.calculator.reset') }}</button>
                    </div>
                </div>
            </div>
        @elseif ($sorry = $calc->sorry())
            <div class="rounded-lg border border-slate-200 bg-white shadow-lg overflow-hidden text-center scroll-mt-24" x-init="window.innerWidth < 1024 && $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                <div class="bg-gradient-to-br from-secondary to-primary px-6 py-8">
                    <h3 class="text-2xl font-bold text-white">{{ __('waiver::waiver.calculator.sorry') }}</h3>
                </div>
                <div class="p-6 space-y-5">
                    <p class="text-sm text-slate-700">{{ $sorry }}</p>
                    <button type="button" wire:click="clear" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center justify-center gap-2 cursor-pointer"><x-theme::icon name="home" class="size-4" /> {{ __('waiver::waiver.calculator.reset') }}</button>
                </div>
            </div>
        @else
            <div class="hidden lg:block rounded-lg border border-dashed border-slate-300 bg-white/60 p-8 text-center">
                <span class="mx-auto grid place-items-center size-14 rounded-full bg-blue-50 text-blue-600 mb-4"><x-theme::icon name="calculator" class="size-7" /></span>
                <h3 class="text-lg font-bold text-slate-900">{{ __('waiver::waiver.calculator.estimate') }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ __('waiver::waiver.calculator.estimate_text') }}</p>
            </div>
        @endif
    </div>
    <style>@media print { .no-print, header, footer { display: none !important } }</style>
</div>
