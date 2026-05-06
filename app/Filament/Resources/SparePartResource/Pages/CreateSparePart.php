<?php

namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSparePart extends CreateRecord
{
    protected static string $resource = SparePartResource::class;

    protected function afterCreate(): void
    {
        // Hitung ROP otomatis setelah create
        $this->record->rop = $this->record->calculateRop();
        $this->record->save();
    }
}
