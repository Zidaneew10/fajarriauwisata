<?php

namespace App\Filament\Resources;

use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Jadwal';

    protected static ?string $navigationLabel = 'Jadwal Keberangkatan';

    protected static ?string $modelLabel = 'Jadwal';

    protected static ?string $pluralModelLabel = 'Jadwal Keberangkatan';

    public static function canAccess(): bool
    {
        return static::canManageTickets();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Jadwal')->schema([
                Select::make('bus_trip_id')
                    ->label('Rute Trip')
                    ->relationship('busTrip', 'trip_number')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('departure_date')
                    ->label('Tanggal Keberangkatan')
                    ->required(),

                TimePicker::make('departure_time')
                    ->label('Waktu Keberangkatan')
                    ->required(),

                TimePicker::make('arrival_time')
                    ->label('Waktu Kedatangan (Estimasi)')
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        Schedule::STATUS_ACTIVE    => 'Aktif',
                        Schedule::STATUS_CANCELLED => 'Dibatalkan',
                        Schedule::STATUS_COMPLETED => 'Selesai',
                    ])
                    ->default(Schedule::STATUS_ACTIVE)
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('busTrip.trip_number')
                    ->label('Rute Trip')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('departure_date')
                    ->label('Tanggal Keberangkatan')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('departure_time')
                    ->label('Waktu Keberangkatan')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('arrival_time')
                    ->label('Waktu Kedatangan')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Schedule::STATUS_ACTIVE => 'success',
                        Schedule::STATUS_COMPLETED => 'gray',
                        Schedule::STATUS_CANCELLED => 'danger',
                        default => 'primary',
                    }),
            ])
            ->filters([
                SelectFilter::make('bus_trip_id')
                    ->label('Rute Trip')
                    ->relationship('busTrip', 'trip_number')
                    ->searchable()
                    ->preload(),
                Filter::make('departure_date')
                    ->form([
                        DatePicker::make('departure_date_from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('departure_date_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['departure_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('departure_date', '>=', $date),
                            )
                            ->when(
                                $data['departure_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('departure_date', '<=', $date),
                            );
                    }),
            ]);

    }

    public static function getPages(): array
    {
        return [
            'index'      => Pages\ListSchedules::route('/'),
            'create'     => Pages\CreateSchedule::route('/create'),
            'edit'       => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
