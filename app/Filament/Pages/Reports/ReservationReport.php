<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Traits\HasRoleAccess;
use App\Models\Bus;
use App\Models\Reservation;
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

class ReservationReport extends Page implements HasForms, HasTable
{
    use HasRoleAccess;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reservasi';

    protected static ?string $navigationLabel = 'Laporan Reservasi';

    protected static ?string $title = 'Laporan Reservasi Bus';

    protected static ?string $slug = 'reports/reservations';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.reports.reservation-report';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $busId = null;

    public static function canAccess(): bool
    {
        return static::canManageTickets() || static::isAdminOrManager();
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
                    ->label('Dari Tanggal Berangkat')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->dateFrom = $state;
                        $this->resetTable();
                    }),

                DatePicker::make('dateTo')
                    ->label('Sampai Tanggal Berangkat')
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
                TextColumn::make('reservation_code')
                    ->label('Kode')
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),

                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->limit(25),

                TextColumn::make('departure_date')
                    ->label('Berangkat')
                    ->date('d M Y'),

                TextColumn::make('buses.bus.plate_number')
                    ->label('Bus')
                    ->badge()
                    ->separator(','),

                TextColumn::make('passenger_count')
                    ->label('Penumpang'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('payment_status')
                    ->label('Bayar')
                    ->badge(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR'),
            ])
            ->defaultSort('departure_date', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getFilteredQuery(): Builder
    {
        return Reservation::query()
            ->with(['buses.bus'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('departure_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('departure_date', '<=', $this->dateTo))
            ->when($this->busId, fn ($q) => $q->whereHas('buses', fn ($q2) => $q2->where('bus_id', $this->busId)));
    }

    public function getSummary(): array
    {
        $query = $this->getFilteredQuery();

        return [
            'total_reservations' => (clone $query)->count(),
            'total_revenue'      => (clone $query)->sum('total_price'),
            'total_passengers'   => (clone $query)->sum('passenger_count'),
            'confirmed_count'    => (clone $query)->whereIn('status', ['confirmed', 'in_progress', 'completed'])->count(),
        ];
    }
}
