<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\BusTrip;
use App\Models\Schedule;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Jadwal';
    protected static ?string $label           = 'Jadwal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('bus_trip_id')
                ->label('Rute Trip')
                ->options(
                    BusTrip::where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn($t) => [
                            $t->id => "{$t->trip_number} — {$t->class_type} ({$t->capacity} kursi — Rp " . number_format($t->price, 0, ',', '.') . ")"
                        ])
                )
                ->searchable()
                ->required()
                ->helperText('Kursi akan otomatis di-generate saat jadwal disimpan'),

            DatePicker::make('departure_date')
                ->label('Tanggal Berangkat')
                ->required()
                ->minDate(now()->toDateString()),

            TimePicker::make('departure_time')
                ->label('Jam Berangkat')
                ->required()
                ->seconds(false),

            Select::make('status')
                ->label('Status')
                ->options([
                    'active'    => 'Aktif',
                    'cancelled' => 'Dibatalkan',
                    'completed' => 'Selesai',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('busTrip.trip_number')
                    ->label('Trip')
                    ->searchable(),

                TextColumn::make('busTrip.class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('departure_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('departure_time')
                    ->label('Jam'),

                TextColumn::make('available_seats')
                    ->label('Kursi Tersedia')
                    ->getStateUsing(fn(Schedule $record) =>
                        $record->seats()->where('is_available', true)->count()
                        . ' / ' . $record->busTrip->capacity
                    ),

                TextColumn::make('busTrip.price')
                    ->label('Harga')
                    ->money('IDR'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'active'    => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active'    => 'Aktif',
                    'cancelled' => 'Dibatalkan',
                    'completed' => 'Selesai',
                ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('lihat_kursi')
                    ->label('Lihat Kursi')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->url(fn(Schedule $record) => ScheduleResource::getUrl('seats', ['record' => $record])),
                DeleteAction::make()
                    ->visible(fn(Schedule $record) =>
                        $record->seats()->where('is_available', false)->count() === 0
                    ),
            ])
            ->defaultSort('departure_date')
            ->defaultSort('departure_time');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit'   => Pages\EditSchedule::route('/{record}/edit'),
            'seats'  => Pages\ViewScheduleSeats::route('/{record}/seats'),
        ];
    }
}
