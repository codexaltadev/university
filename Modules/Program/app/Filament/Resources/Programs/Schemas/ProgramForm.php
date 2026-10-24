<?php

namespace Modules\Program\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Department\Models\Department;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramFacilitator;
use Modules\Program\Models\ProgramListItem;
use Modules\Program\Support\ProgramChecklist;
use Platform\Filament\Components\LinkListField;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\Hub\EntityFormPart;
use Platform\Localization\Languages;
use Platform\Scope\UserScopeFilter;
use Platform\Taxonomy\Vocabularies;

/**
 * The Program editor (FR-PROG-01..10, UI/UX Part A §5.2 v1.2): everything about a
 * program in one place, part by part — Basic info, Details, Eligibility, Majors,
 * Options, Career & Industry, People, Resources, SEO. A new program is entered as a
 * wizard (steps, skippable); an existing one shows the same parts as tabs on the left
 * with what is filled in (ProgramChecklist), saved at once. The sub-items are lists
 * inside their part (BRL-PROG-04), each with its intro text on top.
 *
 * A scoped user edits everything but the Department, name, short name, address and
 * activity (BRL-PROG-02: only an Admin moves a Program); a viewer sees it read only.
 */
class ProgramForm
{
    /** Page sections with an optional intro text (section_intros). */
    public const INTRO_SECTIONS = ['majors', 'career_path', 'industry', 'facilitators', 'materials', 'memberships', 'variants', 'eligibility', 'accreditation', 'facilities', 'testimonials'];

    public const PARTS = ['basic', 'details', 'eligibility', 'majors', 'variants', 'careers', 'people', 'resources', 'seo'];

    protected const ICONS = [
        'basic' => 'heroicon-o-identification',
        'details' => 'heroicon-o-document-text',
        'eligibility' => 'heroicon-o-check-badge',
        'majors' => 'heroicon-o-book-open',
        'variants' => 'heroicon-o-squares-2x2',
        'careers' => 'heroicon-o-briefcase',
        'people' => 'heroicon-o-user-group',
        'resources' => 'heroicon-o-folder-open',
        'seo' => 'heroicon-o-magnifying-glass',
    ];

