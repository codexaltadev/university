<?php

namespace Platform\Media;

use RuntimeException;

/** An upload refused by FileGuard (FR-MEDIA-13); the message is shown to the user. */
class MediaRejected extends RuntimeException {}
