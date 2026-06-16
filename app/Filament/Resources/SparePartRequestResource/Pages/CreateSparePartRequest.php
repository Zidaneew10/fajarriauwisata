<?php

namespace App\Filament\Resources\SparePartRequestResource\Pages;

use App\Filament\Resources\SparePartRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSparePartRequest extends CreateRecord
{
    protected static string $resource = SparePartRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
