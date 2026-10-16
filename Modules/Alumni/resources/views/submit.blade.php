{{-- /alumni/submit (FR-ALUM-07). Contract: none — the Livewire form "alumni.submit" --}}
<x-theme::layout :title="__('alumni::alumni.submit.title')">
    <div class="container" style="padding-block:var(--space-10, 40px);max-width:760px">
        <h1>{{ __('alumni::alumni.submit.title') }}</h1>
        <p>{{ __('alumni::alumni.submit.intro') }}</p>
        <livewire:alumni.submit />
    </div>
</x-theme::layout>
