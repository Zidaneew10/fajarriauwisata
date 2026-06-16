<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\StopResource\Pages;
use App\Models\Stop;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class StopResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = Stop::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Titik Pemberhentian';

    protected static ?string $modelLabel = 'Titik Pemberhentian';

    protected static ?string $pluralModelLabel = 'Titik Pemberhentian';

    public static function canAccess(): bool
    {
        return static::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('type')
                ->label('Tipe')
                ->options([
                    'terminal' => 'Terminal',
                    'titik_jalan' => 'Titik Jalan',
                ])
                ->default('terminal')
                ->required(),

            TextInput::make('city')
                ->label('Kota')
                ->required(),

            TextInput::make('name')
                ->label('Nama Titik')
                ->placeholder('Contoh: Terminal AKAP')
                ->required(),

            TextInput::make('address')
                ->label('Alamat')
                ->placeholder('Contoh: Jl. HR Soebrantas')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) =>
                        $state === 'terminal'
                            ? 'Terminal'
                            : 'Titik Jalan'
                    )
                    ->color(fn ($state) =>
                        $state === 'terminal'
                            ? 'info'
                            : 'warning'
                    ),

                TextColumn::make('city')
                    ->label('Kota')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nama Titik')
                    ->searchable(),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40),
            ])

            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'terminal' => 'Terminal',
                        'titik_jalan' => 'Titik Jalan',
                    ]),
            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStops::route('/'),
            'create' => Pages\CreateStops::route('/create'),
            'edit' => Pages\EditStops::route('/{record}/edit'),
        ];
    }
}
