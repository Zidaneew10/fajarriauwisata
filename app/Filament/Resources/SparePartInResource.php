<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartInResource\Pages;
use App\Models\SparePart;
use App\Models\SparePartIn;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class SparePartInResource extends Resource
{
    protected static ?string $model = SparePartIn::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $label = 'Sparepart Masuk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('reference_number')
                ->label('No. Referensi')
                ->default('IN-' . strtoupper(uniqid()))
                ->required(),

            TextInput::make('supplier')
                ->label('Supplier')
                ->nullable(),

            DatePicker::make('received_at')
                ->label('Tanggal Terima')
                ->default(now())
                ->required(),

            Textarea::make('notes')
                ->label('Catatan')
                ->nullable()
                ->columnSpanFull(),

            Repeater::make('items')
                ->label('Daftar Sparepart Masuk')
                ->schema([
                    Select::make('spare_part_id')
                        ->label('Sparepart')
                        ->options(SparePart::pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->required(),

                    TextInput::make('price_per_unit')
                        ->label('Harga/Unit (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                ])
                ->columns(3)
                ->addActionLabel('Tambah Sparepart')
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference_number')->label('No. Ref')->searchable()->copyable(),
            TextColumn::make('supplier')->label('Supplier'),
            TextColumn::make('received_at')->date('d M Y')->label('Tanggal'),
            TextColumn::make('items_count')->counts('items')->label('Jml Item'),
            TextColumn::make('user.name')->label('Dicatat Oleh'),
        ])->actions([
            ViewAction::make(),
            DeleteAction::make(),
        ])->defaultSort('received_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSparePartIns::route('/'),
            'create' => Pages\CreateSparePartIn::route('/create'),
        ];
    }
}
