{{-- One dropdown panel (DIU DepartmentMegaMenu): the item's name over a rule, then its links with a chevron that slides in on hover; a link with children opens its own panel beside. Contract: $item, $index --}}
<div class="nav__panel absolute top-full mt-0 left-0 bg-white shadow-2xl border border-gray-100 rounded-lg z-[100] min-w-80 max-w-md" x-show="open === {{ $index }}" x-transition.opacity x-cloak>
    <div class="px-8 py-8">
        <div class="pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-gray-900 font-semibold text-lg tracking-tight">{{ $item->label }}</h3>
        </div>
        @include('theme::partials.menus.panel-list', ['links' => $item->children])
    </div>
</div>
