<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\SparePartRequestResource\Pages;
use App\Models\SparePartRequest;
use App\Models\Bus;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class SparePartRequestResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = SparePartRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $label = 'Permintaan Sparepart';

    protected static ?string $pluralLabel = 'Permintaan Sparepart';

    public static function canAccess(): bool
    {
        return static::canManageSparePartRequests();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika driver, hanya tampilkan request miliknya sendiri
        if (self::isDriver()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Buat Permintaan Sparepart')
                ->description('Laporkan kerusakan atau kebutuhan suku cadang kendaraan Anda di sini.')
                ->schema([

                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => Auth::id())
                        ->required(),

                    Forms\Components\Select::make('bus_info')
                        ->label('Pilih Bus')
                        ->options(Bus::where('status', 'active')->get()->mapWithKeys(function ($bus) {
                            return [$bus->plate_number => "{$bus->plate_number} - {$bus->class_type} ({$bus->bus_code})"];
                        }))
                        ->searchable()
                        ->required()
                        ->placeholder('Ketik atau pilih plat nomor bus'),

                    Forms\Components\Select::make('priority')
                        ->label('Tingkat Urgensi (Prioritas)')
                        ->options([
                            'low' => 'Rendah (Bisa ditunda)',
                            'medium' => 'Sedang (Perlu segera)',
                            'high' => 'Tinggi (Sangat penting)',
                            'urgent' => 'Darurat (Kendaraan tidak bisa jalan)',
                        ])
                        ->default('medium')
                        ->required(),

                    Forms\Components\TextInput::make('part_name')
                        ->label('Nama Suku Cadang (Sparepart)')
                        ->placeholder('Contoh: Kampas Rem Depan, Oli Mesin, dll')
                        ->required(),

                    Forms\Components\Hidden::make('quantity')
                        ->default(1),

                    Forms\Components\Hidden::make('unit')
                        ->default('-'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Deskripsi Kerusakan / Alasan Kebutuhan')
                        ->placeholder('Jelaskan dengan singkat bagian mana yang rusak atau mengapa suku cadang ini dibutuhkan.')
                        ->required()
                        ->columnSpanFull()
                        ->rows(4),

                ])
                ->columns(2)

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->dateTime('d M Y, H:i')
                            ->color('gray')
                            ->size('xs'),

                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn($state) => match($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            }),
                    ]),

                    Tables\Columns\TextColumn::make('part_name')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->size('lg')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('bus_info')
                        ->icon('heroicon-m-truck')
                        ->color('primary')
                        ->searchable(),

                        Tables\Columns\TextColumn::make('priority')
                            ->badge()
                            ->color(fn($state) => match($state) {
                                'low' => 'gray',
                                'medium' => 'info',
                                'high' => 'warning',
                                'urgent' => 'danger',
                            })
                            ->formatStateUsing(fn($state) => strtoupper($state)),

                    Tables\Columns\TextColumn::make('user.name')
                        ->icon('heroicon-m-user')
                        ->size('sm')
                        ->color('gray')
                        ->visible(fn () => !self::isDriver()),

                    Tables\Columns\TextColumn::make('reason')
                        ->limit(50)
                        ->color('gray')
                        ->size('sm')
                        ->extraAttributes(['class' => 'mt-2 italic']),
                ])->space(2),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->status === 'pending' && (self::isDriver() ? $record->user_id === Auth::id() : true)),

                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending' && !self::isDriver())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Permintaan disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending' && !self::isDriver())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Permintaan ditolak')
                            ->danger()
                            ->send();
                    }),

            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSparePartRequests::route('/'),
            'create' => Pages\CreateSparePartRequest::route('/create'),
            'edit' => Pages\EditSparePartRequest::route('/{record}/edit'),
            'view' => Pages\ViewSparePartRequest::route('/{record}'),
        ];
    }
}
