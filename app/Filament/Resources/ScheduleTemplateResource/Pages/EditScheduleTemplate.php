<?php

namespace App\Filament\Resources\ScheduleTemplateResource\Pages;

use App\Filament\Resources\ScheduleTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheduleTemplate extends EditRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
