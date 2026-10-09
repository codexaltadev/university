<?php

namespace Platform\Relations;

use RuntimeException;

/** A "Show in" / main site / highlight change was refused; $errors are shown in the form. */
class RelationRejected extends RuntimeException
{
    /** @param list<string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}
