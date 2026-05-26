<?php

namespace App\Filament\Resources\BusTripResource\Pages;

use App\Filament\Resources\BusTripResource;
use App\Models\BusClass;
use App\Models\RouteSegment;
use Filament\Resources\Pages\CreateRecord;

class CreateBusTrip extends CreateRecord
{
    protected static string $resource = BusTripResource::class;

    protected function afterCreate(): void
    {
        foreach ($this->data['routeSegments'] ?? [] as $segment) {
            RouteSegment::create([
                'bus_trip_id' => $this->record->id,
                'stop_id' => $segment['stop_id'],
                'sequence'    => $segment['sequence'],
            ]);
        }

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
