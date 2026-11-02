{{-- Down for maintenance: the site name and one line --}}
<x-theme::layout :title="__('theme::theme.maintenance')">
    <div class="container error-page">
        <x-platform::logo variant="light" class="mx-auto h-14 w-auto" />
        <h1 class="mt-8 text-4xl">{{ __('theme::theme.maintenance') }}</h1>
        <p class="lead mx-auto mt-3 max-w-xl">{{ __('theme::theme.maintenance_text') }}</p>
    </div>
</x-theme::layout>
