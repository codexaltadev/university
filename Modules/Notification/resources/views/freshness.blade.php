<x-filament-panels::page>
    @php($types = $this->types())
    @if ($types === [])
        <p>{{ __('notification::reminder.report.no_rules') }}</p>
    @else
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            @foreach ($types as $key => $label)
                <x-filament::button tag="a" size="sm" :color="$key === $this->type ? 'primary' : 'gray'"
                    :href="request()->url().'?type='.urlencode($key)">{{ $label }}</x-filament::button>
            @endforeach
        </div>

        @php($matrix = $this->matrix())
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:.5rem;border-bottom:1px solid rgba(0,0,0,.1)"></th>
                        @foreach ($matrix['columns'] as $column)
                            <th style="text-align:left;padding:.5rem;border-bottom:1px solid rgba(0,0,0,.1)">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matrix['rows'] as $row)
                        <tr>
                            <td style="padding:.5rem;border-bottom:1px solid rgba(0,0,0,.06);font-weight:600">{{ $row['name'] }}</td>
                            @foreach ($row['cells'] as $state)
                                <td style="padding:.5rem;border-bottom:1px solid rgba(0,0,0,.06)"
                                    title="{{ $state ? __('notification::reminder.report.reminders', ['count' => $state->reminder_count]) : '' }}">
                                    @if (! $state)
                                        —
                                    @elseif ($state->status === 'ok')
                                        ✅
                                    @elseif ($state->status === 'empty')
                                        ❌ {{ __('notification::reminder.status.empty') }}
                                    @else
                                        ⚠ {{ trans_choice('notification::reminder.days', $state->days() ?? 0, ['count' => $state->days() ?? 0]) }}
                                        @if ($state->stale_records)
                                            · {{ trans_choice('notification::reminder.report.old_records', count($state->stale_records), ['count' => count($state->stale_records)]) }}
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($matrix['columns']) + 1 }}" style="padding:.75rem">{{ __('notification::reminder.report.not_checked') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
