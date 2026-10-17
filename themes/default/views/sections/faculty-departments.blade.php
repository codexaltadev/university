{{--
    DIU Faculty Department: title and line; the faculty's departments as a ruled list and the quick
    links on the left; "Meet Our Dean" (the faculty's dean) on the right.
    Contract: $section (title, subheading, content: heading, subheading, list_title, quick_links_title,
    quick_links[label, url], dean_title), $items (Department), $context
--}}
<div class="bg-white py-16">
    <div class="container mx-auto px-4">
        <x-theme::heading tag="h2" size="md">{{ $section->title ?: $section->content['heading'] }}</x-theme::heading>
        <p class="text-gray-600 mb-8 text-lg">{{ $section->subheading ?: $section->content['subheading'] }}</p>

        <div class="grid md:grid-cols-2 gap-16">
            <div class="w-full">
                @if ($items && count($items))
                    <div class="mb-8">
                        <div class="pb-4 border-b border-gray-300 text-xl font-bold text-neutral-600">{{ $section->content['list_title'] }}</div>
                        @foreach ($items as $department)
                            <div class="text-lg md:text-[24px] font-medium pt-6 pb-2 border-b border-gray-300 text-gray-700">
                                <a href="{{ url($department->url()) }}" class="block max-sm:max-w-[250px] hover:text-primary hover:no-underline transition-colors">{{ $department->name }}</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (count($section->content['quick_links']))
                    <div class="rounded-xl">
                        <div class="pb-2 border-b border-gray-300 flex items-center gap-2">
                            <x-theme::icon name="link" class="w-4 h-4" />
                            <h3 class="text-xl font-bold text-neutral-600">{{ $section->content['quick_links_title'] }}</h3>
                        </div>
                        <div class="flex w-full flex-wrap mt-4 gap-2">
                            @foreach ($section->content['quick_links'] as $link)
                                <x-theme::button :href="$link['url'] ?: '#'" :target="str_starts_with((string) $link['url'], 'http') ? '_blank' : null" variant="outlineSecondary" rounded="full" padding="px-4 py-[10px]" size="text-base md:text-lg" class="min-w-max">{{ $link['label'] }}</x-theme::button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($context->entity && method_exists($context->entity, 'people') && ($deans = $context->entity->people('dean'))->isNotEmpty())
                <div>
                    <x-theme::heading tag="h2" size="md">{{ $section->content['dean_title'] }}</x-theme::heading>
                    <div class="grid md:grid-cols-2 gap-5 mt-10">
                        @foreach ($deans as $dean)
                            <a href="{{ $dean->profileUrl() ?: '#' }}" class="bg-white rounded-lg p-0 hover:no-underline">
                                @if ($dean->photoUrl())
                                    <img src="{{ $dean->photoUrl() }}" alt="{{ $dean->name() }}" loading="lazy" class="md:w-[295px] md:h-[371px] object-cover rounded-lg mb-4">
                                @endif
                                <h4 class="font-bold text-primary mb-1 text-xl">{{ $dean->name() }}</h4>
                                <p class="text-sm md:text-lg text-primary leading-relaxed">{{ $dean->designation() }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
