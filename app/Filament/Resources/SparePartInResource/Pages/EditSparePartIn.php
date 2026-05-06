<?php

namespace App\Filament\Resources\SparePartInResource\Pages;

use App\Filament\Resources\SparePartInResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSparePartIn extends EditRecord
{
    protected static string $resource = SparePartInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
