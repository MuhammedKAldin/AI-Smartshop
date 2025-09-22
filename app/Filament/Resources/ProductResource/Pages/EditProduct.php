<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => static::getResource()::getUrl('index'))
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['image'])) {
            $path = ltrim((string) $data['image'], '/');

            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, strlen('storage/'));
            }

            $data['image'] = $path;
        }

        return $data;
    }
}
