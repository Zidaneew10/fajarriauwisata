<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Bus;
use App\Models\Reservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Reservasi';
    protected static ?string $label = 'Reservasi Bus';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Pelanggan')->schema([
                TextInput::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->required(),

                TextInput::make('customer_phone')
                    ->label('No. HP')
                    ->tel()
                    ->required(),

                TextInput::make('customer_email')
                    ->label('Email')
                    ->email()
                    ->nullable(),

                TextInput::make('passenger_count')
                    ->label('Jumlah Penumpang')
                    ->numeric()
                    ->required(),
            ])->columns(2),

            Section::make('Detail Perjalanan')->schema([
                TextInput::make('destination')
                    ->label('Tujuan')
                    ->required()
                    ->columnSpanFull(),

                DatePicker::make('departure_date')
                    ->label('Tanggal Berangkat')
                    ->required(),

                DatePicker::make('return_date')
                    ->label('Tanggal Kembali')
                    ->nullable()
                    ->helperText('Kosongkan jika one way'),

                Textarea::make('notes')
                    ->label('Catatan Tambahan')
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Bus yang Digunakan')->schema([
                Repeater::make('buses')
                    ->label('Daftar Bus')
                    ->schema([
                        Select::make('bus_id')
                            ->label('Bus')
                            ->options(
                                Bus::where('status', 'active')
                                    ->get()
                                    ->mapWithKeys(fn($b) => [
                                        $b->id => "{$b->plate_number} — {$b->class_type} (Kapasitas: {$b->capacity})"
                                    ])
                            )
                            ->searchable()
                            ->required(),

                        TextInput::make('price')
                            ->label('Harga Bus (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Bus')
                    ->default([])
                    ->columnSpanFull(),
            ]),

            Section::make('Pembayaran')->schema([
                Select::make('status')
                    ->label('Status Reservasi')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Dikonfirmasi',
                        'in_progress' => 'Sedang Berjalan',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Dibatalkan',
                    ])
                    ->default('pending')
                    ->required(),

                Select::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'dp'     => 'DP',
                        'paid'   => 'Lunas',
                    ])
                    ->default('unpaid')
                    ->required(),

                TextInput::make('dp_amount')
                    ->label('Jumlah DP (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                TextInput::make('total_price')
                    ->label('Total Harga (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->helperText('Otomatis dihitung dari total harga bus'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reservation_code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

                TextColumn::make('customer_phone')
                    ->label('No. HP'),

                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->limit(30),

                TextColumn::make('departure_date')
                    ->label('Berangkat')
                    ->date('d M Y'),

                TextColumn::make('return_date')
                    ->label('Kembali')
                    ->date('d M Y')
                    ->placeholder('One Way'),

                TextColumn::make('passenger_count')
                    ->label('Penumpang'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'confirmed'   => 'success',
                        'in_progress' => 'info',
                        'completed'   => 'gray',
                        'pending'     => 'warning',
                        'cancelled'   => 'danger',
                    }),

                TextColumn::make('payment_status')
                    ->label('Bayar')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'paid'   => 'success',
                        'dp'     => 'warning',
                        'unpaid' => 'danger',
                    }),

                TextColumn::make('total_price')
                    ->money('IDR')
                    ->label('Total'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'     => 'Pending',
                        'confirmed'   => 'Dikonfirmasi',
                        'in_progress' => 'Sedang Berjalan',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Dibatalkan',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Status Bayar')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'dp'     => 'DP',
                        'paid'   => 'Lunas',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Reservation $record) => $record->status === 'pending')
                    ->action(function (Reservation $record) {
                        $record->update(['status' => 'confirmed']);
                        Notification::make()->title('Reservasi dikonfirmasi')->success()->send();
                    }),
                Actions\Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-flag')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn(Reservation $record) => $record->status === 'in_progress')
                    ->action(function (Reservation $record) {
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Reservasi selesai')->success()->send();
                    }),
                Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Reservation $record) => !in_array($record->status, ['completed', 'cancelled']))
                    ->action(function (Reservation $record) {
                        $record->update(['status' => 'cancelled']);
                        Notification::make()->title('Reservasi dibatalkan')->warning()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit'   => Pages\EditReservation::route('/{record}/edit'),
            'view'   => Pages\ViewReservation::route('/{record}'),
        ];
    }
}
