<?php

namespace App\Filament\Resources\SparePartRequestResource\Pages;

use App\Filament\Resources\SparePartRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSparePartRequest extends EditRecord
{
    protected static string $resource = SparePartRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
