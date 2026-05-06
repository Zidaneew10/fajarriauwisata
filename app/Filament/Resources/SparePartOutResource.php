<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartOutResource\Pages;
use App\Models\Bus;
use App\Models\SparePart;
use App\Models\SparePartOut;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SparePartOutResource extends Resource
{
    protected static ?string $model = SparePartOut::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $label = 'Sparepart Keluar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('bus_id')
                ->label('Bus')
                ->options(
                    Bus::where('status', 'active')
                        ->get()
                        ->mapWithKeys(fn($b) => [
                            $b->id => "{$b->plate_number} — {$b->class_type}"
                        ])
                )
                ->searchable()
                ->required(),
            TextInput::make('reference_number')
                ->label('No. Referensi')
                ->default('OUT-' . strtoupper(uniqid()))
                ->required(),

            DatePicker::make('used_at')
                ->label('Tanggal Pemakaian')
                ->default(now())
                ->required(),

            TextInput::make('reason')
                ->label('Alasan')
                ->nullable(),

            Textarea::make('notes')
                ->label('Catatan')
                ->nullable()
                ->columnSpanFull(),

            Repeater::make('items')
                ->label('Daftar Sparepart Keluar')
                ->schema([
                    Select::make('spare_part_id')
                        ->label('Sparepart')
                        ->options(SparePart::all()->mapWithKeys(fn($s) => [
                            $s->id => "{$s->name} (Stok: {$s->stock} {$s->unit})"
                        ]))
                        ->searchable()
                        ->required(),

                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2)
                ->addActionLabel('Tambah Sparepart')
                ->minItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('reference_number')->label('No. Ref')->searchable()->copyable(),
            TextColumn::make('bus_code')->label('Kode Bus')->searchable(),
            TextColumn::make('reason')->label('Alasan')->limit(30),
            TextColumn::make('used_at')->date('d M Y')->label('Tanggal'),
            TextColumn::make('items_count')->counts('items')->label('Jml Item'),
            TextColumn::make('user.name')->label('Dicatat Oleh'),
        ])->actions([
            ViewAction::make(),
            DeleteAction::make(),
        ])->defaultSort('used_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSparePartOuts::route('/'),
            'create' => Pages\CreateSparePartOut::route('/create'),
        ];
    }
}
