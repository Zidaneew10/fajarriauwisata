<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $label = 'Promo Code';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->label('Kode Promo')->required()
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn($state) => strtoupper($state)),
            Textarea::make('description')->label('Deskripsi')->nullable(),
            Select::make('discount_type')->label('Jenis Diskon')
                ->options(['percentage' => 'Persentase (%)', 'fixed' => 'Nominal Tetap (Rp)'])
                ->required()->live(),
            TextInput::make('discount_value')
                ->label(fn($get) => $get('discount_type') === 'percentage' ? 'Besar Diskon (%)' : 'Besar Diskon (Rp)')
                ->numeric()->required(),
            TextInput::make('max_discount')->label('Maksimum Diskon (Rp)')
                ->numeric()->nullable()
                ->visible(fn($get) => $get('discount_type') === 'percentage'),
            TextInput::make('min_purchase')->label('Minimum Pembelian (Rp)')->numeric()->default(0),
            DatePicker::make('valid_from')->label('Berlaku Dari')->required(),
            DatePicker::make('valid_until')->label('Berlaku Sampai')->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->copyable(),
            TextColumn::make('discount_type')->badge()->label('Jenis')
                ->formatStateUsing(fn($state) => $state === 'percentage' ? 'Persentase' : 'Nominal')
                ->color(fn($state) => $state === 'percentage' ? 'info' : 'warning'),
            TextColumn::make('discount_value')->label('Diskon')
                ->formatStateUsing(fn($state, $record) =>
                    $record->discount_type === 'percentage'
                        ? $state . '%'
                        : 'Rp' . number_format($state, 0, ',', '.')
                ),
            TextColumn::make('valid_from')->date('d M Y')->label('Dari'),
            TextColumn::make('valid_until')->date('d M Y')->label('Sampai'),
            TextColumn::make('usages_count')->counts('usages')->label('Dipakai'),
            IconColumn::make('is_active')->boolean()->label('Aktif'),
        ])->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit'   => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
