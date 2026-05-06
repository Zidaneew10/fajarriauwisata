<?php

namespace App\Filament\Resources\SparePartOutResource\Pages;

use App\Filament\Resources\SparePartOutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSparePartOut extends EditRecord
{
    protected static string $resource = SparePartOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
