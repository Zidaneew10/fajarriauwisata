<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class FacilityResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = Facility::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Fasilitas';

    protected static ?string $modelLabel = 'Fasilitas';

    protected static ?string $pluralModelLabel = 'Fasilitas';

    public static function canAccess(): bool
    {
        return static::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Fasilitas')
                ->schema([

                    TextInput::make('name')
                        ->label('Nama Fasilitas')
                        ->required()
                        ->maxLength(100),

                    FileUpload::make('image')
                        ->label('Gambar Fasilitas')
                        ->image()
                        ->directory('facilities')
                        ->imagePreviewHeight('150')
                        ->nullable(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->columnSpanFull(),

                ])
                ->columns(2),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                ImageColumn::make('image')
                    ->label('Gambar')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),

            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
