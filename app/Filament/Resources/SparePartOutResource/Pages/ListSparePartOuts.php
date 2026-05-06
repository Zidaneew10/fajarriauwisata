<?php

namespace App\Filament\Resources\SparePartOutResource\Pages;

use App\Filament\Resources\SparePartOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSparePartOuts extends ListRecords
{
    protected static string $resource = SparePartOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
