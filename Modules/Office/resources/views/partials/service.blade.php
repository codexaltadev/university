{{-- One service (FR-OFF-06). Contract: $service --}}
<details id="service-{{ $service->id }}" class="accordion__item" style="border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);padding:var(--space-3, 12px) var(--space-4, 16px)">
    <summary class="accordion__title" style="cursor:pointer;font-weight:600">{{ $service->title }}</summary>
    <div class="prose" style="margin-top:var(--space-3, 12px)">
        @if (filled($service->description)){!! \Illuminate\Support\Str::sanitizeHtml((string) $service->description) !!}@endif
        @if (filled($service->required_documents))
            <h4>{{ __('office::office.fields.required_documents') }}</h4>
            {!! \Illuminate\Support\Str::sanitizeHtml((string) $service->required_documents) !!}
        @endif
        <dl style="display:grid;grid-template-columns:max-content 1fr;gap:.25rem var(--space-4, 16px)">
            @if ($service->fee_amount !== null || filled($service->fee_note))
                <dt>{{ __('office::office.fields.fee') }}</dt><dd>{{ $service->fee_amount !== null ? number_format((float) $service->fee_amount, 2) : '' }} {{ $service->fee_note }}</dd>
            @endif
            @if (filled($service->processing_time))
                <dt>{{ __('office::office.fields.processing_time') }}</dt><dd>{{ $service->processing_time }}</dd>
            @endif
        </dl>
        @foreach ($service->people(\Modules\Office\Models\OfficeService::ROLE) as $person)
            <p>{{ __('office::office.handled_by') }}: <strong>{{ $person->name() }}</strong>@if ($person->phone()) · {{ $person->phone() }}@endif @if ($person->email()) · <a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a>@endif</p>
        @endforeach
        <p style="display:flex;gap:var(--space-3, 12px)">
            @if ($service->apply_url)<a href="{{ $service->apply_url }}" class="button button-primary" target="_blank" rel="noopener">{{ __('office::office.apply') }}</a>@endif
            @if ($form = $service->firstMedia('form'))<a href="{{ $form->url() }}" class="button" target="_blank" rel="noopener">{{ __('office::office.fields.form') }}</a>@endif
        </p>
    </div>
</details>
