<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $label = 'Fasilitas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama Fasilitas')
                ->required(),

            FileUpload::make('image')
                ->label('Ikon / Gambar')
                ->image()
                ->directory('facilities')
                ->nullable(),

            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('image')->label('Gambar')->circular(),
            TextColumn::make('name')->label('Nama')->searchable(),
            TextColumn::make('description')->label('Deskripsi')->limit(50),
        ])->actions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit'   => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
