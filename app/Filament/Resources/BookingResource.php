<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Booking';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('booking_code')->disabled(),
            Select::make('status')->options([
                'pending'   => 'Pending',
                'confirmed' => 'Confirmed',
                'paid'      => 'Paid',
                'cancelled' => 'Cancelled',
            ])->required(),
            TextInput::make('total_price')->numeric()->prefix('Rp')->disabled(),
            TextInput::make('discount_amount')->numeric()->prefix('Rp')->disabled(),
            DateTimePicker::make('expired_at')->label('Expired'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('booking_code')->searchable()->copyable(),
            TextColumn::make('status')->badge()->color(fn($state) => match($state) {
                'paid'      => 'success',
                'confirmed' => 'info',
                'pending'   => 'warning',
                'cancelled' => 'danger',
            }),
            TextColumn::make('scheduleBus.busClass.class_type')->label('Kelas'),
            TextColumn::make('scheduleBus.bus_code')->label('Bus'),
            TextColumn::make('scheduleBus.schedule.departure_time')->label('Jadwal')->dateTime('d M Y H:i'),
            TextColumn::make('passengers_count')->counts('passengers')->label('Penumpang'),
            TextColumn::make('total_price')->money('IDR')->label('Total'),
            TextColumn::make('discount_amount')->money('IDR')->label('Diskon'),
            TextColumn::make('promoCode.code')->label('Promo')->placeholder('-'),
            TextColumn::make('expired_at')->dateTime('d M Y H:i')->label('Expired'),
            TextColumn::make('midtrans_token')->label('Token')->limit(20)
                ->copyable()->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('status')->options([
                'pending'   => 'Pending',
                'confirmed' => 'Confirmed',
                'paid'      => 'Paid',
                'cancelled' => 'Cancelled',
            ]),
        ])->actions([
            Actions\ViewAction::make(),
            Actions\Action::make('generate_token')
                ->label('Generate Token')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn(Booking $record) => $record->status === 'pending' && !$record->isExpired())
                ->action(function (Booking $record) {
                    try {
                        app(PaymentService::class)->createSnapToken($record);
                        Notification::make()->title('Token berhasil digenerate')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('confirm_payment')
                ->label('Konfirmasi Bayar')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn(Booking $record) => $record->status === 'pending')
                ->action(function (Booking $record) {
                    $record->update(['status' => 'paid']);
                    Notification::make()->title('Pembayaran dikonfirmasi')->success()->send();
                }),
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn(Booking $record) => !in_array($record->status, ['cancelled', 'paid']))
                ->action(function (Booking $record) {
                    app(BookingService::class)->cancel($record);
                    Notification::make()->title('Booking dibatalkan')->success()->send();
                }),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view'  => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
