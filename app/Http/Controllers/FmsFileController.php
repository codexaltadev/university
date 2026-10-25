<?php

namespace App\Http\Controllers;

use App\Services\FmsApiClient;
use Illuminate\Http\Response;

/**
 * A teacher's CV and vCard from the FMS API, passed through this site so the token stays on the
 * server (/fms/{faculty}/{department}/{teacher}/cv, …/vcard). Not cached: files; the route is throttled.
 */
class FmsFileController extends Controller
{
    public function __invoke(FmsApiClient $fms, string $faculty, string $department, string $teacher, string $file): Response
    {
        $response = $file === 'cv' ? $fms->cv($faculty, $department, $teacher) : $fms->vcard($faculty, $department, $teacher);
        abort_if($response === null, 404);

        $type = $response->header('Content-Type') ?: ($file === 'cv' ? 'application/pdf' : 'text/vcard');
        $name = $teacher.'-'.$file.($file === 'cv' ? '.pdf' : '.vcf');

        return response($response->body(), 200, [
            'Content-Type' => $type,
            'Content-Disposition' => $response->header('Content-Disposition') ?: ($file === 'cv' ? 'inline' : 'attachment').'; filename="'.$name.'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
