<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-clock" icon-color="warning" :heading="__('notification::reminder.widget.heading')">
        <ul style="margin:0;padding-inline-start:1.25rem;display:grid;gap:.35rem">
            @foreach ($this::lines() as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
