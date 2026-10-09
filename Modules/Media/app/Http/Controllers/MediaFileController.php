<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\Request;
use Platform\Media\MediaItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * /media/{id}/{name}: always the item's current file, so links survive "replace
 * file". Public files redirect to storage; private files are streamed only with a
 * valid signed link. Inactive or deleted items are not found.
 */
class MediaFileController
{
    public function __invoke(Request $request, int $item): Response
    {
        $media = MediaItem::query()->where('is_active', true)->findOrFail($item);
        $file = $media->file() ?? abort(404);

        if (! $media->is_private) {
            return redirect()->away($file->getUrl());
        }

        abort_unless($request->hasValidSignature(), 403);

        return response()->file($file->getPath(), [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($file->file_name).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
