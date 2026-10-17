{{--
    DIU Faculty / Department News: title and line; the featured story big on the left, a rule,
    four stories on the right. Hidden when there is no news.
    Contract: $section (title, subheading, content: heading, subheading), $items (Post)
--}}
@if ($items && ($featured = collect($items)->first()))
    <div class="bg-white py-16">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <x-theme::heading tag="h2" size="md">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
                <p class="text-gray-600 text-lg">{{ $section->subheading ?: $section->content['subheading'] }}</p>
            </div>
            <div class="grid relative md:grid-cols-2 gap-12">
                <div class="hidden md:block absolute top-0 bottom-0 left-1/2 w-px bg-gray-400"></div>
                <div>
                    @if ($image = $featured->featuredImage())
                        <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $featured->title }}" class="w-full h-[250px] md:h-[444px] object-cover rounded-lg mb-6">
                    @endif
                    @if ($category = $featured->termsIn('news.category')->first())
                        <div class="inline-block text-green-600 text-base font-bold px-2 py-1 rounded mb-3">• {{ $category->name }}</div>
                    @endif
                    <a href="{{ url($featured->url()) }}" class="hover:underline"><x-theme::heading tag="h3" size="md">{{ $featured->title }}</x-theme::heading></a>
                    <p class="text-neutral-600 text-xs">{{ $featured->shownAt()?->format('n/j/Y') }}</p>
                </div>
                <div class="space-y-6">
                    @foreach (collect($items)->take(4) as $post)
                        <div class="grid grid-cols-12 items-center gap-4 last:border-none border-b border-gray-400 pb-5">
                            <div class="flex-1 col-span-8">
                                @if ($category = $post->termsIn('news.category')->first())
                                    <div class="inline-block text-green-600 text-sm md:text-base font-bold pt-1 rounded mb-2">• {{ $category->name }}</div>
                                @endif
                                <a href="{{ url($post->url()) }}" class="hover:underline"><h4 class="font-bold text-gray-900 text-lg mb-2 leading-tight">{{ $post->title }}</h4></a>
                                <p class="text-neutral-600 text-xs">{{ $post->shownAt()?->format('n/j/Y') }}</p>
                            </div>
                            @if ($image = $post->featuredImage())
                                <img src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $post->title }}" loading="lazy" class="col-span-4 w-full h-[136px] object-cover rounded flex-shrink-0">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
