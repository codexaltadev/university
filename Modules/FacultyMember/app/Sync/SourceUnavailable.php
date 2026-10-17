<?php

namespace Modules\FacultyMember\Sync;

use RuntimeException;

/** The source could not be read, or gave nothing usable: the sync stops and removes no one (BRL-FM-05). */
class SourceUnavailable extends RuntimeException {}
