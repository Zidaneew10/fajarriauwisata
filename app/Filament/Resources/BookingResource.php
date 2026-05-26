<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;

class BookingResource extends Resource
{
    protected static ?string $model          = Booking::class;
    protected static ?string $navigationIcon  = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Tiket';
    protected static ?string $label           = 'Booking';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('booking_code')->label('Kode')->searchable()->copyable(),
            TextColumn::make('user.name')->label('Pemesan')->searchable(),
            TextColumn::make('schedule.busTrip.trip_number')->label('Trip'),
            TextColumn::make('schedule.departure_date')->label('Tanggal')->date('d M Y'),
            TextColumn::make('schedule.departure_time')->label('Jam'),
            TextColumn::make('boardingStop.city')->label('Naik di'),
            TextColumn::make('dropStop.city')->label('Turun di'),
            TextColumn::make('passengers_count')->counts('passengers')->label('Penumpang'),
            TextColumn::make('total_price')->money('IDR')->label('Total'),
            TextColumn::make('status')
                ->badge()
                ->color(fn($state) => match($state) {
                    'paid', 'confirmed' => 'success',
                    'pending'           => 'warning',
                    'cancelled'         => 'danger',
                }),
        ])
        ->filters([
            SelectFilter::make('status')->options([
                'pending'   => 'Pending',
                'paid'      => 'Lunas',
                'confirmed' => 'Dikonfirmasi',
                'cancelled' => 'Dibatalkan',
            ]),
        ])
        ->actions([
            ViewAction::make(),
            Action::make('confirm')
                ->label('Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(Booking $r) => $r->status === 'paid')
                ->action(function (Booking $record) {
                    $record->update(['status' => 'confirmed']);
                    Notification::make()->title('Booking dikonfirmasi')->success()->send();
                }),
            Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(Booking $r) => in_array($r->status, ['pending', 'paid']))
                ->action(function (Booking $record) {
                    app(BookingService::class)->cancel($record);
                    Notification::make()->title('Booking dibatalkan')->warning()->send();
                }),
        ])
        ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view'  => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
