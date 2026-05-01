<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\BusClass;
use App\Models\BusTrip;
use App\Models\Schedule;
use App\Models\ScheduleBus;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Jadwal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('bus_trip_id')
                ->label('Bus Trip')
                ->options(BusTrip::pluck('trip_number', 'id'))
                ->required(),
            DateTimePicker::make('departure_time')
                ->label('Tanggal & Jam Berangkat')
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
                TextColumn::make('departure_time')
                    ->label('Waktu Berangkat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('scheduleBuses_count')
                    ->counts('scheduleBuses')
                    ->label('Bus Ditugaskan'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),

                Actions\Action::make('assign_bus')
                    ->label('Assign Bus')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form(fn(Schedule $record) => [
                        Select::make('bus_class_id')
                            ->label('Kelas Bus')
                            ->options(
                                BusClass::where('bus_trip_id', $record->bus_trip_id)
                                    ->get()
                                    ->mapWithKeys(fn($c) => [
                                        $c->id => $c->class_type . ' — Rp' . number_format($c->price, 0, ',', '.')
                                    ])
                            )
                            ->required(),
                        TextInput::make('bus_code')
                            ->label('Nomor Polisi Bus')
                            ->placeholder('Contoh: BM 1234 AB')
                            ->required(),
                    ])
                    ->action(function (Schedule $record, array $data) {
                        ScheduleBus::create([
                            'schedule_id'  => $record->id,
                            'bus_class_id' => $data['bus_class_id'],
                            'bus_code'     => $data['bus_code'],
                        ]);
                        Notification::make()
                            ->title('Bus berhasil ditugaskan!')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('lihat_bus')
                    ->label('Lihat Bus')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(fn(Schedule $record) => view(
                        'filament.modals.schedule-buses',
                        ['scheduleBuses' => $record->scheduleBuses()->with('busClass')->get()]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->defaultSort('departure_time', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit'   => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
