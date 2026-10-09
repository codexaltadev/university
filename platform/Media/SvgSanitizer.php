<?php

namespace Platform\Media;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Removes anything executable from an SVG (FR-MEDIA-13): script, foreignObject,
 * event attributes (onload …) and javascript: / data:text links. External entities are never loaded.
 */
class SvgSanitizer
{
    protected const REMOVED_ELEMENTS = ['script', 'foreignObject', 'iframe', 'embed', 'object', 'handler', 'listener'];

    /** @throws MediaRejected */
    public function clean(string $path, string $originalName): void
    {
        $svg = (string) file_get_contents($path);

        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $svg)) {
            throw new MediaRejected(__('platform::media.rejected.script', ['name' => $originalName]));
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NONET);
        libxml_use_internal_errors($previous);

        if (! $loaded || $dom->documentElement?->localName !== 'svg') {
            throw new MediaRejected(__('platform::media.rejected.mismatch', ['name' => $originalName]));
        }

        $xpath = new DOMXPath($dom);

        foreach (self::REMOVED_ELEMENTS as $name) {
            foreach (iterator_to_array($xpath->query("//*[local-name()='{$name}']")) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach (iterator_to_array($xpath->query('//*')) as $element) {
            /** @var DOMElement $element */
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);
                $value = strtolower(preg_replace('/\s+/', '', $attribute->nodeValue ?? ''));

                if (str_starts_with($name, 'on')
                    || (str_ends_with($name, 'href') && (str_starts_with($value, 'javascript:') || str_starts_with($value, 'data:text')))) {
                    $element->removeAttributeNode($attribute);
                }
            }
        }

        file_put_contents($path, $dom->saveXML($dom->documentElement));
    }
}
