{{-- DIU News "Explore the Inspiring Stories": the yellow call with More News, then three posts. Contract: $cards (up to 3), $more (url) --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-amber-400 rounded-xl p-8 flex flex-col justify-between min-h-[250px]">
        <h2 class="text-2xl font-bold text-neutral-900 leading-snug">{{ settings('news.explore_title') }}</h2>
        <a href="{{ $more }}" class="self-start mt-6 bg-white text-neutral-900 px-4 py-1.5 hover:no-underline hover:bg-neutral-100">{{ settings('news.explore_button') ?: __('news::news.page.all') }}</a>
    </div>
    @foreach ($cards as $card)
        @include('theme::partials.news.overlay', ['card' => $card])
    @endforeach
</div>
