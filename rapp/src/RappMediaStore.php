<?php
declare(strict_types=1);

/**
 * Filesystem storage for RAPP audio, kept OUTSIDE the web root. Layout:
 *
 *   <baseDir>/<year>/<reportId>/audio-<reportId>.<ext>
 *
 * The path relative to <baseDir> is what gets written to rapp_media.filename;
 * resolve() turns it back into an absolute path and refuses anything that
 * escapes <baseDir>.
 */
final class RappMediaStore
{
    public function __construct(private string $baseDir) {}

    /**
     * Move an uploaded temp file into place. Returns the path relative to the
     * base directory (store this in rapp_media.filename).
     *
     * @throws RuntimeException if the directory or file can't be written
     */
    public function store(int $reportId, string $tmpPath, string $ext): string
    {
        $relDir = date('Y') . '/' . $reportId;
        $absDir = $this->baseDir . '/' . $relDir;

        if (!is_dir($absDir) && !mkdir($absDir, 0750, true) && !is_dir($absDir)) {
            throw new RuntimeException('Could not create the RAPP media directory.');
        }

        $name = 'audio-' . $reportId . '.' . $ext;
        $abs = $absDir . '/' . $name;

        if (!@move_uploaded_file($tmpPath, $abs) && !@rename($tmpPath, $abs)) {
            throw new RuntimeException('Could not save the audio file.');
        }
        @chmod($abs, 0640);

        return $relDir . '/' . $name;
    }

    /**
     * Absolute path for a stored relative path, or null if it would escape the
     * base directory or does not exist.
     */
    public function resolve(string $relativePath): ?string
    {
        $abs = $this->baseDir . '/' . ltrim($relativePath, '/');
        $real = realpath($abs);
        $realBase = realpath($this->baseDir);

        if ($real === false || $realBase === false) {
            return null;
        }
        if ($real !== $realBase && !str_starts_with($real, $realBase . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $real;
    }

    public function delete(string $relativePath): void
    {
        $abs = $this->resolve($relativePath);
        if ($abs !== null && is_file($abs)) {
            @unlink($abs);
        }
    }
}
