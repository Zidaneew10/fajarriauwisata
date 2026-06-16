<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\SparePartInResource\Pages;
use App\Models\SparePart;
use App\Models\SparePartIn;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;

class SparePartInResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = SparePartIn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Sparepart Masuk';

    protected static ?string $modelLabel = 'Sparepart Masuk';

    protected static ?string $pluralModelLabel = 'Sparepart Masuk';

    public static function canAccess(): bool
    {
        return static::canManageInventory();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Barang Masuk')
                ->schema([

                    TextInput::make('reference_number')
                        ->label('Nomor Referensi')
                        ->default('IN-' . strtoupper(uniqid()))
                        ->required()
                        ->disabled()
                        ->dehydrated(),

                    TextInput::make('supplier')
                        ->label('Supplier'),

                    DatePicker::make('received_at')
                        ->label('Tanggal Diterima')
                        ->default(now())
                        ->required(),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(4)
                        ->columnSpanFull(),

                ])
                ->columns(2),

            Section::make('Daftar Sparepart')
                ->schema([

                    Repeater::make('items')
                        ->label('Item Sparepart')
                        ->relationship()
                        ->schema([

                            Select::make('spare_part_id')
                                ->label('Sparepart')
                                ->options(
                                    SparePart::all()
                                        ->mapWithKeys(fn ($part) => [
                                            $part->id => "{$part->code} - {$part->name}"
                                        ])
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->required(),

                            TextInput::make('price_per_unit')
                                ->label('Harga per Unit')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),

                        ])
                        ->columns(3)
                        ->addActionLabel('Tambah Sparepart')
                        ->minItems(1)
                        ->columnSpanFull(),

                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('reference_number')
                    ->label('Nomor Referensi')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('supplier')
                    ->label('Supplier')
                    ->placeholder('-'),

                TextColumn::make('received_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),

                TextColumn::make('user.name')
                    ->label('Dicatat Oleh'),

                TextColumn::make('created_at')
                    ->label('Input')
                    ->since(),

            ])

            ->actions([

                ViewAction::make(),

                DeleteAction::make(),

            ])

            ->defaultSort('received_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSparePartIns::route('/'),
            'create' => Pages\CreateSparePartIn::route('/create'),
        ];
    }
}
