<?php

namespace Modules\Department\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Scope\UserScopeFilter;

/**
 * Department form (FR-DEPT-01/03/04, UI/UX Part A §5.2): name, about, Head's message
 * per language; Faculty, short name, contact, external website, pictures; people
 * (Head …); SEO. A scoped user edits the profile but not the Faculty, name, short
 * name or web address (FR-DEPT-08: only an Admin moves a Department).
 */
class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $profileOnly = fn (string $operation): bool => $operation === 'edit' && static::profileOnly();

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("name.{$locale}")->label(__('department::department.fields.name'))
                            ->required($isDefault)->maxLength(255)->disabled($profileOnly),
                        TextInput::make("about_title.{$locale}")->label(__('department::department.fields.about_title'))->maxLength(255),
                        RichEditor::make("about.{$locale}")->label(__('department::department.fields.about'))->fileAttachments(false),
                        RichEditor::make("head_message.{$locale}")->label(__('department::department.fields.head_message'))->fileAttachments(false),
                        TextInput::make("location.{$locale}")->label(__('department::department.fields.location'))->maxLength(255),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('department::department.sections.department'))->compact()->schema([
                        Select::make('faculty_id')->label(__('department::department.fields.faculty'))
                            ->options(fn (?Department $record): array => static::facultyOptions($record))
                            ->default(fn (): ?int => request()->integer('faculty') ?: null)      // from the Faculty Hub (FR-GEN-86)
                            ->required()->searchable()->live()->dehydrated()
                            ->disabled(fn (string $operation, $livewire): bool => $profileOnly($operation) || filled($livewire->hubFaculty ?? null))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('department::department.help.faculty_move') : null),
                        TextInput::make('short_name')->label(__('department::department.fields.short_name'))
                            ->required()->maxLength(30)->disabled($profileOnly)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('faculty_id', $get('faculty_id'))->whereNull('deleted_at')),   // BRL-DEPT-01
                        WebAddressField::make(prefix: '/department/')->disabled($profileOnly),
                        TextInput::make('external_url')->label(__('department::department.fields.external_url'))
                            ->url()->maxLength(2048)->helperText(__('department::department.help.external_url')),
                        TextInput::make('email')->label(__('department::department.fields.email'))->email()->maxLength(191),
                        TextInput::make('phone')->label(__('department::department.fields.phone'))->tel()->maxLength(50),
                        Toggle::make('show_in_listing')->label(__('department::department.fields.show_in_listing'))
                            ->helperText(__('department::department.help.show_in_listing'))->default(true),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->disabled($profileOnly),
                    ]),
                    Section::make(__('department::department.sections.pictures'))->compact()->schema([
                        MediaPicker::make('photo')->label(__('department::department.fields.photo'))->image(),
                        MediaPicker::make('cover')->label(__('department::department.fields.cover'))->image(),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
            PeopleManager::make()->columnSpanFull(),   // Head, Coordinator, Officer, Contact, Industry Expert (FR-DEPT-03)
        ]);
    }

    /** Scoped users edit the profile only (as BRL-FAC-02); moving is an Admin's (FR-DEPT-08). */
    public static function profileOnly(): bool
    {
        $user = Auth::user();

        return $user !== null && ! $user->isSuperAdmin() && $user->scopes()->exists();
    }

    /**
     * Faculties the user may put a Department in (the scope filter limits a Faculty user
     * to their own, BRL-DEPT-03), plus the current one so its name always shows.
     *
     * @return array<int, string>
     */
    protected static function facultyOptions(?Department $record): array
    {
        $label = fn (Faculty $f): string => "{$f->short_name} — {$f->name}";
        $options = Faculty::query()->ordered()->get()->mapWithKeys(fn (Faculty $f): array => [$f->id => $label($f)])->all();

        if ($record?->faculty_id && ! isset($options[$record->faculty_id])) {
            $current = UserScopeFilter::withoutScope(fn () => Faculty::query()->withTrashed()->find($record->faculty_id));
            $options = $current ? [$current->id => $label($current)] + $options : $options;
        }

        return $options;
    }
}
