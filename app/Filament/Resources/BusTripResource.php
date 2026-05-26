<?php

namespace App\Filament\Resources;

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
    protected static ?string $model          = BusTrip::class;
    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $label           = 'Rute Trip';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Info Trip')->schema([
                TextInput::make('trip_number')
                    ->label('Nomor Trip')
                    ->placeholder('Contoh: PKU-DMI-01')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('class_type')
                    ->label('Kelas')
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
                        '2-2' => '2-2 → 4 kursi/baris (A,B,C,D)',
                        '2-1' => '2-1 → 3 kursi/baris (A,B,C)',
                        '1-1' => '1-1 → 2 kursi/baris (A,B) — Sleeper',
                    ])
                    ->default('2-2')
                    ->required(),

                TextInput::make('capacity')
                    ->label('Kapasitas Kursi')
                    ->numeric()
                    ->required(),

                TextInput::make('price')
                    ->label('Harga (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Rute Perjalanan')->schema([
                Repeater::make('routeSegments')
                    ->label('Titik Pemberhentian')
                    ->relationship()
                    ->schema([
                        TextInput::make('sequence')
                            ->label('Urutan')
                            ->numeric()
                            ->required()
                            ->helperText('1 = titik awal'),

                        Select::make('stop_id')
                            ->label('Titik Pemberhentian')
                            ->options(
                                Stop::all()->mapWithKeys(fn($s) => [
                                    $s->id => ($s->type === 'terminal' ? '🏢' : '📍')
                                        . " {$s->city} — {$s->name}"
                                ])
                            )
                            ->searchable()
                            ->required(),


                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Titik')
                    ->orderColumn('sequence')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('trip_number')->label('No. Trip')->searchable(),
            TextColumn::make('class_type')->label('Kelas')->badge(),
            TextColumn::make('capacity')->label('Kapasitas'),
            TextColumn::make('price')->money('IDR')->label('Harga'),
            TextColumn::make('seat_layout')->label('Layout'),
            ToggleColumn::make('is_active')->label('Aktif'),
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
