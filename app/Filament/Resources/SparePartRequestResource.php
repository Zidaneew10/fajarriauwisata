<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartRequestResource\Pages;
use App\Filament\Traits\HasRoleAccess;
use App\Models\SparePartRequest;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class SparePartRequestResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = SparePartRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $label = 'Permintaan Spare Part';
    protected static ?int $navigationSort = 4;

    /* -------------------------
        ACCESS CONTROL
    ------------------------- */
    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole([
            'administrator',
            'manager',
            'montir',
            'driver',
        ]) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->hasAnyRole([
            'administrator',
            'manager',
            'montir',
        ]) ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->hasRole('administrator') ?? false;
    }

    /* -------------------------
        FORM
    ------------------------- */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Ajukan Permintaan Spare Part')
                ->schema([
                    TextInput::make('part_name')
                        ->label('Nama Spare Part')
                        ->required()
                        ->maxLength(200),

                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(1),

                    TextInput::make('unit')
                        ->label('Satuan')
                        ->required()
                        ->default('pcs'),

                    TextInput::make('bus_info')
                        ->label('Nomor Bus (opsional)')
                        ->nullable(),

                    Textarea::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    /* -------------------------
        TABLE
    ------------------------- */
    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->searchable()
                    ->visible(fn() => !Auth::user()?->hasRole('driver')),

                TextColumn::make('part_name')
                    ->label('Nama Part')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->suffix(fn($record) => ' ' . $record->unit),

                TextColumn::make('bus_info')
                    ->label('Bus')
                    ->default('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($record) => match ($record->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn($record) => match ($record->status) {
                        'pending' => '⏳ Menunggu',
                        'approved' => '✓ Disetujui',
                        'rejected' => '✗ Ditolak',
                        default => $record->status,
                    }),

                TextColumn::make('reviewer.name')
                    ->label('Ditinjau Oleh')
                    ->default('-')
                    ->visible(fn() => !Auth::user()?->hasRole('driver')),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn(SparePartRequest $record) =>
                        $record->status === 'pending'
                            && !Auth::user()?->hasRole('driver')
                    )
                    ->action(function (SparePartRequest $record, array $data = []) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(
                        fn(SparePartRequest $record) =>
                        $record->status === 'pending'
                            && !Auth::user()?->hasRole('driver')
                    )
                    ->action(function (SparePartRequest $record, array $data = []) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Ditolak')
                            ->danger()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /* -------------------------
        INFOLIST
    ------------------------- */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Detail')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('driver.name'),
                    TextEntry::make('part_name'),
                    TextEntry::make('quantity')
                        ->suffix(fn($record) => ' ' . $record->unit),
                    TextEntry::make('status')->badge(),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSparePartRequests::route('/'),
            'create' => Pages\CreateSparePartRequest::route('/create'),
            'view' => Pages\ViewSparePartRequest::route('/{record}'),
        ];
    }
}
