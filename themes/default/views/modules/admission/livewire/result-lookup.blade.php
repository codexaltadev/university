{{--
    Admission result lookup as DIU's /admission-test-result: a white card with "Application Serial Number" and Search Result;
    found — the result card (blue head with the result as a coloured badge, Student Information, Official Message, help);
    not found / not published / too many tries — a red or blue note.
    The card is the seat plan's (x-theme::lookup-form): every lookup looks the same.
    Contract (Admission module): wire:model serial, website (honeypot); wire:submit lookup; wire:click clear; $answer (status, name, serial, program, result, tone, label, message — sanitised HTML, batch)
--}}
<div>
    <x-theme::lookup-form id="admission-serial" :label="__('admission::admission.result_view.serial')" :placeholder="__('admission::admission.result_page.placeholder')"
        :button="__('admission::admission.result_page.search')" :busy="__('admission::admission.seats.searching')" :error="$errors->first('serial')"
        :clear="(bool) $answer" :clear-label="__('admission::admission.seats.reset')" />

    <div aria-live="polite">
        @if ($answer && $answer['status'] === 'found')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-primary to-secondary text-white p-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-bold">{{ __('admission::admission.result_view.heading') }}</h2>
                        @if ($answer['batch'])<p class="text-sm text-white/80">{{ $answer['batch'] }}</p>@endif
                    </div>
                    <span @class(['px-4 py-2 rounded-full border font-semibold',
                                  'text-green-700 bg-green-50 border-green-200' => $answer['tone'] === 'success',
                                  'text-yellow-600 bg-yellow-50 border-yellow-200' => $answer['tone'] === 'warning',
                                  'text-red-600 bg-red-50 border-red-200' => $answer['tone'] === 'danger',
                                  'text-primary bg-blue-50 border-blue-200' => $answer['tone'] === 'info'])>{{ $answer['label'] }}</span>
                </div>
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('admission::admission.result_view.student') }}</h3>
                    <dl class="grid md:grid-cols-2 gap-6">
                        <div><dt class="text-sm font-medium text-gray-500 mb-1">{{ __('admission::admission.result_view.name') }}</dt><dd class="text-lg font-semibold text-gray-900">{{ $answer['name'] }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500 mb-1">{{ __('admission::admission.result_view.serial_short') }}</dt><dd class="text-lg font-mono font-semibold text-primary">{{ $answer['serial'] }}</dd></div>
                        @if ($answer['program'])
                            <div><dt class="text-sm font-medium text-gray-500 mb-1">{{ __('admission::admission.result_view.program') }}</dt><dd class="text-lg font-semibold text-gray-900">{{ $answer['program'] }}</dd></div>
                        @endif
                    </dl>
                </div>
                @if (filled($answer['message'] ?? null))
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('admission::admission.result_view.message') }}</h3>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 space-y-3 [&_a]:text-secondary [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5">{!! $answer['message'] !!}</div>
                    </div>
                @endif
            </div>
        @elseif ($answer)
            <div role="alert" @class(['rounded-2xl p-5 mb-8 border flex items-start gap-3',
                         'bg-red-50 border-red-200 text-red-700' => $answer['status'] === 'not_found',
                         'bg-blue-50 border-blue-200 text-primary' => $answer['status'] !== 'not_found'])>
                <x-theme::icon name="alert-circle" class="size-5 shrink-0 mt-0.5" />
                <div>
                    @if ($answer['status'] === 'not_found')<h3 class="text-sm font-semibold text-red-800">{{ __('admission::admission.result_view.not_found') }}</h3>@endif
                    <div class="text-sm mt-1">{!! $answer['message'] !!}</div>
                </div>
            </div>
        @endif
    </div>
</div>
