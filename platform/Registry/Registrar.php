<?php

namespace Platform\Registry;

/**
 * Passed to a module's Extension; records what the module provides
 * (SAD Part A §7.2). One method per extension point. Each kind maps to a
 * uwms.provides key in module.json, and the two must match (§10.2).
 */
class Registrar
{
    /** registrar kind => uwms.provides key */
    public const PROVIDES = [
        'relationTarget' => 'relation_target',
        'attachable' => 'attachable',
        'personSource' => 'person_source',
        'assignable' => 'assignable',
        'scopeable' => 'scopeable',
        'micrositeEntity' => 'microsite',
        'dynamicPermissions' => 'dynamic_permissions',
        'layouts' => 'layouts',
        'homepageSection' => 'homepage_sections',
        'pageBlock' => 'page_blocks',
        'entityTab' => 'entity_tabs',
        'entityPage' => 'entity_pages',
        'entityFormPart' => 'entity_form_parts',
        'micrositeTool' => 'microsite_tools',
        'websitePage' => 'website_pages',
        'menuItemType' => 'menu_item_types',
        'vocabulary' => 'vocabularies',
        'metric' => 'metrics',
        'searchable' => 'searchable',
        'sitemap' => 'sitemap',
        'apiResource' => 'api',
        'settings' => 'settings',
        'notification' => 'notifications',
        'freshnessSource' => 'freshness_source',
        'dashboardWidget' => 'dashboard_widgets',
    ];

    public function __construct(protected Registry $registry, public readonly string $module) {}

    /** Admin resources, pages and widgets (§7.3). Not a provides key: every module may have one. */
    public function filamentPlugin(string $pluginClass): static
    {
        return $this->add('filamentPlugin', ['class' => $pluginClass]);
    }

    /**
     * Content can be attached to this model; it is a node of the entity hierarchy.
     * $parent is the parent model class (a hard dependency), $parentKey its column,
     * e.g. relationTarget(Department::class, parent: Faculty::class, parentKey: 'faculty_id').
     */
    public function relationTarget(string $model, ?string $parent = null, ?string $parentKey = null): static
    {
        return $this->add('relationTarget', compact('model', 'parent', 'parentKey'));
    }

    public function attachable(string $model): static
    {
        return $this->add('attachable', compact('model'));
    }

    public function personSource(string $model): static
    {
        return $this->add('personSource', compact('model'));
    }

    /**
     * People can be assigned to this model in these roles. Keys come from the
     * Standard Role Catalog (config/platform.php "role_catalog", DB-01 §8.4); list
     * order is the role order on the website.
     *
     * @param  list<string>  $roles
     */
    public function assignable(string $model, array $roles = []): static
    {
        return $this->add('assignable', compact('model', 'roles'));
    }

    public function scopeable(string $model): static
    {
        return $this->add('scopeable', compact('model'));
    }

    /**
     * The model has a microsite at /{routePrefix}/{slug}. $home = the module's view of
     * the microsite homepage until the entity builds its own (FR-FAC-06), e.g.
     * 'faculty::microsite.home' (gets $entity and $context).
     */
    /**
     * A microsite entity (SRS FR-GEN-34): its URL is "/{routePrefix}/{slug}" without a parent,
     * else under the parent's — "/fsit/cse", or with a $childSegment "/fsit/cse/lab/robotics".
     */
    public function micrositeEntity(string $model, string $routePrefix, ?string $parent = null, ?string $home = null, ?string $childSegment = null): static
    {
        return $this->add('micrositeEntity', compact('model', 'routePrefix', 'parent', 'home', 'childSegment'));
    }

    /** Model implements Platform\Contracts\HasEntityPermissions (subject, actions, label). */
    public function dynamicPermissions(string $model): static
    {
        return $this->add('dynamicPermissions', compact('model'));
    }

    /** @param list<string> $layouts */
    public function layouts(string $page, array $layouts): static
    {
        return $this->add('layouts', compact('page', 'layouts'));
    }

