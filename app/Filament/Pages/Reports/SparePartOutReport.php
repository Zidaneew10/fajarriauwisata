<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Traits\HasRoleAccess;
use App\Models\Bus;
use App\Models\SparePartOut;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SparePartOutReport extends Page implements HasForms, HasTable
{
    use HasRoleAccess;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Inventaris';

    protected static ?string $navigationLabel = 'Laporan Sparepart Keluar';

    protected static ?string $title = 'Laporan Sparepart Keluar';

    protected static ?string $slug = 'reports/sparepart-out';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.reports.spare-part-out-report';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $busId = null;

    public static function canAccess(): bool
    {
        return static::canManageInventory() || static::isAdminOrManager();
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
        $this->form->fill([
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
            'busId'    => $this->busId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label('Dari Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->dateFrom = $state;
                        $this->resetTable();
                    }),

                DatePicker::make('dateTo')
                    ->label('Sampai Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->dateTo = $state;
                        $this->resetTable();
                    }),

                Select::make('busId')
                    ->label('Bus')
                    ->placeholder('Semua Bus')
                    ->options(
                        Bus::orderBy('plate_number')
                            ->get()
                            ->mapWithKeys(fn ($bus) => [
                                $bus->id => "{$bus->plate_number} - {$bus->class_type}",
                            ])
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->busId = $state ? (int) $state : null;
                        $this->resetTable();
                    }),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredQuery())
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Nomor Referensi')
                    ->searchable(),

                TextColumn::make('bus.plate_number')
                    ->label('Bus'),

                TextColumn::make('bus.class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('used_at')
                    ->label('Tanggal Pemakaian')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),

                TextColumn::make('user.name')
                    ->label('Dicatat Oleh'),
            ])
            ->defaultSort('used_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getFilteredQuery(): Builder
    {
        return SparePartOut::query()
            ->with(['bus', 'user'])
            ->withCount('items')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('used_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('used_at', '<=', $this->dateTo))
            ->when($this->busId, fn ($q) => $q->where('bus_id', $this->busId));
    }

    public function getSummary(): array
    {
        $query = $this->getFilteredQuery();

        return [
            'total_records' => (clone $query)->count(),
            'total_items'   => (clone $query)->withCount('items')->get()->sum('items_count'),
            'total_buses'   => (clone $query)->distinct('bus_id')->count('bus_id'),
        ];
    }
}
