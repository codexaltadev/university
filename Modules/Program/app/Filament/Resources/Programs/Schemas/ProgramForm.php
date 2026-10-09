<?php

namespace Modules\Program\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Modules\Department\Models\Department;
use Modules\Program\Models\Program;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Scope\UserScopeFilter;
use Platform\Taxonomy\Vocabularies;

/**
 * Program form (FR-PROG-01/03/10, UI/UX Part A §5.2): texts per language on the left
 * (name, subtitle, degree, summary, objective, eligibility, AI summaries); Program,
 * academic facts, publishing, pictures and SEO on the right; people below. A scoped
 * user edits the profile but not the Department, name, short name or address
 * (BRL-PROG-02: only an Admin moves a Program).
 */
class ProgramForm
{
    /** Page sections with an optional intro text (section_intros). */
    public const INTRO_SECTIONS = ['majors', 'career_path', 'industry', 'facilitators', 'materials', 'memberships', 'variants'];

    public static function configure(Schema $schema): Schema
    {
        $profileOnly = fn (string $operation): bool => $operation === 'edit' && static::profileOnly();

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('program::program.fields.name'))
                                ->required($isDefault)->maxLength(255)->disabled($profileOnly),
                            Grid::make(2)->schema([
                                TextInput::make("subtitle.{$locale}")->label(__('program::program.fields.subtitle'))->maxLength(255),
                                TextInput::make("degree_name.{$locale}")->label(__('program::program.fields.degree_name'))->maxLength(255),
                            ]),
                            TextInput::make("duration_label.{$locale}")->label(__('program::program.fields.duration_label'))
                                ->placeholder('4 Years (12 Semesters)')->maxLength(100),
                            RichEditor::make("summary.{$locale}")->label(__('program::program.fields.summary'))->fileAttachments(false),
                            RichEditor::make("objective.{$locale}")->label(__('program::program.fields.objective'))->fileAttachments(false),
                            RichEditor::make("eligibility_general.{$locale}")->label(__('program::program.fields.eligibility_general'))->fileAttachments(false),
                            RichEditor::make("eligibility_english_medium.{$locale}")->label(__('program::program.fields.eligibility_english_medium'))->fileAttachments(false),
                            Textarea::make("ai_short_summary.{$locale}")->label(__('program::program.fields.ai_short_summary'))->rows(2),
                            Textarea::make("ai_summary.{$locale}")->label(__('program::program.fields.ai_summary'))->rows(4),
                        ]),
                    ]),
                    Section::make(__('program::program.sections.intros'))->description(__('program::program.help.intros'))
                        ->collapsible()->collapsed()->compact()->schema([
                            TranslatableTabs::make()->schema(fn (string $locale): array => collect(self::INTRO_SECTIONS)
                                ->map(fn (string $key) => Textarea::make("section_intros.{$locale}.{$key}")->label(__("program::program.items.{$key}"))->rows(2)->maxLength(1000))
                                ->all()),
                        ]),
                    Section::make(__('program::program.sections.admission'))->compact()->columns(2)->schema([
                        DatePicker::make('next_intake_on')->label(__('program::program.fields.next_intake_on')),
                        TextInput::make('application_url')->label(__('program::program.fields.application_url'))->url()->maxLength(2048),
                        KeyValue::make('eligibility_rules')->label(__('program::program.fields.eligibility_rules'))
                            ->keyLabel(__('program::program.fields.rule'))->valueLabel(__('program::program.fields.rule_value'))
                            ->helperText(__('program::program.help.eligibility_rules'))->columnSpanFull(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('program::program.sections.program'))->compact()->schema([
                        Select::make('department_id')->label(__('program::program.fields.department'))
                            ->options(fn (?Program $record): array => static::departmentOptions($record))
                            ->required()->searchable()->disabled($profileOnly)
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('program::program.help.department_move') : null),
                        Select::make('level_term_id')->label(__('program::program.fields.level'))
                            ->options(fn (): array => app(Vocabularies::class)->options('program.level'))
                            ->required(),
                        TermSelect::make('tags')->vocabulary('program.tag'),
                        TextInput::make('short_name')->label(__('program::program.fields.short_name'))
                            ->required()->maxLength(50)->disabled($profileOnly),
                        WebAddressField::make(prefix: '/program/')->disabled($profileOnly),
                    ]),
                    Section::make(__('program::program.sections.academic'))->compact()->columns(2)->schema([
                        TextInput::make('total_credits')->label(__('program::program.fields.total_credits'))->numeric()->minValue(0)->maxValue(99999),
                        TextInput::make('duration_years')->label(__('program::program.fields.duration_years'))->numeric()->minValue(0)->maxValue(99),
                        TextInput::make('total_terms')->label(__('program::program.fields.total_terms'))->integer()->minValue(0)->maxValue(255),
                        TextInput::make('medium')->label(__('program::program.fields.medium'))->maxLength(30)->placeholder('English'),
                        Select::make('shift')->label(__('program::program.fields.shift'))
                            ->options(collect(Program::SHIFTS)->mapWithKeys(fn (string $s): array => [$s => __("program::program.shifts.{$s}")])->all()),
                        Select::make('class_mode')->label(__('program::program.fields.class_mode'))
                            ->options(collect(Program::CLASS_MODES)->mapWithKeys(fn (string $m): array => [$m => __("program::program.class_modes.{$m}")])->all()),
                    ]),
                    Section::make(__('platform::relations.publishing'))->compact()->schema([
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->disabled($profileOnly),
                        DateTimePicker::make('published_at')->label(__('platform::relations.published_at'))
                            ->helperText(__('platform::relations.published_at_help'))->default(fn () => now())->seconds(false),
                        Toggle::make('show_in_listing')->label(__('program::program.fields.show_in_listing'))
                            ->helperText(__('program::program.help.show_in_listing'))->default(true),
                    ]),
                    Section::make(__('program::program.sections.media'))->compact()->schema([
                        MediaPicker::make('image')->label(__('program::program.fields.image'))->image(),
                        MediaPicker::make('brochure')->label(__('program::program.fields.brochure')),   // FR-PROG-17
                        TextInput::make('video_url')->label(__('program::program.fields.video_url'))->url()->maxLength(2048),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
            PeopleManager::make()->columnSpanFull(),   // Coordinator, Officer, Contact, Industry Expert
        ]);
    }

    /** Scoped users edit the profile only; moving is an Admin's (BRL-PROG-02). */
    public static function profileOnly(): bool
    {
        $user = Auth::user();

        return $user !== null && ! $user->isSuperAdmin() && $user->scopes()->exists();
    }

    /**
     * Departments the user may put a Program in (the scope filter limits Faculty /
     * Department users to theirs), plus the current one so its name always shows.
     *
     * @return array<int, string>
     */
    protected static function departmentOptions(?Program $record): array
    {
        $label = fn (Department $d): string => trim(($d->faculty?->short_name ? "{$d->faculty->short_name} › " : '')."{$d->short_name} — {$d->name}");
        $options = Department::query()->with('faculty')->ordered()->get()->mapWithKeys(fn (Department $d): array => [$d->id => $label($d)])->all();

        if ($record?->department_id && ! isset($options[$record->department_id])) {
            $current = UserScopeFilter::withoutScope(fn () => Department::query()->withTrashed()->with('faculty')->find($record->department_id));
            $options = $current ? [$current->id => $label($current)] + $options : $options;
        }

        return $options;
    }
}
