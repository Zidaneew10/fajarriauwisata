<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\SparePartOutResource\Pages;
use App\Models\Bus;
use App\Models\SparePart;
use App\Models\SparePartOut;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class SparePartOutResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = SparePartOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Sparepart Keluar';

    protected static ?string $modelLabel = 'Sparepart Keluar';

    protected static ?string $pluralModelLabel = 'Sparepart Keluar';

    public static function canAccess(): bool
    {
        return static::canManageInventory();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Informasi Pemakaian Sparepart')
                ->schema([

                    Select::make('bus_id')
                        ->label('Bus')
                        ->options(
                            Bus::where('status', 'active')
                                ->get()
                                ->mapWithKeys(fn ($bus) => [
                                    $bus->id => "{$bus->plate_number} - {$bus->class_type}"
                                ])
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('reference_number')
                        ->label('Nomor Referensi')
                        ->default('OUT-' . strtoupper(uniqid()))
                        ->required()
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('used_at')
                        ->label('Tanggal Pemakaian')
                        ->default(now())
                        ->required(),

                    TextInput::make('reason')
                        ->label('Alasan Pemakaian'),

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
                                            $part->id =>
                                                "{$part->code} - {$part->name} (Stok: {$part->stock} {$part->unit})"
                                        ])
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->required()
                                ->helperText('Jumlah sparepart yang digunakan'),

                        ])
                        ->columns(2)
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

                TextColumn::make('bus.plate_number')
                    ->label('Nomor Polisi')
                    ->searchable(),

                TextColumn::make('bus.class_type')
                    ->label('Kelas Bus')
                    ->badge(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('used_at')
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

            ->filters([

                Filter::make('used_at')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('used_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('used_at', '<=', $data['until']));
                    }),

                SelectFilter::make('bus_id')
                    ->label('Bus')
                    ->relationship('bus', 'plate_number')
                    ->searchable()
                    ->preload(),

            ])

            ->actions([

                ViewAction::make(),

                DeleteAction::make(),

            ])

            ->defaultSort('used_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSparePartOuts::route('/'),
            'create' => Pages\CreateSparePartOut::route('/create'),
        ];
    }
}
