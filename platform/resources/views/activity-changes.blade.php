{{-- Old → new for each changed field (FR-LOG-04). $getState() is the activity's attribute_changes. --}}
@php
    $changes = $getState() ?? [];
    $new = $changes['attributes'] ?? [];
    $old = $changes['old'] ?? [];
    $fields = array_unique([...array_keys($new), ...array_keys($old)]);
    $show = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? 'true' : 'false') : (string) ($value ?? '—'));
@endphp

<div class="text-xs space-y-0.5 py-1">
    @forelse ($fields as $field)
        <div>
            <span class="font-medium">{{ $field }}</span>:
            @if (array_key_exists($field, $old))
                <span class="line-through opacity-60">{{ \Illuminate\Support\Str::limit($show($old[$field]), 80) }}</span>
            @endif
            @if (array_key_exists($field, $new))
                → <span>{{ \Illuminate\Support\Str::limit($show($new[$field]), 80) }}</span>
            @endif
        </div>
    @empty
        <span class="opacity-60">—</span>
    @endforelse
</div>
