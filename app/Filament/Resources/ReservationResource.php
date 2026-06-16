<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Bus;
use App\Models\Reservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reservasi';

    protected static ?string $navigationLabel = 'Reservasi Bus';

    protected static ?string $modelLabel = 'Reservasi Bus';

    protected static ?string $pluralModelLabel = 'Reservasi Bus';

    public static function canAccess(): bool
    {
        return static::canManageTickets();
    }

    public static function updateTotalCalculation(callable $get, callable $set, bool $isRepeaterItem = false): void
    {
        $departure = $isRepeaterItem ? $get('../../departure_date') : $get('departure_date');
        $return = $isRepeaterItem ? $get('../../return_date') : $get('return_date');
        
        $duration = 1;
        if ($departure && $return) {
            $duration = \Carbon\Carbon::parse($departure)->startOfDay()->diffInDays(\Carbon\Carbon::parse($return)->startOfDay()) + 1;
        }

        $buses = $isRepeaterItem ? $get('../../buses') : $get('buses');
        $buses = $buses ?? [];

        $totalPrice = collect($buses)->sum(fn ($item) => (float) ($item['price'] ?? 0));
        $finalTotal = $totalPrice * max(1, $duration);

        if ($isRepeaterItem) {
            $set('../../total_price', $finalTotal);
        } else {
            $set('total_price', $finalTotal);
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Wizard::make([

                Step::make('Data Pelanggan')
                    ->schema([

                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required(),

                        TextInput::make('customer_phone')
                            ->label('Nomor HP')
                            ->tel()
                            ->required(),

                        TextInput::make('customer_email')
                            ->label('Email')
                            ->email(),

                        TextInput::make('passenger_count')
                            ->label('Jumlah Penumpang')
                            ->numeric()
                            ->required(),

                    ])
                    ->columns(2),

                Step::make('Perjalanan')
                    ->schema([

                        TextInput::make('destination')
                            ->label('Tujuan')
                            ->required()
                            ->columnSpanFull(),

                        DatePicker::make('departure_date')
                            ->label('Tanggal Berangkat')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                self::updateTotalCalculation($get, $set, false);
                            }),

                        DatePicker::make('return_date')
                            ->label('Tanggal Kembali')
                            ->helperText('Kosongkan jika perjalanan satu arah')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                self::updateTotalCalculation($get, $set, false);
                            }),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->rows(4)
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                Step::make('Bus')
                    ->schema([

                        Repeater::make('buses')
                            ->relationship()
                            ->label('Daftar Bus')
                            ->schema([

                                Select::make('bus_id')
                                    ->label('Armada Bus')
                                    ->options(function (callable $get, \Filament\Forms\Components\Select $component) {
                                        $departureDate = $get('../../departure_date');
                                        if (!$departureDate) return [];

                                        $returnDate = $get('../../return_date') ?: $departureDate;

                                        $livewire = $component->getLivewire();
                                        $reservationId = (method_exists($livewire, 'getRecord') && $livewire->getRecord()) ? $livewire->getRecord()->id : null;

                                        $query = Bus::where('status', 'active')
                                            ->whereDoesntHave('reservationBuses.reservation', function ($q) use ($departureDate, $returnDate, $reservationId) {
                                                $q->whereIn('status', ['confirmed', 'in_progress', 'completed', 'pending']);
                                                
                                                if ($reservationId) {
                                                    $q->where('id', '!=', $reservationId);
                                                }

                                                $q->whereDate('departure_date', '<=', $returnDate)
                                                  ->where(function($q2) use ($departureDate) {
                                                      $q2->whereDate('return_date', '>=', $departureDate)
                                                         ->orWhere(function($q3) use ($departureDate) {
                                                             $q3->whereNull('return_date')
                                                                ->whereDate('departure_date', '>=', $departureDate);
                                                         });
                                                  });
                                            });

                                        return $query->get()->mapWithKeys(fn ($bus) => [
                                            $bus->id => "{$bus->plate_number} - {$bus->class_type} - {$bus->capacity} Kursi"
                                        ]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('price')
                                    ->label('Harga Bus')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        self::updateTotalCalculation($get, $set, true);
                                    }),

                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Bus')
                            ->default([])
                            ->columnSpanFull()
                            ->deleteAction(
                                fn (\Filament\Forms\Components\Actions\Action $action) => $action->after(function (callable $get, callable $set) {
                                    self::updateTotalCalculation($get, $set, false);
                                })
                            ),

                    ]),

                Step::make('Pembayaran')
                    ->schema([

                        Select::make('status')
                            ->label('Status Reservasi')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Dikonfirmasi',
                                'in_progress' => 'Sedang Berjalan',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->required(),

                        Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options([
                                'unpaid' => 'Belum Bayar',
                                'dp' => 'DP',
                                'paid' => 'Lunas',
                            ])
                            ->default('unpaid')
                            ->required(),

                        TextInput::make('dp_amount')
                            ->label('Jumlah DP')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        TextInput::make('total_price')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->helperText('Total harga seluruh bus'),

                    ])
                    ->columns(2),

            ])
            ->columnSpanFull()
            ->skippable(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('reservation_code')
                    ->label('Kode Reservasi')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

                TextColumn::make('customer_phone')
                    ->label('Nomor HP'),

                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->limit(30),

                TextColumn::make('departure_date')
                    ->label('Tanggal Berangkat')
                    ->date('d M Y'),

                TextColumn::make('return_date')
                    ->label('Tanggal Kembali')
                    ->date('d M Y')
                    ->placeholder('Satu Arah'),

                TextColumn::make('passenger_count')
                    ->label('Penumpang'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'in_progress' => 'Sedang Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    })
                    ->color(fn ($state) => match ($state) {
                        'confirmed' => 'success',
                        'in_progress' => 'info',
                        'completed' => 'gray',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                    }),

                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'unpaid' => 'Belum Bayar',
                        'dp' => 'DP',
                        'paid' => 'Lunas',
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'dp' => 'warning',
                        'unpaid' => 'danger',
                    }),

                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR'),

            ])

            ->filters([

                Filter::make('departure_date')
                    ->label('Rentang Tanggal Berangkat')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('departure_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('departure_date', '<=', $data['until']));
                    }),

                SelectFilter::make('bus_id')
                    ->label('Bus')
                    ->options(
                        Bus::orderBy('plate_number')
                            ->pluck('plate_number', 'id')
                    )
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn ($q) => $q->whereHas('buses', fn ($q2) => $q2->where('bus_id', $data['value']))
                    )),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'in_progress' => 'Sedang Berjalan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'dp' => 'DP',
                        'paid' => 'Lunas',
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
                    ->visible(fn (Reservation $record) =>
                        $record->status === 'pending'
                    )
                    ->action(function (Reservation $record) {

                        $record->update([
                            'status' => 'confirmed',
                        ]);

                        Notification::make()
                            ->title('Reservasi berhasil dikonfirmasi')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-flag')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Reservation $record) =>
                        $record->status === 'in_progress'
                    )
                    ->action(function (Reservation $record) {

                        $record->update([
                            'status' => 'completed',
                        ]);

                        Notification::make()
                            ->title('Reservasi selesai')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Reservation $record) =>
                        ! in_array($record->status, ['completed', 'cancelled'])
                    )
                    ->action(function (Reservation $record) {

                        $record->update([
                            'status' => 'cancelled',
                        ]);

                        Notification::make()
                            ->title('Reservasi dibatalkan')
                            ->warning()
                            ->send();
                    }),

            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
            'view' => Pages\ViewReservation::route('/{record}'),
        ];
    }
}
