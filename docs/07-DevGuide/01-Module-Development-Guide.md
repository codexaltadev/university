# Module Development Guide

| বিষয় | তথ্য |
|---|---|
| Document | Development Guide — Part A: Module |
| Version | 1.0 |
| Date | 2026-10-08 |
| Status | ✔ Approved |
| Based on | SAD Part A (§2 dependency, §4 structure, §5 manifest, §6 base model, §7 extension, §9 naming, §10 lifecycle), SAD Part B (§8.6 Blade + Livewire), DB-01 / DB-02 convention, UI/UX Part A (admin pattern) |
| পাঠক | Laravel developer যিনি নতুন installable module বানাবেন (DIU team বা অন্য university / third party) |

### Development Guide-এর গঠন

| Part | বিষয় | Status |
|---|---|---|
| **A** | Module Development — এই document, একটি সম্পূর্ণ উদাহরণ (FAQ module) সহ | **✔ Approved** |
| B | [Theme Development](02-Theme-Development-Guide.md) | ✔ Approved |

> **গুরুত্বপূর্ণ:** `Platform\…` namespace-এর class, `module:make-uwms` command ও `$table->platformColumns()` macro **kernel**-এর অংশ (SAD Part A §3)। এই guide সেগুলোর **নির্ধারিত API** দেখায়; kernel তৈরির সময় (development phase 1) signature সামান্য বদলালে এই guide একসাথে update হবে। Filament Shield-এর সাথে সংযোগের অংশ POC-1 থেকে 4 (SAD Part A §7.4) যাচাইয়ের পর চূড়ান্ত।

## সূচি

