<?php
namespace App\Filament\Resources\SparePartRequestResource\Pages;

use App\Filament\Resources\SparePartRequestResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSparePartRequest extends CreateRecord
{
    protected static string $resource = SparePartRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status']  = 'pending';
        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('✅ Permintaan Terkirim')
            ->body('Admin akan segera meninjau permintaan ini.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
