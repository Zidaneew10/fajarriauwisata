<?php

namespace App\Filament\Resources\BusTripResource\Pages;

use App\Filament\Resources\BusTripResource;
use App\Models\BusClass;
use App\Models\RouteSegment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusTrip extends EditRecord
{
    protected static string $resource = BusTripResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['routeSegments'] = $this->record->routeSegments
            ->map(fn($s) => ['sequence' => $s->sequence, 'terminal_id' => $s->terminal_id])
            ->toArray();

        $data['busClasses'] = $this->record->busClasses
            ->map(fn($c) => [
                'class_type'   => $c->class_type,
                'price'        => $c->price,
                'capacity'     => $c->capacity,
                'facility_ids' => $c->facilities->pluck('id')->toArray(),
            ])->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->routeSegments()->delete();
        foreach ($this->data['routeSegments'] ?? [] as $segment) {
            RouteSegment::create([
                'bus_trip_id' => $this->record->id,
                'terminal_id' => $segment['terminal_id'],
                'sequence'    => $segment['sequence'],
            ]);
        }

        $this->record->busClasses()->each(fn($c) => $c->facilities()->detach());
        $this->record->busClasses()->delete();
        foreach ($this->data['busClasses'] ?? [] as $class) {
            $busClass = BusClass::create([
                'bus_trip_id' => $this->record->id,
                'class_type'  => $class['class_type'],
                'price'       => $class['price'],
                'capacity'    => $class['capacity'],
            ]);

            if (!empty($class['facility_ids'])) {
                $busClass->facilities()->sync($class['facility_ids']);
            }
        }
    }
}
