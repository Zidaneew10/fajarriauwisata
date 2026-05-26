<?php
namespace App\Filament\Resources\SparePartRequestResource\Pages;

use App\Filament\Resources\SparePartRequestResource;
use App\Filament\Traits\HasRoleAccess;
use App\Models\SparePartRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSparePartRequest extends ViewRecord
{
    use HasRoleAccess;

    protected static string $resource = SparePartRequestResource::class;

    protected function getHeaderActions(): array
    {
        // Driver tidak punya tombol approve/reject
        if (static::isDriver()) return [];

        return [
            Action::make('approve')
                ->label('✓ Setujui')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Permintaan')
                ->form([
                    Textarea::make('admin_notes')
                        ->label('Catatan (opsional)')
                        ->rows(3)->nullable(),
                ])
                ->visible(fn() => $this->record->isPending())
                ->action(function (array $data) {
                    $this->record->update([
                        'status'      => 'approved',
                        'admin_notes' => $data['admin_notes'] ?? null,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                    Notification::make()->title('Disetujui')->success()->send();
                    $this->refreshFormData([
                        'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
                    ]);
                }),

            Action::make('reject')
                ->label('✗ Tolak')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Permintaan')
                ->form([
                    Textarea::make('admin_notes')
                        ->label('Alasan Penolakan')
                        ->required()->rows(3),
                ])
                ->visible(fn() => $this->record->isPending())
                ->action(function (array $data) {
                    $this->record->update([
                        'status'      => 'rejected',
                        'admin_notes' => $data['admin_notes'],
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                    Notification::make()->title('Ditolak')->warning()->send();
                    $this->refreshFormData([
                        'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
                    ]);
                }),
        ];
    }
}
