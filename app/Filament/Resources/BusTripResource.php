<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusTripResource\Pages;
use App\Models\BusTrip;
use App\Models\Facility;
use App\Models\Terminal;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class BusTripResource extends Resource
{
    protected static ?string $model = BusTrip::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $label = 'Bus Trip';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Wizard\Step::make('Informasi Trip')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('trip_number')
                            ->label('Nomor Trip')
                            ->placeholder('Contoh: PKU-DMI-001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                    ]),

                Wizard\Step::make('Rute Perjalanan')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Repeater::make('routeSegments')
                            ->label('Segmen Rute')
                            ->schema([
                                TextInput::make('sequence')->label('Urutan')->numeric()->required(),
                                Select::make('terminal_id')
                                    ->label('Terminal')
                                    ->options(Terminal::pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Terminal')
                            ->orderColumn('sequence')
                            ->default([])
                            ->columnSpanFull(),
                    ]),

                Wizard\Step::make('Kelas Bus & Harga')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Repeater::make('busClasses')
                            ->label('Kelas Bus')
                            ->schema([
                                Select::make('class_type')
                                    ->label('Kelas')
                                    ->options(['Sleeper' => 'Sleeper', 'SE 2-1' => 'SE 2-1', 'Executive' => 'Executive'])
                                    ->required(),
                                TextInput::make('price')->label('Harga (Rp)')->numeric()->prefix('Rp')->required(),
                                TextInput::make('capacity')->label('Kapasitas Kursi')->numeric()->required(),
                                Select::make('facility_ids')
                                    ->label('Fasilitas')
                                    ->options(Facility::pluck('name', 'id'))
                                    ->multiple()
                                    ->searchable()
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah Kelas')
                            ->default([])
                            ->columnSpanFull(),
                    ]),
            ])
                ->columnSpanFull()
                ->skippable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('trip_number')->label('Nomor Trip')->searchable(),
            TextColumn::make('busClasses_count')->counts('busClasses')->label('Kelas'),
            TextColumn::make('routeSegments_count')->counts('routeSegments')->label('Segmen Rute'),
            TextColumn::make('created_at')->label('Dibuat')->date('d M Y'),
        ])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBusTrips::route('/'),
            'create' => Pages\CreateBusTrip::route('/create'),
            'edit'   => Pages\EditBusTrip::route('/{record}/edit'),
        ];
    }
}