    public function homepageSection(string $sectionClass): static
    {
        return $this->add('homepageSection', ['class' => $sectionClass]);
    }

    public function pageBlock(string $blockClass): static
    {
        return $this->add('pageBlock', ['class' => $blockClass]);
    }

    public function entityTab(string $targetModel, string $tabClass): static
    {
        return $this->add('entityTab', ['target' => $targetModel, 'class' => $tabClass]);
    }

    /**
     * A part of another entity's admin Hub (FR-GEN-86, SAD Part A §7.3): a Filament
     * resource page of the target's resource, e.g.
     *   entityPage(Faculty::class, FacultyDepartments::class)   // "Departments" in the Faculty Hub
     *   entityPage('microsite', MicrositeMenuPage::class)       // in every microsite entity's Hub
     */
    public function entityPage(string $target, string $pageClass): static
    {
        return $this->add('entityPage', ['target' => $target, 'class' => $pageClass]);
    }

    /**
     * A read-only part at the end of another entity's editor (UI/UX Part A §5.2.1), e.g.
     *   entityFormPart(Program::class, ProgramTuitionPart::class)   // "Tuition fees" below the Program's parts
     * The part class extends Platform\Filament\Hub\EntityFormPart; the target never knows the module.
     */
    public function entityFormPart(string $targetModel, string $partClass): static
    {
        return $this->add('entityFormPart', ['target' => $targetModel, 'class' => $partClass]);
    }

    /**
     * A screen editing what a microsite owns (UI/UX Part A §4.1), e.g. micrositeTool(ManageMenus::class):
     * it implements Platform\Context\MicrositeTool and appears in the entity editors' "Microsite" button.
     */
    public function micrositeTool(string $pageClass): static
    {
        return $this->add('micrositeTool', ['class' => $pageClass]);
    }

    /**
     * A listing page of the module's website, e.g. websitePage('scholarship.index', label: 'scholarship::scholarship.plural',
     * path: '/scholarships'): Menus offer it as a "Module page"; the item hides while the module is off.
     */
    public function websitePage(string $key, string $label, string $path): static
    {
        return $this->add('websitePage', compact('key', 'label', 'path'));
    }

    public function menuItemType(string $model): static
    {
        return $this->add('menuItemType', compact('model'));
    }

    public function vocabulary(string $key, string $label, bool $hierarchical = false, bool $multiple = true): static
    {
        return $this->add('vocabulary', compact('key', 'label', 'hierarchical', 'multiple'));
    }

    public function metric(string $key, string $providerClass): static
    {
        return $this->add('metric', ['key' => $key, 'class' => $providerClass]);
    }

    /**
     * Records of this model are in the website search (C-17, FR-SRCH-01); the model
     * uses IsSearchable and has url($locale). $label = the result group (a translation
     * key or text), $weight = 1–10, higher ranks first.
     */
    public function searchable(string $model, ?string $label = null, int $weight = 5): static
    {
        return $this->add('searchable', compact('model', 'label', 'weight'));
    }

    public function sitemap(string $model, ?string $route = null): static
    {
        return $this->add('sitemap', compact('model', 'route'));
    }

    public function apiResource(string $resourceClass): static
    {
        return $this->add('apiResource', ['class' => $resourceClass]);
    }

    public function settings(string $settingsClass): static
    {
        return $this->add('settings', ['class' => $settingsClass]);
    }

    public function notification(string $notificationClass): static
    {
        return $this->add('notification', ['class' => $notificationClass]);
    }

    public function freshnessSource(string $sourceClass): static
    {
        return $this->add('freshnessSource', ['class' => $sourceClass]);
    }

    public function dashboardWidget(string $widgetClass): static
    {
        return $this->add('dashboardWidget', ['class' => $widgetClass]);
    }

    /** @param array<string, mixed> $entry */
    protected function add(string $kind, array $entry): static
    {
        $this->registry->add($kind, $this->module, $entry);

        return $this;
    }
}
