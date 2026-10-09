<?php

namespace Platform\Media;

use finfo;
use Symfony\Component\Mime\MimeTypes;

/**
 * Upload checks (FR-MEDIA-13, AC-MEDIA-03): the extension must be allowed
 * (Settings › Media) and never a script; the real content type must match the
 * extension; no PHP code anywhere in the file (a ".jpg" with PHP inside is refused).
 */
class FileGuard
{
    /** Never accepted, whatever the settings say. */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'inc',
        'exe', 'dll', 'msi', 'com', 'bat', 'cmd', 'sh', 'bash', 'ps1', 'vbs', 'jar', 'cgi', 'pl', 'py', 'rb',
        'js', 'mjs', 'html', 'htm', 'shtml', 'xhtml', 'asp', 'aspx', 'jsp', 'htaccess', 'svgz',
    ];

    /** Content types that are never a document or a picture. */
    public const BLOCKED_MIMES = [
        'text/x-php', 'application/x-php', 'application/x-httpd-php', 'text/html', 'application/x-executable',
        'application/x-dosexec', 'application/x-msdownload', 'text/x-shellscript', 'application/x-sh', 'text/javascript',
        'application/javascript',
    ];

    /** Real types fileinfo reports for valid files beyond the standard list. */
    protected const ALSO_ACCEPTED = [
        'docx' => ['application/zip', 'application/octet-stream'],
        'xlsx' => ['application/zip', 'application/octet-stream'],
        'pptx' => ['application/zip', 'application/octet-stream'],
        'doc' => ['application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
        'xls' => ['application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
        'ppt' => ['application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
        'svg' => ['image/svg', 'text/xml', 'application/xml', 'text/plain'],
        'csv' => ['text/plain', 'application/csv'],
        'txt' => ['text/plain'],
        'mp3' => ['audio/mpeg', 'application/octet-stream'],
    ];

    /**
     * @param  list<string>  $allowed  extensions from Settings › Media
     * @return string the real MIME type
     *
     * @throws MediaRejected
     */
    public function check(string $path, string $originalName, array $allowed): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = array_map(fn (string $e): string => strtolower(ltrim(trim($e), '.')), $allowed);

        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true) || ! in_array($extension, $allowed, true)) {
            throw new MediaRejected(__('platform::media.rejected.type', ['name' => $originalName]));
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (in_array($mime, self::BLOCKED_MIMES, true) || $this->containsPhp($path)) {
            throw new MediaRejected(__('platform::media.rejected.script', ['name' => $originalName]));
        }

        $expected = [...MimeTypes::getDefault()->getMimeTypes($extension), ...(self::ALSO_ACCEPTED[$extension] ?? [])];

        if (! in_array($mime, $expected, true)) {
            throw new MediaRejected(__('platform::media.rejected.mismatch', ['name' => $originalName]));
        }

        return $extension === 'svg' ? 'image/svg+xml' : $mime;
    }

    protected function containsPhp(string $path): bool
    {
        $handle = fopen($path, 'rb');
        $found = false;
        $tail = '';

        while (! $found && ! feof($handle)) {
            $chunk = $tail.fread($handle, 1024 * 1024);
            $found = stripos($chunk, '<?php') !== false || preg_match('/<\?=\s*\$/', $chunk) === 1;
            $tail = substr($chunk, -8);
        }

        fclose($handle);

        return $found;
    }
}
