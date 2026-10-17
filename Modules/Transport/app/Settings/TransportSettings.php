<?php

namespace Modules\Transport\Settings;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Modules\Transport\Models\ServiceType;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Settings\ModuleSettings;

/**
 * Website Settings › Transport (DB-06 v1.2 §2): which service runs now (FR-TRN-03), the schedule's
 * title, semester and rules PDF (FR-TRN-08, legacy transport_settings), and the page's own text —
 * introduction, "Transport Pass", fees table (defaults: DIU's current page).
 * Read: settings('transport.running_service_id'), settings('transport.intro') (current language) …
 */
class TransportSettings extends ModuleSettings
{
    public function group(): string
    {
        return 'transport';
    }

    public function label(): string
    {
        return __('transport::transport.title');
    }

    public function defaults(): array
    {
        return [
            'running_service_id' => null,
            'schedule_title' => null,
            'semester' => null,
            'policy_file' => null,
            'intro' => ['en' => 'From Daffodil Smart City, Daffodil International University operates a safe, reliable, and affordable transport system with 50+ dedicated buses, smart route management, and emergency support — ensuring smooth, worry-free commutes for students, faculty, and staff.'],
            'pass_title' => ['en' => 'Get Your DIU Transport Pass'],
            'pass_subtitle' => ['en' => 'A secure pass that lets you ride DIU\'s buses easily.'],
            'pass_how' => ['en' => 'Pay the transport fee, then register online via the student portal or offline at the Transport Office. Your pass will be issued once approved.'],
            'pass_why' => ['en' => 'Required for boarding DIU buses — ensures student verification and smooth, secure commutes.'],
            'pass_image' => null,
            'fees' => [
                ['route' => 'Dhanmondi', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
                ['route' => 'Uttara', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
                ['route' => 'ECB-Mirpur', 'tri' => '3000/=tk', 'bi' => '4500/=tk'],
            ],
            'fees_note' => null,
        ];
    }

    public function translatable(): array
    {
        return ['schedule_title', 'semester', 'intro', 'pass_title', 'pass_subtitle', 'pass_how', 'pass_why', 'fees_note'];
    }

    public function schema(): array
    {
        return [
            Section::make(__('transport::transport.settings.schedule'))->compact()->schema([
                Select::make('running_service_id')->label(__('transport::transport.settings.running_service'))
                    ->helperText(__('transport::transport.settings.running_service_help'))
                    ->options(fn (): array => ServiceType::query()->where('is_active', true)->ordered()->get()->mapWithKeys(fn (ServiceType $t): array => [$t->id => (string) $t->name])->all())
                    ->placeholder(__('transport::transport.settings.default_service')),
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    TextInput::make("schedule_title.{$locale}")->label(__('transport::transport.settings.schedule_title'))->placeholder('Special Transport Schedule for Exam-2026')->maxLength(200),
                    TextInput::make("semester.{$locale}")->label(__('transport::transport.settings.semester'))->placeholder('Semester : Summer-2026')->maxLength(100),
                ]),
                MediaPicker::make('policy_file')->label(__('transport::transport.settings.policy_file'))->storeIds(),
            ]),
            Section::make(__('transport::transport.settings.page'))->compact()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    Textarea::make("intro.{$locale}")->label(__('transport::transport.settings.intro'))->rows(3)->maxLength(1000),
                    TextInput::make("pass_title.{$locale}")->label(__('transport::transport.settings.pass_title'))->maxLength(150),
                    TextInput::make("pass_subtitle.{$locale}")->label(__('transport::transport.settings.pass_subtitle'))->maxLength(255),
                    Textarea::make("pass_how.{$locale}")->label(__('transport::transport.pass.how'))->rows(2)->maxLength(600),
                    Textarea::make("pass_why.{$locale}")->label(__('transport::transport.pass.why'))->rows(2)->maxLength(600),
                    Textarea::make("fees_note.{$locale}")->label(__('transport::transport.settings.fees_note'))->rows(2)->maxLength(600),
                ]),
                MediaPicker::make('pass_image')->label(__('transport::transport.settings.pass_image'))->image()->storeIds(),
                Repeater::make('fees')->label(__('transport::transport.fees.title'))->helperText(__('transport::transport.settings.fees_help'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('route')->label(__('transport::transport.fees.route'))->required()->maxLength(100),
                            TextInput::make('tri')->label(__('transport::transport.fees.tri'))->maxLength(50),
                            TextInput::make('bi')->label(__('transport::transport.fees.bi'))->maxLength(50),
                        ]),
                    ])
                    ->reorderable()->collapsible()->defaultItems(0)->itemLabel(fn (array $state): ?string => $state['route'] ?? null),
            ]),
        ];
    }
}
