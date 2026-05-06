<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use App\Models\ReservationBus;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReservation extends EditRecord
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['buses'] = $this->record->buses
            ->map(fn($b) => [
                'bus_id' => $b->bus_id,
                'price'  => $b->price,
            ])->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->buses()->delete();

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
