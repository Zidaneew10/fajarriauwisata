<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\SparePartResource\Pages;
use App\Models\SparePart;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class SparePartResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = SparePart::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Sparepart';

    protected static ?string $modelLabel = 'Sparepart';

    protected static ?string $pluralModelLabel = 'Sparepart';

    public static function canAccess(): bool
    {
        return static::canManageInventory();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Sparepart')
                ->schema([

                    TextInput::make('code')
                        ->label('Kode Sparepart')
                        ->required()
                        ->unique(ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Nama Sparepart')
                        ->required(),

                    Select::make('unit')
                        ->label('Satuan')
                        ->options([
                            'pcs'   => 'Pcs',
                            'liter' => 'Liter',
                            'set'   => 'Set',
                            'meter' => 'Meter',
                            'kg'    => 'Kilogram',
                            'buah'  => 'Buah',
                        ])
                        ->required(),

                    TextInput::make('stock')
                        ->label('Stok Saat Ini')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('price')
                        ->label('Harga Satuan')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->columnSpanFull(),

                ])
                ->columns(2),

            Section::make('Perhitungan Safety Stock & ROP')
                ->schema([

                    TextInput::make('maximum_daily_usage')
                        ->label('Pemakaian Maksimum / Hari')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($get, $set) {

                            $max  = (float) $get('maximum_daily_usage');
                            $avg  = (float) $get('avg_daily_usage');
                            $lead = (float) $get('lead_time');


                            $ss = ($max - $avg) * $lead;


                            $rop = ($lead * $avg) + $ss;

                            $set('safety_stock', $ss >= 0 ? (int) ceil($ss) : 0);
                            $set('rop', (int) ceil($rop));
                        }),

                    TextInput::make('avg_daily_usage')
                        ->label('Rata-rata Pemakaian / Hari')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($get, $set) {

                            $max  = (float) $get('maximum_daily_usage');
                            $avg  = (float) $get('avg_daily_usage');
                            $lead = (float) $get('lead_time');

                            $ss = ($max - $avg) * $lead;

                            $rop = ($lead * $avg) + $ss;

                            $set('safety_stock', ceil($ss));
                            $set('rop', ceil($rop));
                        }),

                    TextInput::make('lead_time')
                        ->label('Lead Time (Hari)')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($get, $set) {

                            $max  = (float) $get('maximum_daily_usage');
                            $avg  = (float) $get('avg_daily_usage');
                            $lead = (float) $get('lead_time');

                            $ss = ($max - $avg) * $lead;

                            $rop = ($lead * $avg) + $ss;

                            $set('safety_stock', ceil($ss));
                            $set('rop', ceil($rop));
                        }),

                    TextInput::make('safety_stock')
                        ->label('Safety Stock')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),

                    TextInput::make('rop')
                        ->label('Reorder Point (ROP)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),

                ])
                ->columns(2),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                TextColumn::make('unit')
                    ->label('Satuan'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(SparePart $record) => match (true) {
                        $record->isCritical()   => 'danger',
                        $record->needsReorder() => 'warning',
                        default                 => 'success',
                    }),

            

                TextColumn::make('safety_stock')
                    ->label('Safety Stock')
                    ->badge()
                    ->color('info'),

                TextColumn::make('rop')
                    ->label('ROP')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->label('Status Stok')
                    ->options([
                        'critical' => 'Kritis',
                        'reorder'  => 'Perlu Reorder',
                        'safe'     => 'Aman',
                    ])
                    ->query(function ($query, $data) {

                        match ($data['value'] ?? null) {

                            'critical'
                            => $query->whereRaw('stock <= safety_stock'),

                            'reorder'
                            => $query->whereRaw('stock <= rop AND stock > safety_stock'),

                            'safe'
                            => $query->whereRaw('stock > rop'),

                            default => null,
                        };
                    }),

            ])

            ->actions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

            ])

            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'edit'   => Pages\EditSparePart::route('/{record}/edit'),
        ];
    }
}
