{{-- The tuition table of /tuition-fees. Contract: $tuitions (Collection<Tuition>), $withFaculty (bool, optional) --}}
@php
    $range = fn ($t, $min, $max) => $max !== null && (float) $max > (float) $min ? $t->category->money($min).' – '.$t->category->money($max) : $t->category->money($min);
@endphp
<div style="overflow-x:auto">
    <table class="tuition-table" style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="text-align:left;border-bottom:2px solid var(--color-border)">
                @if ($withFaculty ?? false) <th scope="col">{{ __('tuition::tuition.fields.faculty') }}</th> @endif
                <th scope="col">{{ __('tuition::tuition.fields.program') }}</th>
                <th scope="col">{{ __('tuition::tuition.fields.credits') }}</th>
                <th scope="col">{{ __('tuition::tuition.fields.duration') }}</th>
                <th scope="col">{{ __('tuition::tuition.fields.admission_fee') }}</th>
                <th scope="col">{{ __('tuition::tuition.fields.tuition_total') }}</th>
                <th scope="col">{{ __('tuition::tuition.fields.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tuitions as $tuition)
                <tr class="tuition-row" style="border-bottom:1px solid var(--color-border)">
                    @if ($withFaculty ?? false) <td>{{ $tuition->program->department?->faculty?->short_name }}</td> @endif
                    <td>
                        <a href="{{ $tuition->program->url() }}">{{ $tuition->program->name }}</a>
                        @if (filled($tuition->label)) <small>({{ $tuition->label }})</small> @endif
                    </td>
                    <td>{{ $tuition->creditsText() }}</td>
                    <td>{{ $tuition->duration_label }}</td>
                    <td>{{ $tuition->category->money($tuition->admissionFee()) }}</td>
                    <td>{{ $range($tuition, $tuition->tuition_min, $tuition->tuition_max) }}</td>
                    <td><strong>{{ $range($tuition, $tuition->total_min, $tuition->total_max) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
