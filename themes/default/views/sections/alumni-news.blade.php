{{--
    DIU News ("Alumni Stories & Achievements"): title and line; left — two counting figures over
    the featured story (picture, green category, title, date); right — four stories with picture.
    Contract: $section (title, subheading, content: heading, subheading, stats[number, suffix, label]), $items (Post)
--}}
<div class="bg-white px-4 md:px-6 py-6 md:py-6 lg:px-12">
    <div class="container mx-auto px-0">
        <div class="mb-8">
            <x-theme::heading tag="h2" size="md">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
            <p class="text-gray-600 text-lg">{{ $section->subheading ?: $section->content['subheading'] }}</p>
        </div>
        <div class="grid relative lg:grid-cols-2 gap-12">
            <div class="w-full">
                @if (count($section->content['stats']))
                    <div class="rounded-t-lg grid grid-cols-2 lg:grid-cols-2 p-2 md:p-0 bg-neutral-100 gap-8 mb-2">
                        @foreach ($section->content['stats'] as $stat)
                            <div @class(['text-center py-5 md:py-10 border-gray-200', 'md:border-r' => ! $loop->last])>
                                <div class="text-3xl md:text-4xl lg:text-5xl font-bold text-primary mb-2"><x-theme::counter :value="$stat['number'] ?? 0" :suffix="$stat['suffix'] ?? ''" /></div>
                                <div class="text-base md:text-[24px] text-gray-600 leading-tight">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($items && ($featured = collect($items)->first()))
                    <div class="w-full h-[300px] relative rounded-b-lg overflow-hidden mb-6">
                        @if ($image = $featured->featuredImage())
                            <img loading="lazy" decoding="async" src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $featured->title }}" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                    </div>
                    @if ($category = $featured->termsIn('news.category')->first())
                        <div class="inline-block text-green-700 text-sm md:text-base font-bold px-2 py-1 rounded mb-3">• {{ $category->name }}</div>
                    @endif
                    <a href="{{ url($featured->url()) }}" class="hover:underline"><x-theme::heading tag="h3" size="md">{{ $featured->title }}</x-theme::heading></a>
                    <p class="text-neutral-600 text-xs">{{ $featured->shownAt()?->format('n/j/Y') }}</p>
                @endif
            </div>
            <div class="space-y-6">
                @foreach (collect($items ?? [])->take(4) as $post)
                    <div class="grid grid-cols-12 items-center gap-4 last:border-none border-b border-gray-400 pb-5">
                        <div class="flex-1 col-span-8">
                            @if ($category = $post->termsIn('news.category')->first())
                                <div class="inline-block text-green-700 text-sm md:text-base font-bold pt-1 rounded mb-2">• {{ $category->name }}</div>
                            @endif
                            <a href="{{ url($post->url()) }}" class="hover:underline"><h4 class="font-bold text-gray-900 text-lg mb-2 leading-tight">{{ $post->title }}</h4></a>
                            <p class="text-neutral-600 text-xs">{{ $post->shownAt()?->format('n/j/Y') }}</p>
                        </div>
                        @if ($image = $post->featuredImage())
                            <img decoding="async" src="{{ $image->url('medium') ?? $image->url() }}" alt="{{ $post->title }}" loading="lazy" class="col-span-4 w-full h-[136px] object-cover rounded flex-shrink-0">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
