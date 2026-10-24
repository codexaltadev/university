{{-- DIU DeanCard / HeadCard: tall photo, name, a line (faculty and role, or department), "View Profile". Contract: $person (PersonView), $line --}}
<div class="bg-neutral-100 p-3 md:p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300">
    <div class="relative pb-4">
        @if ($person->photoUrl())
            <img src="{{ $person->photoUrl() }}" alt="{{ $person->name() }}" loading="lazy" class="rounded-xl w-full h-[420px] object-cover object-top">
        @else
            <div class="rounded-xl w-full h-[420px] bg-gray-200"></div>
        @endif
    </div>
    <h3 class="text-[24px] font-semibold text-neutral-800 mb-4 leading-tight">{{ $person->name() }}</h3>
    <p class="text-base text-neutral-500 mb-6 leading-relaxed line-clamp-4">{{ $line }}</p>
    <div class="bg-gray-200 mb-3 w-full h-[0.5px]"></div>
    <a href="{{ $person->profileUrl() ?: '#' }}" @if ($person->profileUrl()) target="_blank" rel="noopener" @endif class="flex items-center gap-2 text-secondary font-medium text-[24px] hover:text-sky-700 hover:no-underline transition-colors group">
        {{ __('theme::theme.faculties.view_profile') }}
        <x-theme::icon name="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
    </a>
</div>
