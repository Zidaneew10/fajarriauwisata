<?php

namespace App\Filament\Resources\SparePartInResource\Pages;

use App\Filament\Resources\SparePartInResource;
use App\Models\SparePartInItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSparePartIn extends CreateRecord
{
    protected static string $resource = SparePartInResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        unset($data['items']);
        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->data['items'] ?? [] as $item) {
            SparePartInItem::create([
                'spare_part_in_id' => $this->record->id,
                'spare_part_id'    => $item['spare_part_id'],
                'quantity'         => $item['quantity'],
                'price_per_unit'   => $item['price_per_unit'],
            ]);
        }
    }
}
