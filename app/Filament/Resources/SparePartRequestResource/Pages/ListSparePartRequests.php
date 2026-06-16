<?php
namespace App\Filament\Resources\SparePartRequestResource\Pages;

use App\Filament\Resources\SparePartRequestResource;
use App\Filament\Traits\HasRoleAccess;
use App\Models\SparePartRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListSparePartRequests extends ListRecords
{
    use HasRoleAccess;

    protected static string $resource = SparePartRequestResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->when(
                static::isDriver(),
                fn(Builder $q) => $q->where('user_id', Auth::id())
            )
            ->latest();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(
                    static::isDriver()
                        ? '+ Ajukan Permintaan'
                        : '+ Tambah Permintaan'
                ),
        ];
    }

    public function getTabs(): array
    {
        $userId   = Auth::id();
        $isDriver = static::isDriver();

        return [
            'all' => Tab::make('Semua')
                ->badge(
                    $isDriver
                        ? SparePartRequest::where('user_id', $userId)->count()
                        : SparePartRequest::count()
                ),

            'pending' => Tab::make('Menunggu')
                ->modifyQueryUsing(fn(Builder $q) =>
                    $q->where('status', 'pending')
                )
                ->badge(
                    $isDriver
                        ? SparePartRequest::where('user_id', $userId)->where('status', 'pending')->count()
                        : SparePartRequest::where('status', 'pending')->count()
                )
                ->badgeColor('warning'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $q) =>
                    $q->where('status', 'approved')
                )
                ->badge(
                    $isDriver
                        ? SparePartRequest::where('user_id', $userId)->where('status', 'approved')->count()
                        : SparePartRequest::where('status', 'approved')->count()
                )
                ->badgeColor('success'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $q) =>
                    $q->where('status', 'rejected')
                )
                ->badge(
                    $isDriver
                        ? SparePartRequest::where('user_id', $userId)->where('status', 'rejected')->count()
                        : SparePartRequest::where('status', 'rejected')->count()
                )
                ->badgeColor('danger'),
        ];
    }
}
