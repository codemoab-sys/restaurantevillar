<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicAssetController extends Controller
{
    public function show(string $path)
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '../') || str_contains($path, '..\\')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path));
    }
}