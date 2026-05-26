<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function afterCreate(): void
    {
        $count = $this->record->seats()->count();
        Notification::make()
            ->title("✅ Jadwal dibuat! {$count} kursi berhasil di-generate otomatis.")
            ->success()
            ->send();
    }
}
