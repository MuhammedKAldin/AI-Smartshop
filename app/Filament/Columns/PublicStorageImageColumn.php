<?php

declare(strict_types=1);

namespace App\Filament\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Storage;

class PublicStorageImageColumn extends ImageColumn
{
    public function getImageUrl(?string $state = null): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $path = ltrim($state, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return Storage::disk('public')->url($path);
    }
}


