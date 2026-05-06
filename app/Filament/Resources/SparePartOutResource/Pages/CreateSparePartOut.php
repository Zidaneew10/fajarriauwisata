<?php

namespace App\Filament\Resources\SparePartOutResource\Pages;

use App\Filament\Resources\SparePartOutResource;
use App\Models\SparePartOutItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSparePartOut extends CreateRecord
{
    protected static string $resource = SparePartOutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        unset($data['items']);
        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->data['items'] ?? [] as $item) {
            SparePartOutItem::create([
                'spare_part_out_id' => $this->record->id,
                'spare_part_id'     => $item['spare_part_id'],
                'quantity'          => $item['quantity'],
            ]);
        }
    }
}
