<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Bus;
use App\Models\BusClass;
use App\Models\BusTrip;
use App\Models\Schedule;
use App\Models\ScheduleBus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                        Select::make('bus_id')
                            ->label('Pilih Bus')
                            ->options(
                                Bus::where('status', 'active')
                                    ->get()
                                    ->mapWithKeys(fn($b) => [
                                        $b->id => "{$b->plate_number} — {$b->class_type} (Kapasitas: {$b->capacity})"
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Schedule $record, array $data) {
                        $bus = Bus::find($data['bus_id']);
                        ScheduleBus::create([
                            'schedule_id'  => $record->id,
                            'bus_id'       => $bus->id,
                            'bus_class_id' => $record->busTrip->busClasses()
                                ->where('class_type', $bus->class_type)
                                ->first()?->id,
                        ]);
                        Notification::make()->title('Bus berhasil ditugaskan!')->success()->send();
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
