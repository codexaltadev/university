<?php

namespace Platform\Localization;

use RuntimeException;

/** A change that would break BRL-LANG-01 (one default language, always active). */
class LanguageRuleViolation extends RuntimeException {}
