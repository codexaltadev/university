{{-- DIU "Downloadable Materials" beside a program; the brochure first (FR-PROG-17). Contract: $materials (ProgramMaterial with an address), $brochure (?MediaItem) --}}
@if ($brochure || $materials->isNotEmpty())
    <div class="rounded-xl bg-white mt-10">
        <h3 class="flex items-center gap-2 text-xl font-bold text-neutral-600 mb-4 border-b border-gray-300 pb-3">
            <x-theme::icon name="file-text" class="w-5 h-5" />
            {{ __('theme::theme.program.materials') }}
        </h3>
        <div class="space-y-3">
            @if ($brochure)
                <div class="bg-white transition-all group">
                    <a href="{{ $brochure->url() }}" download class="flex items-center gap-4 group hover:underline hover:text-secondary font-semibold text-neutral-800 text-[18px] line-clamp-2">
                        <x-theme::icon name="download" class="w-4 h-4 shrink-0 group-hover:text-secondary" />
                        {{ __('program::program.brochure') }}
                    </a>
                </div>
            @endif
            @foreach ($materials as $material)
                <div class="bg-white transition-all group">
                    <a href="{{ $material->href() }}" target="_blank" rel="noopener" class="flex items-center gap-4 group hover:underline hover:text-secondary font-semibold text-neutral-800 text-[18px] line-clamp-2">
                        <x-theme::icon name="download" class="w-4 h-4 shrink-0 group-hover:text-secondary" />
                        {{ $material->title }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
