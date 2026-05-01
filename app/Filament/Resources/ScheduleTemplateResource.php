<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleTemplateResource\Pages;
use App\Models\BusTrip;
use App\Models\ScheduleTemplate;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;

class ScheduleTemplateResource extends Resource
{
    protected static ?string $model = ScheduleTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Jadwal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('bus_trip_id')->label('Bus Trip')
                ->options(BusTrip::pluck('trip_number', 'id'))->required(),
            TagsInput::make('departure_times')->label('Jam Keberangkatan')
                ->placeholder('Contoh: 08:00')->required(),
            CheckboxList::make('days_of_week')->label('Hari Operasi')
                ->options([0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'])
                ->columns(4)->required(),
            DatePicker::make('start_date')->required(),
            DatePicker::make('end_date')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('busTrip.trip_number')->label('Trip'),
            TextColumn::make('start_date')->date('d M Y'),
            TextColumn::make('end_date')->date('d M Y'),
        ])->actions([
            Actions\Action::make('generate')
                ->label('Generate Jadwal')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (ScheduleTemplate $record) {
                    $count = $record->generateSchedules();
                    Notification::make()->title("{$count} jadwal berhasil digenerate!")->success()->send();
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
