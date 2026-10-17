<?php

namespace Modules\FacultyMember\Filament\Resources\FacultyMembers\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Assignments\AssignmentEngine;
use Platform\Filament\Components\SeoPanel;

/**
 * A teacher (DB-03 §8 v1.7): what the faculty site says, read only, on the left; this site's own
 * switches — which contacts the website shows — and the places they are assigned on the right
 * (FR-FM-11, BRL-FM-03). Changes to the rest are made on the faculty site and arrive with the sync.
 */
class FacultyMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('facultymember::facultymember.sections.from_source'))
                        ->description(__('facultymember::facultymember.help.read_only'))->schema([
                            Grid::make(2)->schema([
                                static::show('name', fn (FacultyMember $r) => $r->name),
                                static::show('designation', fn (FacultyMember $r) => $r->designationLabel()),
                                static::show('department', fn (FacultyMember $r) => $r->department?->name ?? __('facultymember::facultymember.no_department')),
                                static::show('other_departments', fn (FacultyMember $r) => $r->departments->pluck('name')->implode(', ')),
                                static::show('employee_id', fn (FacultyMember $r) => $r->employee_id),
                                static::show('employment_type', fn (FacultyMember $r) => __("facultymember::facultymember.employment.{$r->employment_type}")),
                                static::show('email', fn (FacultyMember $r) => $r->email),
                                static::show('phone', fn (FacultyMember $r) => trim($r->phone.($r->extension ? ' · '.__('facultymember::facultymember.ext').' '.$r->extension : ''))),
                                static::show('office_room', fn (FacultyMember $r) => $r->office_room),
                                static::show('external_ref', fn (FacultyMember $r) => $r->external_ref.' · '.__('facultymember::facultymember.fields.synced_at').' '.$r->synced_at?->format('d M Y H:i')),
                            ]),
                            static::show('research_interests', fn (FacultyMember $r) => strip_tags((string) $r->research_interests)),
                            Placeholder::make('profile')->label(__('facultymember::facultymember.fields.profile'))
                                ->content(fn (?FacultyMember $record): string => static::profileSummary($record)),
                        ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('facultymember::facultymember.fields.photo'))->compact()->schema([
                        Placeholder::make('photo')->hiddenLabel()->content(fn (?FacultyMember $record): HtmlString => new HtmlString($record?->photo_url
                            ? '<img src="'.e($record->photo_url).'" alt="" style="width:160px;height:160px;object-fit:cover;border-radius:.5rem">'
                            : e(__('facultymember::facultymember.no_photo')))),
                    ]),
                    Section::make(__('facultymember::facultymember.sections.website'))
                        ->description(__('facultymember::facultymember.help.website'))->compact()->schema([
                            Toggle::make('show_email')->label(__('facultymember::facultymember.fields.show_email')),
                            Toggle::make('show_phone')->label(__('facultymember::facultymember.fields.show_phone')),
                            Toggle::make('show_extension')->label(__('facultymember::facultymember.fields.show_extension')),
                        ]),
                    Section::make(__('facultymember::facultymember.sections.places'))->compact()->schema([
                        Placeholder::make('places')->hiddenLabel()->content(fn (?FacultyMember $record): string => static::places($record)),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    protected static function show(string $field, \Closure $value): Placeholder
    {
        return Placeholder::make("show_{$field}")->label(__("facultymember::facultymember.fields.{$field}"))
            ->content(fn (?FacultyMember $record): string => $record ? ((string) $value($record) ?: '—') : '—');
    }

    /** "Education 3 · Experience 2 · Publication 12" */
    protected static function profileSummary(?FacultyMember $record): string
    {
        $parts = collect(FacultyMember::PROFILE_SECTIONS)
            ->map(fn (string $s): ?string => ($n = count($record?->profileSection($s) ?? [])) ? __("facultymember::facultymember.sections.{$s}").' '.$n : null)
            ->filter();

        return $parts->isEmpty() ? '—' : $parts->implode(' · ');
    }

    /** "Department of CSE — Head; Syndicate — Member" (read only; places add people from their own pages). */
    protected static function places(?FacultyMember $record): string
    {
        $list = $record ? app(AssignmentEngine::class)->assignmentsOf($record)->load('assignable') : collect();
        if ($list->isEmpty()) {
            return __('facultymember::facultymember.no_places');
        }

        return $list->map(fn ($a): string => trim((string) ($a->assignable?->name ?? $a->assignable?->title ?? $a->assignable_type).' — '.($a->role_label ?: $a->role_key)))->implode('; ');
    }
}
