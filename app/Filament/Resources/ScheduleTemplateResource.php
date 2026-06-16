<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\ScheduleTemplateResource\Pages;
use App\Models\BusTrip;
use App\Models\ScheduleTemplate;
use Filament\Forms\Form;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduleTemplateResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = ScheduleTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Jadwal';

    protected static ?string $navigationLabel = 'Template Jadwal';

    protected static ?string $modelLabel = 'Template Jadwal';

    protected static ?string $pluralModelLabel = 'Template Jadwal';

    public static function canAccess(): bool
    {
        return static::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Template Jadwal')
                ->schema([

                    Select::make('bus_trip_id')
                        ->label('Rute Trip')
                        ->options(
                            BusTrip::all()->mapWithKeys(fn ($trip) => [
                                $trip->id => "{$trip->trip_number} - {$trip->class_type}"
                            ])
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    TagsInput::make('departure_times')
                        ->label('Jam Keberangkatan')
                        ->placeholder('Contoh: 08:00')
                        ->required()
                        ->helperText('Tekan enter setelah mengisi jam'),

                    // ← TAMBAHAN INI SAJA
                    TextInput::make('duration_minutes')
                        ->label('Durasi Perjalanan')
                        ->numeric()
                        ->required()
                        ->suffix('menit')
                        ->helperText('Contoh: 180 = 3 jam, 90 = 1,5 jam'),

                    CheckboxList::make('days_of_week')
                        ->label('Hari Operasional')
                        ->options([
                            0 => 'Minggu',
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu',
                        ])
                        ->columns(4)
                        ->required(),

                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required(),

                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required(),

                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('busTrip.trip_number')
                    ->label('Nomor Trip')
                    ->searchable(),

                TextColumn::make('busTrip.class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d M Y'),

                TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date('d M Y'),

                TextColumn::make('departure_times')
                    ->label('Jam Keberangkatan')
                    ->formatStateUsing(fn ($state) =>
                        is_array($state) ? implode(', ', $state) : '-'
                    )
                    ->limit(40),

                // ← TAMBAHAN INI SAJA
                TextColumn::make('duration_minutes')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => $state . ' menit'),

            ])
            ->actions([

                Actions\Action::make('generate')
                    ->label('Generate Jadwal')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ScheduleTemplate $record) {
                        $count = $record->generateSchedules();
                        Notification::make()
                            ->title("{$count} jadwal berhasil dibuat")
                            ->success()
                            ->send();
                    }),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListScheduleTemplates::route('/'),
            'create' => Pages\CreateScheduleTemplate::route('/create'),
            'edit'   => Pages\EditScheduleTemplate::route('/{record}/edit'),
        ];
    }
}
