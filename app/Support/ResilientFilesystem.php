<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

class ResilientFilesystem extends Filesystem
{
    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * On Windows a rename onto a file that another PHP worker or a virus scanner
     * currently holds open fails with "Access is denied", so the rename is retried
     * and, when another worker has already written the file, its copy is kept.
     *
     * @param  string  $path
     * @param  string  $content
     * @param  int|null  $mode
     */
    public function replace($path, $content, $mode = null): void
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        @chmod($tempPath, $mode ?? 0777 - umask());

        file_put_contents($tempPath, $content);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            if (@rename($tempPath, $path)) {
                return;
            }

            usleep($attempt * 10_000);
        }

        clearstatcache(true, $path);

        if (is_file($path)) {
            @unlink($tempPath);

            return;
        }

        rename($tempPath, $path);
    }
}
