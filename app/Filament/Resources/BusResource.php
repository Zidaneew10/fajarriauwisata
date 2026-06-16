<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\BusResource\Pages;
use App\Models\Bus;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class BusResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = Bus::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Armada Bus';

    protected static ?string $modelLabel = 'Armada Bus';

    protected static ?string $pluralModelLabel = 'Armada Bus';

    public static function canAccess(): bool
    {
        return static::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            TextInput::make('bus_code')
                ->label('Kode Bus')
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('plate_number')
                ->label('Nomor Polisi')
                ->placeholder('Contoh: BM 1234 AU')
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('class_type')
                ->label('Kelas Bus')
                ->options([
                    'Sleeper'   => 'Sleeper',
                    'SE 2-1'    => 'SE 2-1',
                    'Executive' => 'Executive',
                ])
                ->required(),

            TextInput::make('capacity')
                ->label('Kapasitas Kursi')
                ->numeric()
                ->required(),

            TextInput::make('brand')
                ->label('Merek Bus')
                ->placeholder('Contoh: Scania')
                ->maxLength(100),

            TextInput::make('model')
                ->label('Model Bus')
                ->placeholder('Contoh: Jetbus 5')
                ->maxLength(100),

            TextInput::make('year')
                ->label('Tahun')
                ->numeric()
                ->minValue(2000)
                ->maxValue(date('Y')),

            Select::make('status')
                ->label('Status Armada')
                ->options([
                    'active'      => 'Aktif',
                    'maintenance' => 'Maintenance',
                    'inactive'    => 'Tidak Aktif',
                ])
                ->default('active')
                ->required(),

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('bus_code')
                    ->label('Kode Bus')
                    ->searchable(),

                TextColumn::make('plate_number')
                    ->label('Nomor Polisi')
                    ->searchable(),

                TextColumn::make('class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('capacity')
                    ->label('Kapasitas'),

                TextColumn::make('brand')
                    ->label('Merek'),

                TextColumn::make('model')
                    ->label('Model'),

                TextColumn::make('year')
                    ->label('Tahun'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Aktif',
                        'maintenance' => 'Maintenance',
                        'inactive' => 'Tidak Aktif',
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'maintenance' => 'warning',
                        'inactive' => 'danger',
                    }),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'maintenance' => 'Maintenance',
                        'inactive' => 'Tidak Aktif',
                    ]),

                SelectFilter::make('class_type')
                    ->label('Kelas')
                    ->options([
                        'Sleeper' => 'Sleeper',
                        'SE 2-1' => 'SE 2-1',
                        'Executive' => 'Executive',
                    ]),

            ])

            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBuses::route('/'),
            'create' => Pages\CreateBus::route('/create'),
            'edit' => Pages\EditBus::route('/{record}/edit'),
        ];
    }
}