1. [শুরুর আগে](#1-শুরুর-আগে)
2. [৫টি নিয়ম যা ভাঙা যাবে না](#2-৫টি-নিয়ম-যা-ভাঙা-যাবে-না)
3. [Quick start: module তৈরি](#3-quick-start-module-তৈরি)
4. [ধাপ ১ — Manifest (`module.json`)](#4-ধাপ-১--manifest-modulejson)
5. [ধাপ ২ — Database migration](#5-ধাপ-২--database-migration)
6. [ধাপ ৩ — Model](#6-ধাপ-৩--model)
7. [ধাপ ৪ — Extension (registration)](#7-ধাপ-৪--extension-registration)
8. [ধাপ ৫ — Admin Panel (Filament)](#8-ধাপ-৫--admin-panel-filament)
9. [ধাপ ৬ — Permission ও Policy](#9-ধাপ-৬--permission-ও-policy)
10. [ধাপ ৭ — Frontend (route, view, Livewire)](#10-ধাপ-৭--frontend-route-view-livewire)
11. [ধাপ ৮ — Homepage section, Search, Statistics, Reminder](#11-ধাপ-৮--homepage-section-search-statistics-reminder)
12. [ধাপ ৯ — Translation ও Settings](#12-ধাপ-৯--translation-ও-settings)
13. [ধাপ ১০ — Seeder](#13-ধাপ-১০--seeder)
14. [ধাপ ১১ — Test](#14-ধাপ-১১--test)
15. [ধাপ ১২ — Package, version ও update](#15-ধাপ-১২--package-version-ও-update)
16. [অন্য module-এর সাথে কাজ](#16-অন্য-module-এর-সাথে-কাজ)
17. [Submission checklist](#17-submission-checklist)
18. [Reference](#18-reference)

---

## 1. শুরুর আগে

**যা জানা দরকার:** Laravel 13 (Eloquent, migration, Blade), Filament 5 (resource, form, table), Livewire 4-এর মৌলিক বিষয়।

**Development environment:**

| প্রয়োজন | Version |
|---|---|
| PHP | 8.4 (ন্যূনতম 8.3) |
| Composer, Node 20+ | — |
| MySQL | 8.0+ |
| Platform core | `requires_core` অনুযায়ী (এই guide: `^1.0`) |

Local-এ platform clone করে `composer install && npm ci && php artisan migrate --seed` চালান; সব bundled module ও default theme চালু হয়ে যাবে। নতুন module `Modules/` folder-এ তৈরি হবে।

**উদাহরণ module:** এই guide জুড়ে আমরা **FAQ** module (SRS M-28, DB-05 §13) বানাব — ছোট, কিন্তু প্রায় সব extension point ব্যবহার করে: translatable field, hierarchical category, Related To (attachable), Livewire search, homepage section, search provider, freshness reminder।

---

## 2. ৫টি নিয়ম যা ভাঙা যাবে না

এগুলো install validator ও CI architecture test যাচাই করে (SAD Part A §2.2, §10.1); ভাঙলে module install হবে না।

| # | নিয়ম | উদাহরণ |
|---|---|---|
| R-1 | **সবকিছু নিজের slug দিয়ে prefix** — table, route, view, config, translation, morph alias, custom permission | `faq_faqs`, `faq.index`, `faq::listing.accordion`, `faq.faq` |
| R-2 | **Core বা অন্য module-এর code / table সরাসরি ছোঁয়া নিষেধ** — যোগাযোগ শুধু kernel registry, contract ও event দিয়ে | ✘ `DB::table('news_posts')`; ✔ `RelationEngine`, event listener |
| R-3 | **অন্য installable module-এর class `use` শুধু `dependencies`-এ থাকলে**; optional dependency-র class কখনো সরাসরি নয় | FAQ কোনো module-এর উপর নির্ভর করে না |
| R-4 | **সব business model `BaseModel` extend করে**, সব business table `platformColumns()` পায় | Soft delete, `is_active`, blame, log automatic |
| R-5 | **Composer dependency নয়** — `composer.json`-এর `require` খালি; প্রয়োজনীয় package core release-এ যোগের অনুরোধ করুন (ADR-03) | — |

---

## 3. Quick start: module তৈরি

```bash
php artisan module:make-uwms Faq --slug=faq
```

যা তৈরি হয় (SAD Part A §4 অনুযায়ী; Filament v5 modular standard):

```
Modules/Faq/
├── module.json
├── composer.json                 # PSR-4 only
├── README.md                     # template — পূরণ করতে হবে
├── CHANGELOG.md
├── app/
│   ├── FaqExtension.php          # ★ registration
│   ├── Providers/FaqServiceProvider.php
│   ├── Providers/RouteServiceProvider.php
│   ├── Models/Faq.php
│   ├── Filament/
│   │   ├── FaqPlugin.php
│   │   └── Resources/
│   │       └── Faqs/                         # plural model নাম (Filament v5 default)
│   │           ├── FaqResource.php           # main file — শুধু hook
│   │           ├── Pages/
│   │           │   ├── ListFaqs.php
│   │           │   ├── CreateFaq.php
│   │           │   └── EditFaq.php
│   │           ├── Schemas/
│   │           │   └── FaqForm.php
│   │           └── Tables/
│   │               └── FaqsTable.php
│   ├── Http/Controllers/Frontend/FaqController.php
│   ├── Livewire/FaqSearch.php
│   ├── Policies/FaqPolicy.php
│   └── Services/FaqQuery.php     # data access — website ও (Release 2) API একই service ব্যবহার করবে
├── config/config.php
├── database/
│   ├── migrations/2026_10_08_000001_create_faq_faqs_table.php
│   ├── seeders/FaqDatabaseSeeder.php
│   └── factories/FaqFactory.php
├── resources/
│   ├── views/listing/accordion.blade.php
│   ├── views/sections/faq-highlights.blade.php
│   ├── views/livewire/faq-search.blade.php
│   └── lang/en/  lang/bn/
├── routes/web.php
└── tests/Feature/  tests/Unit/
```

তারপর:

```bash
php artisan uwms:module:install Modules/Faq     # development-এ ZIP ছাড়া, folder থেকে (validator একই)
php artisan uwms:module:activate faq
# ZIP থেকে: php artisan uwms:module:install path/to/faq-1.0.0.zip
```

---

## 4. ধাপ ১ — Manifest (`module.json`)

```json
{
  "name": "Faq",
  "alias": "faq",
  "description": "Frequently asked questions with categories, attachable to any entity",
  "keywords": ["content", "faq"],
  "priority": 50,
  "providers": ["Modules\\Faq\\Providers\\FaqServiceProvider"],
  "files": [],

  "uwms": {
    "slug": "faq",
    "version": "1.0.0",
    "type": "installable",
    "requires_core": "^1.0",
    "default_active": true,
    "author": { "name": "DIU Web Team", "email": "web@example.edu" },
    "license": "proprietary",
    "table_prefix": "faq_",

    "dependencies": {},
    "optional_dependencies": [],

    "morph_aliases": {
      "faq.faq": "Modules\\Faq\\Models\\Faq"
    },

    "custom_permissions": [],

    "provides": {
      "attachable": true,
      "relation_target": false,
      "microsite": false,
      "person_source": false,
      "assignable": false,
      "layouts": { "listing": ["accordion", "two-column"] },
      "homepage_sections": ["faq.highlights"],
      "menu_item_types": [],
      "metrics": ["faq.count"],
      "vocabularies": ["faq.category"],
      "searchable": true,
      "sitemap": true,
      "freshness_source": true,
      "settings": true,
      "api": false
    }
  }
}
```

| Field | মনে রাখুন |
|---|---|
| `slug` | একবার install হলে আর বদলানো যায় না (BRL-MODM-02) |
| `version` | Semantic version; প্রতিটি release-এ বাড়ান (Section 15) |
| `provides` | Extension-এ (ধাপ ৪) যা register করবেন, এখানে ঠিক তাই লিখুন — না মিললে activate বাতিল |
| `api` | Release 1-এ `false` (BRD Q-28) |

---

## 5. ধাপ ২ — Database migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_faqs', function (Blueprint $table) {
            $table->id();
            $table->json('question');                       // translatable
            $table->json('answer');                         // translatable
            $table->unsignedInteger('helpful_yes')->default(0);
            $table->unsignedInteger('helpful_no')->default(0);
            $table->timestamp('published_at')->nullable();  // HasPublishDate (BRD Q-27)
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();                      // is_active, created_by, updated_by, deleted_by,
                                                            // timestamps, softDeletes, active_key (+ index)

            $table->index(['is_active', 'deleted_at', 'published_at']); // IX-5
            $table->index('sort_order');                                  // IX-6
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_faqs');
    }
};
```

**নিয়ম (DB-01 §1, DB-02 §1, SAD Part A §6.6):**

| বিষয় | নিয়ম |
|---|---|
| Table নাম | `{table_prefix}` + plural snake (`faq_faqs`) |
| Business table | `platformColumns()` বাধ্যতামূলক; pivot table `platformPivotColumns()` |
| Translatable text | `json` |
| Category, slug, media, SEO, related entity | **Column নয়** — kernel table (Taxonomy, `core_slugs`, Media usage, `core_seo_meta`, `core_relations`) |
| Unique | Soft delete থাকলে `active_key` সহ: `$table->unique(['code', 'active_key'])` |
| Foreign key | নিজের table-এ ও kernel table-এ FK; hard dependency module-এ FK; optional dependency-তে শুধু index |
| Reference column | FK না থাকলে index বাধ্যতামূলক (CI test fail করবে) |
| `down()` | সবসময় সঠিক — uninstall ও rollback এর উপর নির্ভর করে |
| Released migration | **কখনো edit নয়** — পরিবর্তন নতুন migration-এ (Section 15) |

---

## 6. ধাপ ৩ — Model

```php
<?php

namespace Modules\Faq\Models;

use Platform\Concerns\HasPublishDate;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

class Faq extends BaseModel
{
    use HasPublishDate;     // published_at; frontendVisible()-এ যোগ হয়
    use HasSortOrder;
    use HasTaxonomy;        // faq.category
    use HasTranslations;
    use IsAttachable;       // "Show in" — Faculty / Department / Program / Office / Lab
    use IsSearchable;

    protected $table = 'faq_faqs';

    public array $translatable = ['question', 'answer'];

    protected $fillable = [
        'question',
        'answer',
        'published_at',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Search index-এ কী যাবে (IsSearchable) */
    public function toSearchDocument(string $locale): array
    {
        return [
            'title' => $this->getTranslation('question', $locale),
            'body' => strip_tags($this->getTranslation('answer', $locale)),
        ];
    }
}
```

`BaseModel` থেকে automatically যা আসে (SAD Part A §6.2): `SoftDeletes`, `HasActiveFlag`, `Blameable`, `LogsPlatformActivity`, `GuardsParentDelete`। নিজে যোগ করবেন না।

**Frontend query সবসময়:**

```php
Faq::query()->frontendVisible()->...   // is_active, deleted, published_at, module active — এক জায়গায় (SAD Part A §6.4)
```

নিজে `where('is_active', true)` লেখা **নিষেধ** — নিয়ম বদলালে সব module একসাথে বদলায় না।

---

## 7. ধাপ ৪ — Extension (registration)

Module **active** হলে kernel শুধু এই class চালায়; inactive হলে কিছুই register হয় না (SAD Part A §7.1)।

```php
<?php

namespace Modules\Faq;

use Modules\Faq\Filament\FaqPlugin;
use Modules\Faq\Freshness\FaqFreshness;
use Modules\Faq\Metrics\FaqCount;
use Modules\Faq\Models\Faq;
use Modules\Faq\Sections\FaqHighlightsSection;
use Modules\Faq\Settings\FaqSettings;
use Platform\Contracts\ModuleExtension;
use Platform\Registry\Registrar;

final class FaqExtension implements ModuleExtension
{
    public function register(Registrar $r): void
    {
        $r->filamentPlugin(FaqPlugin::class);

        $r->attachable(Faq::class);                                   // P-01 — Show in / Highlight / Main site

        $r->vocabulary('faq.category', label: 'FAQ category', hierarchical: true, multiple: false);

        $r->layouts('faq.listing', ['accordion', 'two-column']);      // Theme Management-এ নির্বাচনযোগ্য
        $r->homepageSection(FaqHighlightsSection::class);

        $r->searchable(Faq::class);
        $r->sitemap(Faq::class, route: 'faq.index');                  // FAQ-এর detail page নেই; শুধু listing
        $r->metric('faq.count', FaqCount::class);
        $r->freshnessSource(FaqFreshness::class);                     // Content Update Reminder
        $r->settings(FaqSettings::class);
    }
}
```

পূর্ণ registrar তালিকা: SAD Part A §7.2 (২২টি method)। যা আপনার module-এর দরকার নেই, তা লিখবেন না — কিন্তু যা লিখবেন, তা `module.json`-এর `provides`-এ থাকতে হবে।

---

## 8. ধাপ ৫ — Admin Panel (Filament)

### 8.1 Plugin

```php
<?php

namespace Modules\Faq\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Faq\Filament\Resources\Faqs\FaqResource;

class FaqPlugin implements Plugin
{
    public function getId(): string
    {
        return 'faq';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([FaqResource::class]);
    }

    public function boot(Panel $panel): void {}
}
```

`AdminPanelProvider` শুধু active module-এর plugin যোগ করে (ADR-05) — নিজে panel-এ resource register করবেন না।

### 8.2 Resource — শুধু hook

Filament v5-এর `make:filament-resource` যে গঠন তৈরি করে, সেটাই standard। `Resources/{PluralModel}/` folder-এর root-এ শুধু main file `{Model}Resource.php`; বাকি সব file নিজ নিজ sub-folder-এ:

| File | Location | Namespace |
|---|---|---|
| Main resource | `Resources/Faqs/FaqResource.php` | `…\Resources\Faqs` |
| Pages | `Resources/Faqs/Pages/ListFaqs.php` … | `…\Resources\Faqs\Pages` |
| Form | `Resources/Faqs/Schemas/FaqForm.php` | `…\Resources\Faqs\Schemas` |
| Infolist (view page থাকলে) | `Resources/Faqs/Schemas/FaqInfolist.php` | `…\Resources\Faqs\Schemas` |
| Table | `Resources/Faqs/Tables/FaqsTable.php` | `…\Resources\Faqs\Tables` |
| Relation manager (থাকলে) | `Resources/Faqs/RelationManagers/…` | `…\Resources\Faqs\RelationManagers` |

`module:make-uwms` ও `module:make-filament-resource {Module} {Model}` এই গঠনেই file তৈরি করে।

```php
<?php

namespace Modules\Faq\Filament\Resources\Faqs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Faq\Filament\Resources\Faqs\Pages\CreateFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\EditFaq;
use Modules\Faq\Filament\Resources\Faqs\Pages\ListFaqs;
use Modules\Faq\Filament\Resources\Faqs\Schemas\FaqForm;
use Modules\Faq\Filament\Resources\Faqs\Tables\FaqsTable;
use Modules\Faq\Models\Faq;
use Platform\Filament\Concerns\ScopedResource;
use UnitEnum;

class FaqResource extends Resource
{
    use ScopedResource;   // AppliesUserScope + trash tab + platform navigation নিয়ম

    protected static ?string $model = Faq::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
```

**Model ও Resource class নাম system-wide অনন্য** (Shield resource permission key **model-এর নাম** থেকে তৈরি হয়, যেমন `Update:Faq` — SAD Part A §7.4, POC ফলাফল)। সাধারণ নাম হলে prefix দিন: model `FaqCategory` ও `FaqCategoryResource`, শুধু `Category` / `CategoryResource` নয়।

### 8.3 Form — platform component ব্যবহার করুন

UI/UX Part A §5.2-এর standard layout: বামে content tab, ডানে Publishing / Show in / Highlight / Category।

```php
<?php

namespace Modules\Faq\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault) => [
                        TextInput::make("question.{$locale}")
                            ->label('Question')
                            ->required($isDefault)
                            ->maxLength(500),
                        RichEditor::make("answer.{$locale}")
                            ->label('Answer')
                            ->required($isDefault),
                    ]),
                ]),
                Section::make()->columnSpan(1)->schema([
                    PublishingPanel::make(),            // Active, Publish date (expiry নেই এই module-এ)
                    RelatedToField::make(),             // Show in + Main site (PublishMain) + Request (FR-CNT-H09)
                    HighlightPanel::make(),
                    TermSelect::make('category')->vocabulary('faq.category'),
                ]),
            ]),
        ]);
    }
}
```

এগুলো নিজে বানাবেন না: ভাষা tab, Related To, main site switch, highlight, media picker, person picker — সব platform component (UI/UX Part A §6)। এতে সব module-এর form একই রকম থাকে এবং scope / permission নিয়ম automatically মানে।

### 8.4 Table

```php
<?php

namespace Modules\Faq\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Platform\Filament\Tables\Columns\ActiveToggleColumn;
use Platform\Filament\Tables\Columns\ShowInColumn;
use Platform\Filament\Tables\Columns\TranslationStatusColumn;
use Platform\Filament\Tables\Concerns\PlatformTableDefaults;

class FaqsTable
{
    use PlatformTableDefaults;   // tabs (All / Active / Scheduled / Inactive / Trash), restore, force delete, activate bulk

    public static function configure(Table $table): Table
    {
        return static::applyPlatformDefaults($table)
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('question')->searchable()->limit(80),
                TranslationStatusColumn::make('question'),
                ShowInColumn::make(),
                TextColumn::make('category.name')->label('Category'),
                ActiveToggleColumn::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

সব import explicit; string class reference নয়। v5-এ সব action (`EditAction`, `DeleteBulkAction` …) `Filament\Actions` namespace-এ, আর table-এ `recordActions()` / `toolbarActions()` — পুরনো `Filament\Tables\Actions`, `actions()`, `bulkActions()` ব্যবহার করবেন না।

### 8.5 Pages

Filament v5 standard (`ListRecords`, `CreateRecord`, `EditRecord`); resource reference main file-এ:

```php
<?php

namespace Modules\Faq\Filament\Resources\Faqs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Faq\Filament\Resources\Faqs\FaqResource;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

`CreateFaq` ও `EditFaq` একইভাবে (`CreateRecord`, `EditRecord`); অতিরিক্ত কিছু লাগে না। Edit page-এ Activity tab platform automatically যোগ করে (FR-GEN-83)।

---

## 9. ধাপ ৬ — Permission ও Policy

**Permission:** Resource / page / widget-এর permission Shield activate-এর সময় automatically তৈরি করে (`ViewAny:Faq`, `Create:Faq` …)। শুধু resource-এর বাইরের কাজের জন্য manifest-এ `custom_permissions` (যেমন `Import:Course`)। `PublishMain:{Resource}` ও `HighlightMain:{Resource}` platform নিজে যোগ করে, কারণ module attachable।

**Policy:** Shield-এর generate করা policy kernel-এর `ScopedPolicy` extend করে (SAD Part A §7.4)। Scope-এর বাইরে কিছু নিজে যাচাই করতে হলে override করুন:

```php
<?php

namespace Modules\Faq\Policies;

use Platform\Policies\ScopedPolicy;

class FaqPolicy extends ScopedPolicy
{
    // ScopedPolicy: Shield permission → scope → entity permission → business rule।
    // FAQ-এর অতিরিক্ত নিয়ম নেই, তাই কিছু লিখতে হবে না।
}
```

**নিষেধ:** Controller বা Livewire-এ `if ($user->hasRole('admin'))` — সবসময় policy (`$this->authorize()` / `can()`)।

---

## 10. ধাপ ৭ — Frontend (route, view, Livewire)

### 10.1 Service (data access)

Website (এখন) ও API (Release 2) একই service ব্যবহার করবে (SAD Part B §8.6):

```php
<?php

namespace Modules\Faq\Services;

use Illuminate\Support\Collection;
use Modules\Faq\Models\Faq;
use Platform\Context\SiteContext;

class FaqQuery
{
    /** Context-এর FAQ, category অনুযায়ী grouped */
    public function grouped(SiteContext $context, ?string $search = null): Collection
    {
        return Faq::query()
            ->frontendVisible()
            ->inContext($context)              // IsAttachable: main site = site:0; microsite = related entity
            ->when($search, fn ($q) => $q->whereTranslationLike('question', "%{$search}%"))
            ->with('terms')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Faq $faq) => $faq->primaryTerm('faq.category')?->name ?? __('faq::faq.general'));
    }
}
```

### 10.2 Route

```php
<?php
// Modules/Faq/routes/web.php — platform localized ও microsite route group-এর ভেতরে load করে

use Illuminate\Support\Facades\Route;
use Modules\Faq\Http\Controllers\Frontend\FaqController;

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
// Microsite-এ automatically: /{faculty}/{department}/faq → name 'faq.index' + context (SAD Part B §6.1)
```

### 10.3 Controller

```php
<?php

namespace Modules\Faq\Http\Controllers\Frontend;

use Platform\Context\SiteContext;
use Platform\Http\Controllers\FrontendController;

class FaqController extends FrontendController
{
    public function index(SiteContext $context)
    {
        return $this->layout('faq.listing')           // Theme Management-এ নির্বাচিত layout (accordion / two-column)
            ->with(['context' => $context])
            ->seo(title: __('faq::faq.title'));
    }
}
```

### 10.4 View (layout)

`resources/views/listing/accordion.blade.php` — শুধু theme token (CSS variable) ব্যবহার, hardcoded রং বা font নয় (FR-GEN-101):

```blade
<x-platform::page-header :title="__('faq::faq.title')" />

<section class="container mx-auto">
    <livewire:faq::faq-search :context="$context" />
</section>
```

Theme চাইলে override করতে পারে: `themes/{theme}/views/modules/faq/listing/accordion.blade.php` (SAD Part B §8.2)।

### 10.5 Livewire component

```php
<?php

namespace Modules\Faq\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Faq\Models\Faq;
use Modules\Faq\Services\FaqQuery;
use Platform\Context\SiteContext;
use Platform\Livewire\Concerns\RateLimitsActions;

class FaqSearch extends Component
{
    use RateLimitsActions;   // public action-এর rate limit (API Spec §10.2-এর নিয়ম)

    public SiteContext $context;

    #[Url(as: 'q')]
    public string $search = '';

    public function helpful(int $faqId, bool $yes): void
    {
        $this->rateLimit('faq-feedback', perMinute: 5);

        $faq = Faq::query()->frontendVisible()->findOrFail($faqId);
        $faq->increment($yes ? 'helpful_yes' : 'helpful_no');
    }

    public function render(FaqQuery $query)
    {
        return view('faq::livewire.faq-search', [
            'groups' => $query->grouped($this->context, $this->search ?: null),
        ]);
    }
}
```

Service provider-এ register: `Livewire::component('faq::faq-search', FaqSearch::class)` (scaffold করে দেয়)। Livewire component full-page cache-এর বাইরে lazy-load হয় (ADR-16)।

**Accessibility:** accordion-এ `<button aria-expanded>`, search input-এ `<label>`, ফলাফলের সংখ্যা `aria-live`-এ (UI/UX Part B §6)।

---

## 11. ধাপ ৮ — Homepage section, Search, Statistics, Reminder

### 11.1 Homepage section

```php
<?php

namespace Modules\Faq\Sections;

use Modules\Faq\Models\Faq;
use Platform\Homepage\Section;
use Platform\Homepage\SectionContext;

class FaqHighlightsSection extends Section
{
    public static function key(): string { return 'faq.highlights'; }
    public static function label(): string { return __('faq::faq.section_label'); }
    public static function view(): string { return 'faq::sections.faq-highlights'; }

    /** Source mode (Latest / Highlighted / Highlighted-first / Manual) platform প্রয়োগ করে — FR-HOME-11 */
    public function items(SectionContext $ctx)
    {
        return $ctx->applySourceMode(Faq::query()->frontendVisible(), defaultCount: 5);
    }
}
```

### 11.2 Statistics metric

```php
<?php

namespace Modules\Faq\Metrics;

use Modules\Faq\Models\Faq;
use Platform\Statistics\MetricProvider;
use Platform\Statistics\StatContext;

class FaqCount implements MetricProvider
{
    public function key(): string { return 'faq.count'; }
    public function label(): string { return __('faq::faq.metric_count'); }

    public function count(StatContext $ctx, array $params): int
    {
        return Faq::query()->frontendVisible()->inContext($ctx->asSiteContext(), descendants: true)->count();
    }
}
```

### 11.3 Content Update Reminder

```php
<?php

namespace Modules\Faq\Freshness;

use Modules\Faq\Models\Faq;
use Platform\Notifications\Freshness\FreshnessSource;

class FaqFreshness extends FreshnessSource
{
    public function model(): string { return Faq::class; }
    public function linkedVia(): string { return self::VIA_RELATION; }   // core_relations দিয়ে entity-র সাথে যুক্ত
    public function updatedColumn(): string { return 'updated_at'; }
}
```

### 11.4 Search

`IsSearchable` trait + `toSearchDocument()` (Section 6) যথেষ্ট; index update platform-এর queue job করে (FR-SRCH-09)।

---

## 12. ধাপ ৯ — Translation ও Settings

**Translation:** `resources/lang/en/faq.php` ও `resources/lang/bn/faq.php`; সব string `__('faq::faq.key')`। Admin-এর translation override (FR-LANG-03) automatically কাজ করে।

```php
// resources/lang/bn/faq.php
return [
    'title' => 'প্রায়শ জিজ্ঞাসিত প্রশ্ন',
    'general' => 'সাধারণ',
    'section_label' => 'নির্বাচিত প্রশ্ন',
    'metric_count' => 'প্রশ্নের সংখ্যা',
    'helpful' => 'এই উত্তর কি কাজে লেগেছে?',
];
```

**Settings** (Website Settings-এ "FAQ" tab — FR-SET-10):

```php
<?php

namespace Modules\Faq\Settings;

use Filament\Forms\Components\Toggle;
use Platform\Settings\ModuleSettings;

class FaqSettings extends ModuleSettings
{
    public function group(): string { return 'faq'; }

    public function schema(): array
    {
        return [
            Toggle::make('show_helpful_buttons')->label('Show “Was this helpful?”')->default(true),
        ];
    }
}
// পড়া: settings('faq.show_helpful_buttons')
```

---

## 13. ধাপ ১০ — Seeder

Install-এর সময় Module Manager চালায় (ADR-06) — **শুধু প্রয়োজনীয় default data**, কোনো demo content নয়:

```php
<?php

namespace Modules\Faq\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Taxonomy\Vocabularies;

class FaqDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Vocabularies::ensureTerms('faq.category', [
            ['slug' => 'general', 'name' => ['en' => 'General', 'bn' => 'সাধারণ']],
        ]);
    }
}
```

Seeder **idempotent** হতে হবে (দ্বিতীয়বার চালালে duplicate নয়) — update ও reinstall-এ আবার চলে।

---

## 14. ধাপ ১১ — Test

Pest; প্রতিটি module-এ ন্যূনতম তিন ধরনের test (NFR-MNT-03, SAD Part A §11):

```php
<?php

use Modules\Faq\Filament\Resources\Faqs\FaqResource;
use Modules\Faq\Models\Faq;
use Platform\Testing\ModuleLifecycle;

// ১. Lifecycle — install → activate → deactivate → reactivate → uninstall
it('survives the full module lifecycle', function () {
    ModuleLifecycle::for('faq')->assertFullCycle(keepDataOnUninstall: true);
});

// ২. Visibility — frontendVisible() ও context
it('hides inactive and future-dated FAQs from the website', function () {
    $visible = Faq::factory()->create(['is_active' => true, 'published_at' => now()->subDay()]);
    Faq::factory()->create(['is_active' => false]);
    Faq::factory()->create(['published_at' => now()->addDay()]);

    expect(Faq::frontendVisible()->pluck('id')->all())->toBe([$visible->id]);
});

// ৩. Scope — অন্য department-এর FAQ edit করা যায় না
it('forbids editing a FAQ outside the user scope', function () {
    $faq = Faq::factory()->relatedTo('department:eee')->create();

    actingAsScopedUser('department:cse', permissions: ['Update:Faq'])
        ->get(FaqResource::getUrl('edit', ['record' => $faq]))
        ->assertForbidden();
});
```

চালানো: `php artisan test Modules/Faq` — CI-তে architecture test (R-1 থেকে R-5) ও migration index test আলাদাভাবে চলে।

---

## 15. ধাপ ১২ — Package, version ও update

### 15.1 ZIP তৈরি

```bash
php artisan uwms:module:package faq      # → faq-1.0.0.zip  (command আসছে; ততদিন module folder হাতে ZIP করুন, root-এ `Faq/`)
```

Command যাচাই করে ও বাদ দেয়: `vendor/`, `node_modules/`, `.git/`, `tests/` (ঐচ্ছিক), symlink, `.env`। ZIP-এর root-এ `Faq/` folder থাকবে।

### 15.2 Version নিয়ম

| পরিবর্তন | Version |
|---|---|
| Bug fix, view পরিবর্তন | PATCH (`1.0.1`) |
| নতুন field / feature, নতুন migration (পুরনো data অক্ষত) | MINOR (`1.1.0`) |
| Column মুছে ফেলা, অর্থ বদল, `requires_core` major পরিবর্তন | MAJOR (`2.0.0`) — CHANGELOG-এ migration নির্দেশনা বাধ্যতামূলক |

### 15.3 Update-এর নিয়ম

* **Release হওয়া migration কখনো বদলাবেন না** — নতুন migration file যোগ করুন।
* Column মুছতে হলে আগের version-এ deprecated, পরের MAJOR-এ বাদ।
* `slug`, `morph_aliases`-এর key, `table_prefix` কখনো বদলাবে না।
* Update ZIP install-এর মতোই validate হয়; downgrade নিষেধ (FR-MODM-10)।

---

## 16. অন্য module-এর সাথে কাজ

| প্রয়োজন | কীভাবে | উদাহরণ |
|---|---|---|
| Content কোনো Department / Program-এর সাথে যুক্ত | `attachable()` + `RelatedToField` — target module-কে চেনার দরকার নেই | FAQ "Show in: CSE" |
| ব্যক্তি দেখানো (speaker, author, contact) | `assignable(roles: [...])` + `PersonPicker`; role key Standard Role Catalog-এ (DB-01 §8.4) | Event speaker |
| নিজের entity-তে অন্যরা content যুক্ত করবে | `relationTarget(parent: ...)` (+ `microsite`, `scopeable`) | Department |
| অন্য module-এর page-এ tab / section যোগ | `entityTab()` | Tuition tab → Program page |
| Hard dependency-র model ব্যবহার | `dependencies`-এ রাখুন, তারপর সরাসরি `use` ও FK | Department → Faculty |
| Optional dependency | Registry / contract / event দিয়ে; `Modules::active('alumni')` যাচাই; class সরাসরি নয় | Alumni → Program |
| কিছু ঘটলে প্রতিক্রিয়া | Kernel event listen (SAD Part A §8) | `ContentPublished` |

---

## 17. Submission checklist

Module ZIP দেওয়ার আগে:

- [ ] `module.json`-এর সব বাধ্যতামূলক field; `provides` ↔ Extension মিলছে
- [ ] সব table `table_prefix` দিয়ে; business table-এ `platformColumns()`; reference column-এ index
- [ ] `down()` সব migration-এ কাজ করে
- [ ] সব model `BaseModel`; frontend query শুধু `frontendVisible()`
- [ ] Model / resource / page / widget class নাম system-wide অনন্য
- [ ] Form-এ platform component (TranslatableTabs, PublishingPanel, RelatedToField …)
- [ ] কোনো hardcoded রং / font নয়; view theme override-যোগ্য
- [ ] Public Livewire action-এ rate limit; form-এ validation ও honeypot
- [ ] `en` ও `bn` translation file
- [ ] Seeder idempotent, demo data নেই
- [ ] Test: lifecycle, visibility, scope — সব pass
- [ ] `composer.json`-এ `require` খালি; ZIP-এ `vendor/` নেই
- [ ] README: module কী করে, settings, permission, route, layout তালিকা, আর **Views** অংশ — প্রতিটি view ও Livewire view-এর নাম, দেওয়া variable এবং `wire:` property / action (theme override-এর contract — Theme Guide §9–10)
- [ ] CHANGELOG-এ version entry

---

## 18. Reference

| বিষয় | কোথায় |
|---|---|
| Layer ও dependency নিয়ম | SAD Part A §2 |
| Manifest field reference | SAD Part A §5.2 |
| Base model ও সব trait | SAD Part A §6 |
| Foreign key ও index নিয়ম | SAD Part A §6.6, DB-02 §1 |
| Registrar method (২২টি) | SAD Part A §7.2 |
| Permission architecture (Shield + Scope) | SAD Part A §7.4 |
| Event catalog | SAD Part A §8 |
| Naming convention | SAD Part A §9 |
| Install / update / uninstall-এর ভেতরের ধাপ | SAD Part A §10 |
| Context, fallback, highlight engine | SAD Part B §3–§6 |
| Blade + Livewire নিয়ম | SAD Part B §8.6 |
| Admin form ও list pattern | UI/UX Part A §5–§6 |
| Frontend component ও accessibility | UI/UX Part B §6–§8 |
| Kernel table (relation, assignment, slug, media, taxonomy …) | DB-01, DB-02 |
