<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Models\Schedule;
use Filament\Resources\Pages\Page;

class ViewScheduleSeats extends Page
{
    protected static string $resource = ScheduleResource::class;
    protected static string $view     = 'filament.pages.schedule-seats';

    public Schedule $record;

    public function mount(Schedule $record): void
    {
        $this->record = $record->load(['busTrip', 'seats' => fn($q) => $q->orderBy('row')->orderBy('column')]);
    }

    public function getTitle(): string
    {
        return "Kursi — {$this->record->busTrip->trip_number} {$this->record->departure_date->format('d M Y')}";
    }
}
