<?php

namespace Platform\Modules;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Throwable;

/**
 * Manifest rules (SAD Part A §5.2, §10.1 step 3–4). Returns readable errors;
 * an empty list means the manifest is valid.
 */
class ManifestValidator
{
    /** provides keys the platform understands */
    public const PROVIDES_KEYS = [
        'relation_target', 'attachable', 'person_source', 'assignable', 'scopeable', 'microsite',
        'layouts', 'homepage_sections', 'page_blocks', 'entity_tabs', 'entity_pages', 'entity_form_parts', 'microsite_tools', 'website_pages', 'menu_item_types', 'vocabularies',
        'metrics', 'searchable', 'sitemap', 'api', 'settings', 'notifications', 'freshness_source',
        'dashboard_widgets', 'dynamic_permissions',
    ];

    /**
     * @return list<string>
     */
    public function validate(Manifest $manifest, bool $fromZip = false): array
    {
        $errors = [];

        if (! is_array($manifest->data['uwms'] ?? null)) {
            return ['module.json has no "uwms" block.'];
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $manifest->name())) {
            $errors[] = 'name must be PascalCase (folder and namespace), e.g. "FacultyMember".';
        }

        if (! preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $manifest->slug())) {
            $errors[] = 'uwms.slug must be lowercase letters, digits and "-", e.g. "faculty-member".';
        }

        if (! preg_match('/^\d+\.\d+\.\d+$/', $manifest->version())) {
            $errors[] = 'uwms.version must be a semantic version (MAJOR.MINOR.PATCH).';
        }

        if (! in_array($manifest->type(), ['core', 'installable'], true)) {
            $errors[] = 'uwms.type must be "core" or "installable".';
        } elseif ($fromZip && $manifest->isCore()) {
            $errors[] = 'A module installed from ZIP must be of type "installable".';
        }

        $errors = [...$errors, ...$this->checkCoreVersion($manifest)];

        if ($manifest->slug() !== '' && $manifest->tablePrefix() !== $manifest->expectedTablePrefix()) {
            $errors[] = sprintf('uwms.table_prefix must be "%s".', $manifest->expectedTablePrefix());
        }

        foreach ($manifest->dependencies() as $slug => $constraint) {
            if (! $this->isConstraint((string) $constraint)) {
                $errors[] = "Dependency \"{$slug}\" has an invalid version constraint.";
            }
        }

        foreach ($manifest->morphAliases() as $alias => $class) {
            if (! str_starts_with((string) $alias, $manifest->slug().'.')) {
                $errors[] = "Morph alias \"{$alias}\" must start with \"{$manifest->slug()}.\" (ADR-17).";
            }
            if (! str_starts_with((string) $class, $manifest->namespace().'\\')) {
                $errors[] = "Morph alias \"{$alias}\" must point to a class of this module.";
            }
        }

        foreach (array_keys($manifest->provides()) as $key) {
            if (! in_array($key, self::PROVIDES_KEYS, true)) {
                $errors[] = "Unknown uwms.provides key \"{$key}\".";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function checkCoreVersion(Manifest $manifest): array
    {
        $constraint = $manifest->requiresCore();

        if (! $this->isConstraint($constraint)) {
            return ['uwms.requires_core must be a version constraint such as "^1.0".'];
        }

        if (! Semver::satisfies(config('platform.version'), $constraint)) {
            return [sprintf('Needs core %s, this system runs %s.', $constraint, config('platform.version'))];
        }

        return [];
    }

    protected function isConstraint(string $constraint): bool
    {
        if ($constraint === '') {
            return false;
        }

        try {
            (new VersionParser)->parseConstraints($constraint);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
