<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Cache-busting asset URL: appends the file's modification time as a version query.
 */
if (! function_exists('auto_version')) {
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
if (! function_exists('loadFiles')) {
    function loadFiles(string $file_path): string
    {
        return auto_version(asset($file_path));
    }
}

if (! function_exists('salutationEnum')) {
    function salutationEnum(): array
    {
        return ['Mr', 'Ms', 'Mrs', 'Miss', 'Sir', 'Dr', 'Not Set'];
    }
}

/**
 * Log an exception/message with its stack trace for easier debugging.
 */
if (! function_exists('fullLog')) {
    function fullLog(string|Throwable $message): void
    {
        if ($message instanceof Throwable) {
            Log::error($message->getMessage(), ['exception' => $message]);

            return;
        }

        Log::error($message);
    }
}

/**
 * Store an uploaded file on the public disk and return its generated filename.
 */
if (! function_exists('saveFileToStorage')) {
    function saveFileToStorage(UploadedFile $file, string $directory): string
    {
        return basename($file->store($directory, 'public'));
    }
}

/**
 * Delete a stored file from the public disk. External URLs (e.g. an OAuth avatar)
 * are ignored since they are not owned by the application.
 */
if (! function_exists('deleteFile')) {
    function deleteFile(?string $filename, string $directory): void
    {
        if (! $filename || str_starts_with($filename, 'http')) {
            return;
        }

        Storage::disk('public')->delete($directory.'/'.$filename);
    }
}

/**
 * The admin panel's CRUD route block, as a callback for Route::group().
 *
 *     Route::group(
 *         ['prefix' => 'learning-mode', 'as' => 'learning-mode.'],
 *         resourceRoutesCallback(LearningModeController::class, 'learning_mode'),
 *     );
 *
 * Deliberately not Route::resource(): every admin resource here uses the same
 * six actions with an explicit route-model-binding parameter name, and one
 * helper keeps that shape identical across resources (DRY) without inheriting
 * `show`, `.store`-on-collection naming, or the API-only variants we never use.
 *
 * @param  class-string  $controller
 * @param  array<int, string>  $except  action names to skip, e.g. ['destroy']
 */
if (! function_exists('resourceRoutesCallback')) {
    function resourceRoutesCallback(string $controller, string $parameter, array $except = []): Closure
    {
        return function () use ($controller, $parameter, $except) {
            $routes = [
                'index' => fn () => Route::get('/', [$controller, 'index'])->name('index'),
                'create' => fn () => Route::get('create', [$controller, 'create'])->name('create'),
                'store' => fn () => Route::post('/', [$controller, 'store'])->name('store'),
                'edit' => fn () => Route::get('{'.$parameter.'}/edit', [$controller, 'edit'])->name('edit'),
                'update' => fn () => Route::put('{'.$parameter.'}', [$controller, 'update'])->name('update'),
                'destroy' => fn () => Route::delete('{'.$parameter.'}', [$controller, 'destroy'])->name('destroy'),
            ];

            foreach ($routes as $action => $register) {
                if (! in_array($action, $except, true)) {
                    $register();
                }
            }
        };
    }
}
