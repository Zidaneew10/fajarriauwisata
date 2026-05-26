<?php

namespace App\Filament\Resources\BusTripResource\Pages;

use App\Filament\Resources\BusTripResource;
use App\Models\RouteSegment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusTrip extends EditRecord
{
    protected static string $resource = BusTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['routeSegments'] = $this->record->routeSegments
            ->map(fn($s) => [
                'sequence'    => $s->sequence,
                'stop_id'     => $s->stop_id,  // ← stop_id bukan terminal_id
                'time_offset' => $s->time_offset,
            ])->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->routeSegments()->delete();

        foreach ($this->data['routeSegments'] ?? [] as $segment) {
            RouteSegment::create([
                'bus_trip_id' => $this->record->id,
                'stop_id'     => $segment['stop_id'],
                'sequence'    => $segment['sequence'],
                'time_offset' => $segment['time_offset'] ?? null,
            ]);
        }
    }
}
