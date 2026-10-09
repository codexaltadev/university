<?php

namespace Modules\ActivityLog\Services;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV download of the filtered activity log (FR-LOG-05), streamed in chunks. */
class ActivityExport
{
    public function csv(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows Bangla correctly
            fputcsv($out, ['id', 'when', 'user', 'event', 'log', 'module', 'record', 'changes', 'ip']);

            $query->clone()->with('causer')->chunkById(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->created_at?->toDateTimeString(),
                        $row->causer?->name,
                        $row->event,
                        $row->log_name,
                        $row->module_slug,
                        $row->subject_type ? "{$row->subject_type}#{$row->subject_id}" : '',
                        json_encode($row->attribute_changes?->all() ?? [], JSON_UNESCAPED_UNICODE),
                        $row->ip_address,
                    ]);
                }
            });

            fclose($out);
        }, 'activity-log-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