    /** Edit and view: the parts as tabs on the left, with their state. */
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Tabs::make('program')->vertical()->persistTabInQueryString('part')->columnSpanFull()
                ->tabs(collect(self::PARTS)->map(fn (string $part): Tab => Tab::make(__("program::program.parts.{$part}"))
                    ->icon(self::ICONS[$part])
                    ->badge(fn (?Program $record): ?string => $record ? ProgramChecklist::badge($record, $part) : null)
                    ->badgeColor(fn (?Program $record): string => $record ? ProgramChecklist::color($record, $part) : 'gray')
                    ->schema(self::part($part)))
                    ->push(...EntityFormPart::tabsFor(Program::class))   // other modules' read-only parts, e.g. Tuition fees
                    ->all()),
        ]);
    }

    /** @return list<Step> a new program, step by step (CreateProgram) */
    public static function steps(): array
    {
        return collect(self::PARTS)->map(fn (string $part): Step => Step::make(__("program::program.parts.{$part}"))
            ->icon(self::ICONS[$part])
            ->description(__("program::program.part_help.{$part}"))
            ->schema(self::part($part)))->all();
    }

    /** @return array<Component> */
    protected static function part(string $part): array
    {
        return match ($part) {
            'basic' => self::basic(),
            'details' => self::details(),
            'eligibility' => self::eligibility(),
            'majors' => [self::intro('majors'), self::majors()],
            'variants' => [self::intro('variants'), self::variants()],
            'careers' => [self::intro('career_path'), self::listItems('career_paths', ProgramListItem::CAREER_PATH), self::intro('industry'), self::listItems('industries', ProgramListItem::INDUSTRY)],
            'people' => [self::intro('facilitators'), self::facilitators(), PeopleManager::make()->collapsible(false)],   // Coordinator, Officer, Contact, Industry Expert
            'resources' => [self::intro('materials'), self::materials(), self::intro('memberships'), self::memberships(),
                Section::make(__('program::program.fields.quick_links'))->description(__('program::program.help.quick_links'))->compact()
                    ->schema([LinkListField::make('quick_links')->hiddenLabel()])],
            'seo' => self::seo(),
        };
    }

    /** @return array<Component> */
    protected static function basic(): array
    {
        $profileOnly = fn (string $operation): bool => $operation === 'edit' && static::profileOnly();

        return [
            TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('program::program.fields.name'))
                    ->required($isDefault)->maxLength(255)->disabled($profileOnly),
                Grid::make(2)->schema([
                    TextInput::make("subtitle.{$locale}")->label(__('program::program.fields.subtitle'))->maxLength(255),
                    TextInput::make("degree_name.{$locale}")->label(__('program::program.fields.degree_name'))->maxLength(255),
                ]),
            ]),
            Grid::make(2)->schema([
                Select::make('department_id')->label(__('program::program.fields.department'))
                    ->options(fn (?Program $record): array => static::departmentOptions($record))
                    ->default(fn (): ?int => request()->integer('department') ?: null)      // from the Department Hub (FR-GEN-86)
                    ->required()->searchable()->dehydrated()
                    ->disabled(fn (string $operation, $livewire): bool => $profileOnly($operation) || filled($livewire->hubDepartment ?? null))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('program::program.help.department_move') : null),
                Select::make('level_term_id')->label(__('program::program.fields.level'))
                    ->options(fn (): array => app(Vocabularies::class)->options('program.level'))
                    ->required(),
                TextInput::make('short_name')->label(__('program::program.fields.short_name'))
                    ->required()->maxLength(50)->disabled($profileOnly),
                WebAddressField::forMicrosite(Program::class, parentField: 'department_id')->disabled($profileOnly),
                TermSelect::make('tags')->vocabulary('program.tag')->columnSpanFull(),
            ]),
            Section::make(__('program::program.sections.media'))->compact()->columns(2)->schema([
                MediaPicker::make('image')->label(__('program::program.fields.image'))->image(),
                TextInput::make('video_url')->label(__('program::program.fields.video_url'))->url()->maxLength(2048),
                MediaPicker::make('brochure')->label(__('program::program.fields.brochure')),   // FR-PROG-17
            ]),
            Section::make(__('platform::relations.publishing'))->compact()->columns(3)->schema([
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->disabled($profileOnly),
                Toggle::make('show_in_listing')->label(__('program::program.fields.show_in_listing'))
                    ->helperText(__('program::program.help.show_in_listing'))->default(true),
                DateTimePicker::make('published_at')->label(__('platform::relations.published_at'))
                    ->helperText(__('platform::relations.published_at_help'))->default(fn () => now())->seconds(false),
            ]),
        ];
    }

    /** @return array<Component> */
    protected static function details(): array
    {
        return [
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("summary.{$locale}")->label(__('program::program.fields.summary'))->fileAttachments(false),
                RichEditor::make("objective.{$locale}")->label(__('program::program.fields.objective'))->fileAttachments(false),
                RichEditor::make("credit_duration.{$locale}")->label(__('program::program.fields.credit_duration'))->fileAttachments(false),
                TextInput::make("duration_label.{$locale}")->label(__('program::program.fields.duration_label'))
                    ->placeholder('4 Years (12 Semesters)')->maxLength(100),
            ]),
            Section::make(__('program::program.sections.academic'))->compact()->columns(3)->schema([
                TextInput::make('total_credits')->label(__('program::program.fields.total_credits'))->numeric()->minValue(0)->maxValue(99999),
                TextInput::make('duration_years')->label(__('program::program.fields.duration_years'))->numeric()->minValue(0)->maxValue(99),
                TextInput::make('total_terms')->label(__('program::program.fields.total_terms'))->integer()->minValue(0)->maxValue(255),
                TextInput::make('medium')->label(__('program::program.fields.medium'))->maxLength(30)->placeholder('English'),
                Select::make('shift')->label(__('program::program.fields.shift'))
                    ->options(collect(Program::SHIFTS)->mapWithKeys(fn (string $s): array => [$s => __("program::program.shifts.{$s}")])->all()),
                Select::make('class_mode')->label(__('program::program.fields.class_mode'))
                    ->options(collect(Program::CLASS_MODES)->mapWithKeys(fn (string $m): array => [$m => __("program::program.class_modes.{$m}")])->all()),
            ]),
            Section::make(__('program::program.sections.admission'))->compact()->columns(2)->schema([
                DatePicker::make('next_intake_on')->label(__('program::program.fields.next_intake_on')),
                TextInput::make('application_url')->label(__('program::program.fields.application_url'))->url()->maxLength(2048),
            ]),
            Section::make(__('program::program.sections.other_intros'))->compact()->collapsed()   // parts other modules add to the page
                ->schema([self::intro('accreditation'), self::intro('facilities'), self::intro('testimonials')]),
        ];
    }

    /** @return array<Component> FR-PROG-10 */
    protected static function eligibility(): array
    {
        return [
            self::intro('eligibility'),
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                RichEditor::make("eligibility_general.{$locale}")->label(__('program::program.fields.eligibility_general'))->fileAttachments(false),
                RichEditor::make("eligibility_english_medium.{$locale}")->label(__('program::program.fields.eligibility_english_medium'))->fileAttachments(false),
            ]),
            KeyValue::make('eligibility_rules')->label(__('program::program.fields.eligibility_rules'))
                ->keyLabel(__('program::program.fields.rule'))->valueLabel(__('program::program.fields.rule_value'))
                ->helperText(__('program::program.help.eligibility_rules')),
        ];
    }

    /** @return array<Component> */
    protected static function seo(): array
    {
        return [
            TranslatableTabs::make()->schema(fn (string $locale): array => [
                Textarea::make("ai_short_summary.{$locale}")->label(__('program::program.fields.ai_short_summary'))->rows(2),
                Textarea::make("ai_summary.{$locale}")->label(__('program::program.fields.ai_summary'))->rows(4),
            ]),
            SeoPanel::make(),
        ];
    }

    /** The intro text shown above a part of the program page (section_intros). */
    protected static function intro(string $section): Component
    {
        return Section::make(__("program::program.items.{$section}"))->compact()
            ->description(__('program::program.help.intro'))
            ->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                Textarea::make("section_intros.{$locale}.{$section}")->hiddenLabel()->placeholder(__('program::program.fields.intro')." ({$language})")
                    ->rows(2)->maxLength(1000),
            ]));
    }

    /** FR-PROG-04 */
    protected static function majors(): Repeater
    {
        return self::list('majors', 'majors', 'majors')
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('program::program.fields.title')." ({$language})")->required($isDefault)->maxLength(255),
                    Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description')." ({$language})")->rows(2)->maxLength(2000),
                ]),
                Select::make('category_term_id')->label(__('program::program.fields.major_category'))
                    ->options(fn (): array => app(Vocabularies::class)->options('program.major-category'))->searchable(),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]);
    }

    /** FR-PROG-05: career paths and industries share program_list_items. */
    protected static function listItems(string $name, string $type): Repeater
    {
        return self::list($name, 'listItems', $type, fn (Builder $query) => $query->where('list_type', $type))
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => [...$data, 'list_type' => $type])
            ->collapsed(false)->grid(2)
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('program::program.fields.title')." ({$language})")->required($isDefault)->maxLength(255),
                ]),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            ]);
    }

    /** FR-PROG-06 */
    protected static function facilitators(): Repeater
    {
        return self::list('facilitators', 'facilitators', 'facilitators', label: 'name')
            ->columns(2)
            ->schema([
                TextInput::make('name')->label(__('program::program.fields.person_name'))->required()->maxLength(191),
                Select::make('type')->label(__('program::program.fields.facilitator_type'))
                    ->options(collect(ProgramFacilitator::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("program::program.facilitator_types.{$t}")])->all()),
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("designation.{$locale}")->label(__('program::program.fields.designation')." ({$language})")->maxLength(255),
                    TextInput::make("organization.{$locale}")->label(__('program::program.fields.organization')." ({$language})")->maxLength(255),
                ]),
                TextInput::make('email')->label(__('program::program.fields.email'))->email()->maxLength(191),
                TextInput::make('phone')->label(__('program::program.fields.phone'))->tel()->maxLength(50),
                TextInput::make('profile_url')->label(__('program::program.fields.profile_url'))->url()->maxLength(2048),
                TextInput::make('linkedin_url')->label(__('program::program.fields.linkedin_url'))->url()->maxLength(2048),
                MediaPicker::make('photo')->label(__('program::program.fields.photo'))->image(),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]);
    }

    /** FR-PROG-07: a link or a file from the Media Library. */
    protected static function materials(): Repeater
    {
        return self::list('materials', 'materials', 'materials')
            ->columns(2)
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('program::program.fields.title')." ({$language})")->required($isDefault)->maxLength(255),
                ]),
                TextInput::make('url')->label(__('program::program.fields.link'))->url()->maxLength(2048),
                MediaPicker::make('file')->label(__('program::program.fields.file'))->helperText(__('program::program.help.material_file')),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]);
    }

    /** FR-PROG-08 */
    protected static function memberships(): Repeater
    {
        return self::list('memberships', 'memberships', 'memberships')
            ->columns(2)
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('program::program.fields.title')." ({$language})")->required($isDefault)->maxLength(255),
                    Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description')." ({$language})")->rows(2)->maxLength(2000),
                ]),
                MediaPicker::make('logo')->label(__('program::program.fields.logo'))->image(),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]);
    }

    /** FR-PROG-09: Dual Degree, Evening … */
    protected static function variants(): Repeater
    {
        return self::list('variants', 'variants', 'variants')
            ->columns(2)
            ->schema([
                ...TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                    TextInput::make("title.{$locale}")->label(__('program::program.fields.title')." ({$language})")->required($isDefault)->maxLength(255),
                    Textarea::make("short_description.{$locale}")->label(__('program::program.fields.short_description')." ({$language})")->required($isDefault)->rows(2)->maxLength(2000),
                    RichEditor::make("details.{$locale}")->label(__('program::program.fields.details')." ({$language})")->fileAttachments(false)->columnSpanFull(),
                    TextInput::make("button_text.{$locale}")->label(__('program::program.fields.button_text')." ({$language})")->maxLength(100),
                ]),
                Select::make('related_program_id')->label(__('program::program.fields.related_program'))->searchable()
                    ->options(fn ($livewire): array => static::programOptions($livewire->getRecord()?->getKey())),
                TextInput::make('button_url')->label(__('program::program.fields.button_url'))->url()->maxLength(2048)
                    ->helperText(__('program::program.help.button_url')),
                MediaPicker::make('image')->label(__('program::program.fields.image'))->image(),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]);
    }

    /** A sub-item list of the program: ordered, collapsed to its title, "+ Add …" (BRL-PROG-04). */
    protected static function list(string $name, string $relationship, string $item, ?\Closure $modifyQuery = null, string $label = 'title'): Repeater
    {
        $default = app(Languages::class)->default();

        return Repeater::make($name)->hiddenLabel()
            ->relationship($relationship, $modifyQuery)
            ->orderColumn('sort_order')
            ->defaultItems(0)
            ->collapsible()->collapsed()
            ->cloneable(false)
            ->itemLabel(fn (array $state): ?string => is_array($state[$label] ?? null) ? ($state[$label][$default] ?? null) : ($state[$label] ?? null))
            ->addActionLabel(__('program::program.add', ['item' => __("program::program.items.{$item}")]));
    }

    /** Scoped users edit the profile only; moving is an Admin's (BRL-PROG-02). */
    public static function profileOnly(): bool
    {
        $user = Auth::user();

        return $user !== null && ! $user->isSuperAdmin() && $user->scopes()->exists();
    }

    /**
     * Any other program may be linked from an option, also outside the user's scope (only its name is used).
     *
     * @return array<int, string>
     */
    protected static function programOptions(?int $except): array
    {
        return UserScopeFilter::withoutScope(fn () => Program::query()->when($except, fn (Builder $q) => $q->whereKeyNot($except))->ordered()->get())
            ->mapWithKeys(fn (Program $p): array => [$p->id => "{$p->short_name} — {$p->name}"])->all();
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
