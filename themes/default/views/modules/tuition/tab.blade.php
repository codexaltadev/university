{{--
    DIU TuitionFees on the program page: a card per fee category — level and category badges, program,
    department, duration and credits; Admission Fees, Per Semester, Tuition Fees (max), Total Program Cost
    (max); the credit range. Contract: $entity (Program), $tuitions (Collection<Tuition>)
--}}
<div class="grid gap-6">
    @foreach ($tuitions as $tuition)
        <div class="border border-gray-200 bg-white rounded-xl hover:border-gray-400 hover:shadow-lg transition-all duration-300">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            @if ($level = $entity->level?->name)
                                <span @class(['px-3 py-1 text-xs font-semibold rounded-full', str_contains(strtolower($level), 'under') ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'])>{{ $level }}</span>
                            @endif
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">{{ $tuition->category->name }}</span>   {{-- local / international: DIU tells them apart by currency only --}}
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $entity->name }}</h3>
                        @if ($entity->department)
                            <p class="text-gray-600 flex items-center gap-2">
                                <x-theme::icon name="graduation-cap" class="w-4 h-4" />
                                {{ $entity->department->name }}
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        @if (filled($tuition->duration_label))
                            <div class="flex items-center gap-2 text-gray-600"><x-theme::icon name="clock" class="w-4 h-4" /><span>{{ $tuition->duration_label }}</span></div>
                        @endif
                        @if ((float) $tuition->credit_min > 0)
                            <div class="flex items-center gap-2 text-gray-600"><x-theme::icon name="book-open" class="w-4 h-4" /><span>{{ __('theme::theme.program.credits_short', ['count' => $tuition->plain($tuition->credit_min)]) }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach (array_filter(['admission_fees' => [$tuition->admissionFee(), null], 'per_semester' => [$tuition->termFee(), null], 'tuition_fees' => [$tuition->tuition_min, $tuition->tuition_max]], fn ($pair) => (float) $pair[0] > 0) as $label => $pair)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">{{ __("theme::theme.program.{$label}") }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ $tuition->category->money($pair[0]) }}</p>
                            @if ((float) $pair[1] > 0)<p class="text-xs text-gray-500 mt-1">{{ __('theme::theme.program.max', ['amount' => $tuition->category->money($pair[1])]) }}</p>@endif
                        </div>
                    @endforeach
                    @if ((float) $tuition->total_min > 0)
                        <div class="bg-gray-900 text-white p-4 rounded-lg">
                            <p class="text-sm text-gray-300 mb-1">{{ __('theme::theme.program.total_cost') }}</p>
                            <p class="text-xl font-bold">{{ $tuition->category->money($tuition->total_min) }}</p>
                            @if ((float) $tuition->total_max > 0)<p class="text-xs text-gray-400 mt-1">{{ __('theme::theme.program.max', ['amount' => $tuition->category->money($tuition->total_max)]) }}</p>@endif
                        </div>
                    @endif
                </div>

                @if ((float) $tuition->credit_max > 0 && (float) $tuition->credit_max !== (float) $tuition->credit_min)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600"><span class="font-semibold">{{ __('theme::theme.program.credit_range') }}</span> {{ __('theme::theme.program.credit_range_value', ['min' => $tuition->plain($tuition->credit_min), 'max' => $tuition->plain($tuition->credit_max)]) }}</p>
                    </div>
                @endif
                @if ($tuition->prospectus_url)
                    <p class="mt-4 text-sm"><a href="{{ $tuition->prospectus_url }}" target="_blank" rel="noopener" class="text-secondary hover:underline">{{ __('tuition::tuition.prospectus') }}</a></p>
                @endif
            </div>
        </div>
    @endforeach
</div>
