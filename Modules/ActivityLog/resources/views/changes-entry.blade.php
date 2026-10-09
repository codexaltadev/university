{{-- Full old / new comparison for one activity (FR-LOG-04) --}}
@php
    $changes = $getState() ?? [];
    $new = $changes['attributes'] ?? [];
    $old = $changes['old'] ?? [];
    $fields = array_unique([...array_keys($new), ...array_keys($old)]);
    $show = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '—'));
@endphp

@if ($fields === [])
    <p class="text-sm opacity-60">{{ __('activitylog::activity.no_changes') }}</p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-start">
                <th class="py-1 pe-4 text-start">{{ __('activitylog::activity.fields.field') }}</th>
                <th class="py-1 pe-4 text-start">{{ __('activitylog::activity.fields.old') }}</th>
                <th class="py-1 text-start">{{ __('activitylog::activity.fields.new') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fields as $field)
                <tr class="border-t border-gray-200 dark:border-white/10 align-top">
                    <td class="py-1 pe-4 font-medium">{{ $field }}</td>
                    <td class="py-1 pe-4 whitespace-pre-wrap opacity-70">{{ array_key_exists($field, $old) ? $show($old[$field]) : '' }}</td>
                    <td class="py-1 whitespace-pre-wrap">{{ array_key_exists($field, $new) ? $show($new[$field]) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
