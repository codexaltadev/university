<?php

namespace Modules\Scholarship\Filament\Resources\Scholarships\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Scholarship\Models\Scholarship;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;

/**
 * A scholarship or a part of one (DB-03 §11.3): texts per language on the left; where it
 * belongs (top level, or under a scholarship), address, apply link, deadline, active,
 * pictures and files, SEO on the right.
 */
class ScholarshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")->label(__('scholarship::scholarship.fields.title'))->required($isDefault)->maxLength(255),
                        Textarea::make("summary.{$locale}")->label(__('scholarship::scholarship.fields.summary'))
                            ->helperText(__('scholarship::scholarship.help.summary'))->rows(2)->maxLength(1000),
                        RichEditor::make("details.{$locale}")->label(__('scholarship::scholarship.fields.details'))->fileAttachments(false),
                        RichEditor::make("eligibility.{$locale}")->label(__('scholarship::scholarship.fields.eligibility'))->fileAttachments(false),
                        RichEditor::make("how_to_apply.{$locale}")->label(__('scholarship::scholarship.fields.how_to_apply'))->fileAttachments(false),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('scholarship::scholarship.sections.scholarship'))->compact()->schema([
                        Select::make('parent_id')->label(__('scholarship::scholarship.fields.parent'))
                            ->helperText(__('scholarship::scholarship.help.parent'))
                            ->placeholder(__('scholarship::scholarship.top_level'))
                            ->options(fn (?Scholarship $record): array => static::parentOptions($record))
                            ->default(fn (): ?int => request()->integer('parent') ?: null)
                            ->disabled(fn (?Scholarship $record): bool => (bool) $record?->parts()->exists())   // a scholarship with parts stays on top
                            ->dehydrated()
                            ->searchable(),
                        WebAddressField::make(prefix: '/'.Scholarship::PATH.'/'),
                        TextInput::make('apply_url')->label(__('scholarship::scholarship.fields.apply_url'))->url()->maxLength(2048),
                        DateTimePicker::make('deadline_at')->label(__('scholarship::scholarship.fields.deadline_at'))
                            ->helperText(__('scholarship::scholarship.help.deadline_at'))->seconds(false),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    Section::make(__('scholarship::scholarship.sections.media'))->compact()->schema([
                        MediaPicker::make('image')->label(__('scholarship::scholarship.fields.image'))->image(),
                        MediaPicker::make('attachments')->label(__('scholarship::scholarship.fields.attachments'))->multiple(),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    /**
     * Top-level scholarships a part can go under (not itself).
     *
     * @return array<int, string>
     */
    protected static function parentOptions(?Scholarship $record): array
    {
        return Scholarship::query()->topLevel()
            ->when($record?->exists, fn ($q) => $q->whereKeyNot($record->getKey()))
            ->ordered()->get()
            ->mapWithKeys(fn (Scholarship $s): array => [$s->id => $s->title])
            ->all();
    }
}
