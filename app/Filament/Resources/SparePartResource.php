<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Models\SparePart;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $label = 'Sparepart';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Sparepart')->schema([
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

                TextInput::make('price')
                    ->label('Harga Satuan (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Safety Stock & ROP')->schema([
                TextInput::make('safety_stock')
                    ->label('Safety Stock')
                    ->numeric()
                    ->required()
                    ->helperText('Stok minimum yang harus selalu tersedia'),

                TextInput::make('lead_time')
                    ->label('Lead Time (hari)')
                    ->numeric()
                    ->required()
                    ->helperText('Rata-rata waktu tunggu pengiriman dari supplier'),

                TextInput::make('avg_daily_usage')
                    ->label('Rata-rata Pemakaian/Hari')
                    ->numeric()
                    ->required()
                    ->helperText('Akan otomatis diupdate dari histori pemakaian'),

                TextInput::make('rop')
                    ->label('ROP (Reorder Point)')
                    ->numeric()
                    ->disabled()
                    ->helperText('ROP = (Avg Daily Usage × Lead Time) + Safety Stock — dihitung otomatis'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('unit')->label('Satuan'),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(SparePart $record) => match(true) {
                        $record->isCritical()   => 'danger',
                        $record->needsReorder() => 'warning',
                        default                 => 'success',
                    }),
                TextColumn::make('safety_stock')->label('Safety Stock'),
                TextColumn::make('rop')->label('ROP'),
                TextColumn::make('avg_daily_usage')->label('Avg/Hari')->numeric(2),
                TextColumn::make('price')->money('IDR')->label('Harga'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Stok')
                    ->options([
                        'critical' => 'Kritis (< Safety Stock)',
                        'reorder'  => 'Perlu Reorder (< ROP)',
                        'ok'       => 'Aman',
                    ])
                    ->query(function ($query, $data) {
                        match($data['value'] ?? null) {
                            'critical' => $query->whereRaw('stock <= safety_stock'),
                            'reorder'  => $query->whereRaw('stock <= rop AND stock > safety_stock'),
                            'ok'       => $query->whereRaw('stock > rop'),
                            default    => null,
                        };
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->after(function (SparePart $record) {
                        // Hitung ulang ROP setelah edit
                        $record->rop = $record->calculateRop();
                        $record->save();
                    }),
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
