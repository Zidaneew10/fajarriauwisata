<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Models\ReservationBus;
use Filament\Resources\Pages\CreateRecord;

class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

protected function afterCreate(): void
{
    foreach ($this->data['buses'] ?? [] as $bus) {
        ReservationBus::create([
            'reservation_id' => $this->record->id,
            'bus_id'         => $bus['bus_id'],
            'price'          => $bus['price'],
        ]);
    }

    $this->record->refresh();
    $this->record->recalculateTotal();
}
}
