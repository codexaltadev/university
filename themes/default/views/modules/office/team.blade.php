{{-- DIU /registrar-office/staff-directory: every member, head first, in the office's frame. Contract: $entity (Office), $page (OfficePage) --}}
<x-theme::office-frame :office="$page" :current="$page->url('team')" :title="__('office::office.page.team_members').' — '.$entity->name"
    :crumbs="[['label' => __('office::office.page.team'), 'url' => $page->url('team')]]">
    <section aria-labelledby="office-team">
        <h2 id="office-team" class="lg:leading-16 text-[24px] md:text-3xl lg:text-5xl font-bold text-neutral-800 mb-2">{{ __('office::office.page.team_members') }}</h2>
        <p class="text-gray-500 mb-8">{{ __('office::office.page.team_members_text') }}</p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($page->team() as $person)
                @include('theme::partials.office.member', ['person' => $person])
            @endforeach
        </div>
    </section>
</x-theme::office-frame>
