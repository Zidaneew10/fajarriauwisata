<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\BusTripResource\Pages;
use App\Models\BusTrip;
use App\Models\Stop;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class BusTripResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = BusTrip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Rute Trip';

    protected static ?string $modelLabel = 'Rute Trip';

    protected static ?string $pluralModelLabel = 'Rute Trip';

    public static function canAccess(): bool
    {
        return static::canManageTickets();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Trip')
                ->schema([

                    TextInput::make('trip_number')
                        ->label('Nomor Trip')
                        ->placeholder('Contoh: PKU-DMI-01')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Select::make('class_type')
                        ->label('Kelas Bus')
                        ->options([
                            'Ekonomi'   => 'Ekonomi',
                            'Executive' => 'Executive',
                            'SE 2-1'    => 'SE 2-1',
                            'Sleeper'   => 'Sleeper',
                        ])
                        ->required(),

                    Select::make('seat_layout')
                        ->label('Layout Kursi')
                        ->options([
                            '2-2' => '2-2 (4 Kursi)',
                            '2-1' => '2-1 (3 Kursi)',
                            '1-1' => '1-1 (Sleeper)',
                        ])
                        ->default('2-2')
                        ->required(),

                    TextInput::make('capacity')
                        ->label('Kapasitas Kursi')
                        ->numeric()
                        ->required(),

                    TextInput::make('price')
                        ->label('Harga Tiket')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->columnSpanFull(),

                ])
                ->columns(2),

            Section::make('Rute Perjalanan')
                ->schema([

                    Repeater::make('routeSegments')
                        ->label('Daftar Titik Pemberhentian')
                        ->relationship()
                        ->schema([

                            TextInput::make('sequence')
                                ->label('Urutan')
                                ->numeric()
                                ->required(),

                            Select::make('stop_id')
                                ->label('Titik Pemberhentian')
                                ->options(
                                    Stop::all()->mapWithKeys(fn ($stop) => [
                                        $stop->id => "{$stop->city} - {$stop->name}"
                                    ])
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah Titik Pemberhentian')
                        ->orderColumn('sequence')
                        ->columnSpanFull(),

                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('trip_number')
                    ->label('Nomor Trip')
                    ->searchable(),

                TextColumn::make('class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('capacity')
                    ->label('Kapasitas'),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),

                TextColumn::make('seat_layout')
                    ->label('Layout'),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
