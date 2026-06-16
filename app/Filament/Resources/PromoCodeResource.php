<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class PromoCodeResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kode Promo';

    protected static ?string $modelLabel = 'Kode Promo';

    protected static ?string $pluralModelLabel = 'Kode Promo';

    public static function canAccess(): bool
    {
        return static::canManageMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Promo')
                ->schema([

                    TextInput::make('code')
                        ->label('Kode Promo')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                    Select::make('discount_type')
                        ->label('Jenis Diskon')
                        ->options([
                            'percentage' => 'Persentase',
                            'fixed' => 'Nominal',
                        ])
                        ->required()
                        ->live(),

                    TextInput::make('discount_value')
                        ->label(fn ($get) =>
                            $get('discount_type') === 'percentage'
                                ? 'Diskon (%)'
                                : 'Diskon (Rp)'
                        )
                        ->numeric()
                        ->required(),

                    TextInput::make('max_discount')
                        ->label('Maksimum Diskon')
                        ->numeric()
                        ->visible(fn ($get) =>
                            $get('discount_type') === 'percentage'
                        ),

                    TextInput::make('min_purchase')
                        ->label('Minimum Pembelian')
                        ->numeric()
                        ->default(0),

                    DatePicker::make('valid_from')
                        ->label('Berlaku Dari')
                        ->required(),

                    DatePicker::make('valid_until')
                        ->label('Berlaku Sampai')
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true),

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

                TextColumn::make('code')
                    ->label('Kode Promo')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('discount_type')
                    ->label('Jenis Diskon')
                    ->badge()
                    ->formatStateUsing(fn ($state) =>
                        $state === 'percentage'
                            ? 'Persentase'
                            : 'Nominal'
                    )
                    ->color(fn ($state) =>
                        $state === 'percentage'
                            ? 'info'
                            : 'warning'
                    ),

                TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->discount_type === 'percentage'
                            ? $state . '%'
                            : 'Rp ' . number_format($state, 0, ',', '.')
                    ),

                TextColumn::make('min_purchase')
                    ->label('Minimum Pembelian')
                    ->money('IDR'),

                TextColumn::make('valid_from')
                    ->label('Mulai')
                    ->date('d M Y'),

                TextColumn::make('valid_until')
                    ->label('Berakhir')
                    ->date('d M Y'),

                TextColumn::make('usages_count')
                    ->label('Digunakan')
                    ->counts('usages'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

            ])

            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
