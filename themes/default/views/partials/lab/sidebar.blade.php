{{--
    DIU LabSidebar (components/Lab/LabSidebar.js): on phones a box with the current lab that opens the
    list; on larger screens a sticky "Laboratories" card. Contract: $labs (list of [name, url, current])
--}}
<div class="lg:hidden" x-data="{ open: false }">
    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
        <button type="button" @click="open = ! open" :aria-expanded="open" class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50/50 transition-all duration-200 cursor-pointer">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-0.5">{{ __('lab::lab.page.current') }}</p>
                <p class="text-sm font-bold text-gray-900 truncate">{{ collect($labs)->firstWhere('current', true)['name'] ?? __('lab::lab.page.laboratories') }}</p>
            </div>
            <span class="p-1.5 rounded-full bg-gray-50 transition-colors" :class="open && 'bg-gray-100'">
                <x-theme::icon name="chevron-down" class="w-4 h-4 text-gray-600 transition-transform duration-300 ease-in-out" ::class="open && 'rotate-180'" />
            </span>
        </button>
        <div x-show="open" x-cloak class="border-t border-gray-100 max-h-[60vh] overflow-y-auto bg-gray-50/30">
            <div class="py-2">
                @foreach ($labs as $lab)
                    <a href="{{ $lab['url'] }}" @if ($lab['current']) aria-current="page" @endif
                       @class(['block px-5 py-3 text-sm transition-all duration-200 hover:no-underline', 'bg-white text-blue-600 font-bold border-r-4 border-blue-600 shadow-sm' => $lab['current'], 'text-gray-600 hover:text-gray-900 hover:bg-white/50' => ! $lab['current']])>{{ $lab['name'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="hidden lg:block">
    <div class="sticky top-24">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-8rem)]">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ __('lab::lab.page.laboratories') }}</h2>
            </div>
            <nav class="p-3 overflow-y-auto flex-1" aria-label="{{ __('lab::lab.page.laboratories') }}">
                <div class="space-y-1">
                    @foreach ($labs as $lab)
                        <a href="{{ $lab['url'] }}" @if ($lab['current']) aria-current="page" @endif
                           @class(['group flex items-center px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 hover:no-underline', 'bg-gray-900 text-white shadow-md shadow-gray-200 translate-x-1' => $lab['current'], 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:translate-x-1' => ! $lab['current']])>
                            <span @class(['w-1.5 h-1.5 rounded-full mr-3 shrink-0 transition-all', 'bg-blue-400 scale-125' => $lab['current'], 'bg-gray-300 group-hover:bg-gray-400' => ! $lab['current']])></span>
                            <span class="truncate">{{ $lab['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>
            @if (count($labs) > 5)
                <div class="px-6 py-3 border-t border-gray-50 bg-gray-50/30">
                    <p class="text-[10px] text-center text-gray-400 font-medium">{{ __('lab::lab.page.scroll') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
