<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cache-busting asset URL: appends the file's modification time as a version query.
 */
if (!function_exists('auto_version')) {
    function auto_version(string $file): string
    {
        $path = public_path(str_replace(asset(''), '', $file));
        $mtime = file_exists($path) ? filemtime($path) : time();

        return sprintf('%s?v=%d', $file, $mtime);
    }
}

/**
 * Versioned URL for a public asset (relative path under /public).
 */
if (!function_exists('loadFiles')) {
    function loadFiles(string $file_path): string
    {
        return auto_version(asset($file_path));
    }
}

if (!function_exists('salutationEnum')) {
    function salutationEnum(): array
    {
        return ['Mr', 'Ms', 'Mrs', 'Miss', 'Sir', 'Dr', 'Not Set'];
    }
}

/**
 * Log an exception/message with its stack trace for easier debugging.
 */
if (!function_exists('fullLog')) {
    function fullLog(string|\Throwable $message): void
    {
        if ($message instanceof \Throwable) {
            Log::error($message->getMessage(), ['exception' => $message]);

            return;
        }

        Log::error($message);
    }
}

/**
 * Store an uploaded file on the public disk and return its generated filename.
 */
if (!function_exists('saveFileToStorage')) {
    function saveFileToStorage(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        return basename($file->store($directory, 'public'));
    }
}

/**
 * Delete a stored file from the public disk. External URLs (e.g. an OAuth avatar)
 * are ignored since they are not owned by the application.
 */
if (!function_exists('deleteFile')) {
    function deleteFile(?string $filename, string $directory): void
    {
        if (!$filename || str_starts_with($filename, 'http')) {
            return;
        }

        Storage::disk('public')->delete($directory.'/'.$filename);
    }
}