{{-- DIU's centred outline button under the news ("Stay Connected! Join the alumni portal"). Contract: $section (content: strong, text, url) --}}
<div class="max-sm:px-4 mt-10 mb-20 flex flex-col md:flex-row justify-center items-center">
    <x-theme::button :href="$section->content['url'] ?: '#'" target="_blank" variant="outlinePrimary" :right-icon="true">
        <span class="flex flex-col md:flex-row md:gap-1"><span class="font-bold">{{ $section->content['strong'] }}</span><span>{{ $section->content['text'] }}</span></span>
    </x-theme::button>
</div>
