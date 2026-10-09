<?php

namespace Platform\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ParentHasActiveChildren extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $children  relation => record titles
     */
    public function __construct(public readonly Model $parent, public readonly array $children)
    {
        parent::__construct(sprintf(
            'Cannot delete %s #%s: it still has active %s.',
            class_basename($parent),
            $parent->getKey(),
            implode(', ', array_keys($children)),
        ));
    }
}
