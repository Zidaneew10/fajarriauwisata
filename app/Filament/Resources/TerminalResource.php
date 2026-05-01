<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TerminalResource\Pages;
use App\Models\Terminal;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;

class TerminalResource extends Resource
{
    protected static ?string $model = Terminal::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->required()->maxLength(10)->unique(ignoreRecord: true),
            TextInput::make('name')->required(),
            TextInput::make('city')->required(),
            TextInput::make('country')->default('Indonesia'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('city'),
            TextColumn::make('country'),
        ])->actions([
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTerminals::route('/'),
            'create' => Pages\CreateTerminal::route('/create'),
            'edit'   => Pages\EditTerminal::route('/{record}/edit'),
        ];
    }
}
