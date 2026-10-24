{{-- DIU Accreditations on the program page: logo panel, title, rule, "Visit Website" (else its page). Contract: $entity, $items (in order) --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
    @foreach ($items as $accreditation)
        <div class="group relative bg-white rounded-2xl p-6 border border-gray-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.1)] hover:shadow-[0_10px_30px_-10px_rgba(0,0,0,0.15)] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
            <div class="h-40 mb-6 flex items-center justify-center p-6 bg-gray-50 rounded-xl group-hover:bg-blue-50/30 transition-colors duration-300">
                @if ($logo = $accreditation->firstMedia('logo'))
                    <img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $accreditation->title }}" loading="lazy" class="w-full h-full object-contain transition-all duration-500">
                @endif
            </div>
            <div class="flex-grow flex flex-col">
                <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-3 leading-tight group-hover:text-secondary transition-colors duration-300 line-clamp-3">{{ $accreditation->title }}</h3>
                <div class="w-12 h-1 bg-gray-100 rounded-full mb-6 group-hover:bg-secondary/30 transition-colors duration-300"></div>
                @if ($href = $accreditation->website_url ?: ($accreditation->body ? url($accreditation->url()) : null))
                    <div class="mt-auto">
                        <a href="{{ $href }}" @if ($accreditation->website_url) target="_blank" rel="noopener noreferrer" @endif
                           class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-medium text-secondary bg-blue-50 rounded-lg group-hover:bg-secondary group-hover:text-white hover:no-underline transition-all duration-300 w-full sm:w-auto">
                            {{ __('theme::theme.program.visit_website') }}
                            <x-theme::icon name="external" class="w-4 h-4 ml-2" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
