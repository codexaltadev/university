<?php

namespace Modules\Seo\Filament\Resources\Redirects\Schemas;

use Closure;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Support\Redirects;

/**
 * From an old address to a URL, a path or a record of the website (P-15).
 * Saving refuses loops and resolves chains (FR-SEO-09).
 */
class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('source_path')->label(__('seo::seo.fields.source_path'))
                    ->helperText(__('seo::seo.help.source_path'))
                    ->required()->maxLength(2048)->placeholder('/old-page')->columnSpanFull()
                    ->rule(fn (Get $get, ?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $problem = app(Redirects::class)->problem([
                            'source_path' => $value,
                            'destination_url' => $get('kind') === 'record' ? null : $get('destination_url'),
                            'target' => $get('kind') === 'record' ? $get('target') : null,
                        ], $record?->id);

                        if ($problem) {
                            $fail($problem);
                        }
                    }),
                Radio::make('kind')->label(__('seo::seo.fields.kind'))
                    ->options(['url' => __('seo::seo.kinds.url'), 'record' => __('seo::seo.kinds.record')])
                    ->default('url')->inline()->live()->dehydrated(false)
                    ->afterStateHydrated(fn (Radio $component, ?Redirect $record) => $component->state($record?->target_type ? 'record' : 'url'))
                    ->columnSpanFull(),
                TextInput::make('destination_url')->label(__('seo::seo.fields.destination_url'))
                    ->helperText(__('seo::seo.help.destination_url'))
                    ->placeholder('/new-page  ·  https://…')->maxLength(2048)
                    ->visible(fn (Get $get): bool => $get('kind') !== 'record')
                    ->required(fn (Get $get): bool => $get('kind') !== 'record')
                    ->columnSpanFull(),
                Select::make('target')->label(__('seo::seo.fields.target'))
                    ->helperText(__('seo::seo.help.target'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(Redirects::class)->searchTargets($search))
                    ->getOptionLabelUsing(fn (?string $value): ?string => app(Redirects::class)->targetLabel(app(Redirects::class)->resolveTarget($value)))
                    ->afterStateHydrated(fn (Select $component, ?Redirect $record) => $record?->target_type ? $component->state($record->target_type.':'.$record->target_id) : null)
                    ->visible(fn (Get $get): bool => $get('kind') === 'record')
                    ->required(fn (Get $get): bool => $get('kind') === 'record')
                    ->dehydrated(fn (Get $get): bool => $get('kind') === 'record')
                    ->columnSpanFull(),
                Select::make('status_code')->label(__('seo::seo.fields.status_code'))
                    ->options([301 => __('seo::seo.status.301'), 302 => __('seo::seo.status.302')])
                    ->default(301)->required()->selectablePlaceholder(false),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
        ]);
    }
}
