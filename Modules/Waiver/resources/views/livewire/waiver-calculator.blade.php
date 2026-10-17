{{--
    Waiver Calculator (FR-FEE-06/08). Contract: wire:model feeCategoryId, levelId, tuitionId, waiverCategoryId, boardId,
    gender, ssc, sscGolden, hsc, hscGolden, cgpa; wire:click calculate; $feeCategories, $levels, $tuitions,
    $waiverCategories, $boards, $genders, $fields (school, cgpa), $diploma, $answer, $tuition, $rule, $disclaimer
--}}
@php
    $money = fn ($v) => $tuition?->category?->money($v);
    $range = fn (string $key) => isset($answer['max'][$key]) ? $money($answer['min'][$key]).' – '.$money($answer['max'][$key]) : $money($answer['min'][$key]);
@endphp
<div class="waiver-calculator" style="display:grid;gap:var(--space-8, 32px);grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));align-items:start">
    <form wire:submit="calculate" class="waiver-form no-print" style="display:grid;gap:var(--space-3, 12px)">
        <label style="display:grid;gap:.25rem">
            <span>{{ __('waiver::waiver.fields.category') }}</span>
            <select wire:model.live="feeCategoryId">
                @foreach ($feeCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:grid;gap:.25rem">
            <span>{{ __('waiver::waiver.fields.level') }}</span>
            <select wire:model.live="levelId">
                @foreach ($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:grid;gap:.25rem">
            <span>{{ __('waiver::waiver.fields.program') }}</span>
            <select wire:model.live="tuitionId">
                <option value="">{{ __('waiver::waiver.calculator.choose') }}</option>
                @foreach ($tuitions as $item)
                    <option value="{{ $item->id }}">{{ $item->program->name }}@if (filled($item->label)) ({{ $item->label }})@endif</option>
                @endforeach
            </select>
            @error('tuitionId') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
        </label>
        <label style="display:grid;gap:.25rem">
            <span>{{ __('waiver::waiver.calculator.waiver_category') }}</span>
            <select wire:model.live="waiverCategoryId">
                <option value="">{{ __('waiver::waiver.calculator.choose') }}</option>
                @foreach ($waiverCategories as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
            @error('waiverCategoryId') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
        </label>
        @if ($boards->isNotEmpty())
            <label style="display:grid;gap:.25rem">
                <span>{{ __('waiver::waiver.calculator.board') }}</span>
                <select wire:model.live="boardId">
                    <option value="">{{ __('waiver::waiver.calculator.choose') }}</option>
                    @foreach ($boards as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                @error('boardId') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
            </label>
        @endif
        <fieldset style="border:0;padding:0;margin:0;display:flex;gap:1rem;flex-wrap:wrap">
            <legend>{{ __('waiver::waiver.calculator.gender') }}</legend>
            @foreach ($genders as $item)
                <label><input type="radio" wire:model.live="gender" value="{{ $item }}"> {{ __("waiver::waiver.genders.{$item}") }}</label>
            @endforeach
            @error('gender') <small class="error" style="color:var(--color-danger, #b91c1c);width:100%">{{ $message }}</small> @enderror
        </fieldset>
        @if ($fields['school'])
            <div style="display:grid;gap:var(--space-3, 12px);grid-template-columns:1fr 1fr">
                <label style="display:grid;gap:.25rem">
                    <span>{{ __('waiver::waiver.calculator.ssc') }}</span>
                    <input type="number" step="0.01" min="0" max="5" inputmode="decimal" wire:model.live.blur="ssc">
                    @if ((float) $ssc === 5.0) <span><input type="checkbox" wire:model.live="sscGolden"> {{ __('waiver::waiver.calculator.golden') }}</span> @endif
                    @error('ssc') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
                </label>
                <label style="display:grid;gap:.25rem">
                    <span>{{ $diploma ? __('waiver::waiver.calculator.diploma') : __('waiver::waiver.calculator.hsc') }}</span>
                    <input type="number" step="0.01" min="0" max="{{ $diploma ? 4 : 5 }}" inputmode="decimal" wire:model.live.blur="hsc">
                    @if (! $diploma && (float) $hsc === 5.0) <span><input type="checkbox" wire:model.live="hscGolden"> {{ __('waiver::waiver.calculator.golden') }}</span> @endif
                    @error('hsc') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
                </label>
            </div>
        @endif
        @if ($fields['cgpa'])
            <label style="display:grid;gap:.25rem">
                <span>{{ __('waiver::waiver.calculator.cgpa') }}</span>
                <input type="number" step="0.01" min="0" max="4" inputmode="decimal" wire:model.live.blur="cgpa">
                @error('cgpa') <small class="error" style="color:var(--color-danger, #b91c1c)">{{ $message }}</small> @enderror
            </label>
        @endif
        <button type="submit" class="btn btn--primary" wire:loading.attr="disabled">{{ __('waiver::waiver.calculator.calculate') }}</button>
    </form>

    <div class="waiver-result" aria-live="polite">
        @if ($answer && $answer['status'] === 'ok')
            <div class="waiver-result__ok" style="border:3px solid var(--color-primary);border-radius:var(--radius-lg, 16px);padding:var(--space-6, 24px)">
                <p style="text-align:center;font-size:var(--text-lg, 20px)">
                    {!! __('waiver::waiver.calculator.eligible', ['percent' => '<strong style="font-size:2em">'.e(\Modules\Tuition\Models\Tuition::plain($answer['percent'])).'%</strong>', 'category' => e($answer['category'])]) !!}
                </p>
                <h3>{{ $tuition->program->name }}</h3>
                <dl style="display:grid;grid-template-columns:minmax(0, 1fr) auto;gap:.25rem 1rem">
                    @if (filled($tuition->duration_label)) <dt>{{ __('waiver::waiver.fields.duration') }}</dt><dd>{{ $tuition->duration_label }}</dd> @endif
                    @if ($tuition->creditsText()) <dt>{{ __('waiver::waiver.fields.credits') }}</dt><dd>{{ $tuition->creditsText() }}</dd> @endif
                    <dt>{{ __('waiver::waiver.fields.total') }}</dt><dd>{{ $range('total') }}</dd>
                    <dt>{{ __('waiver::waiver.fields.tuition_total') }}</dt><dd>{{ $range('tuition') }}</dd>
                    <dt>{{ __('waiver::waiver.calculator.other') }}</dt><dd>{{ $range('other') }}</dd>
                    <dt>{{ __('waiver::waiver.calculator.waiver_amount') }}</dt><dd>{{ $money($answer['min']['waiver']) }}@isset($answer['max']) – {{ $money($answer['max']['waiver']) }}@endisset</dd>
                    <dt><strong>{{ __('waiver::waiver.calculator.after_waiver') }}</strong></dt><dd><strong>{{ $range('final') }}</strong></dd>
                </dl>
                @if ($answer['min']['admission'])
                    <p>{{ __('waiver::waiver.calculator.payable_at_admission', ['amount' => $money($answer['min']['admission'])]) }}</p>
                @endif
                @isset($answer['max'])
                    <p><small>{{ __('waiver::waiver.calculator.range_note') }}</small></p>
                @endisset
                @if ($rule && filled($rule->message)) <p class="waiver-result__condition"><small>{{ $rule->message }}</small></p> @endif
                <button type="button" class="btn no-print" onclick="window.print()">{{ __('waiver::waiver.print') }}</button>
            </div>
        @elseif ($answer && $answer['status'] === 'no_tuition')
            <p class="waiver-result__none">{{ __('waiver::waiver.calculator.no_tuition') }}</p>
        @elseif ($answer)
            <p class="waiver-result__none">{{ __('waiver::waiver.calculator.no_waiver') }}</p>
        @endif
        @if (filled($disclaimer)) <p class="waiver-disclaimer" style="color:var(--color-text-muted)"><small>{{ $disclaimer }}</small></p> @endif
    </div>
</div>
<style>@media print { .no-print, header, footer, .site-header, .site-footer { display: none !important } }</style>
