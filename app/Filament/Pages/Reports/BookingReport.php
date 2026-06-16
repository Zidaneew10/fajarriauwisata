<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Traits\HasRoleAccess;
use App\Models\Booking;
use App\Models\BusTrip;
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

class BookingReport extends Page implements HasForms, HasTable
{
    use HasRoleAccess;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Tiket';

    protected static ?string $navigationLabel = 'Laporan Booking';

    protected static ?string $title = 'Laporan Booking Tiket';

    protected static ?string $slug = 'reports/bookings';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.reports.booking-report';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $statusFilter = null;

    public ?int $busTripId = null;

    public static function canAccess(): bool
    {
        return static::canManageTickets() || static::isAdminOrManager();
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
        $this->form->fill([
            'dateFrom'     => $this->dateFrom,
            'dateTo'       => $this->dateTo,
            'statusFilter' => $this->statusFilter,
            'busTripId'    => $this->busTripId,
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

                Select::make('statusFilter')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->options([
                        'pending'   => 'Pending',
                        'paid'      => 'Lunas',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                        'expired'   => 'Expired',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->statusFilter = $state;
                        $this->resetTable();
                    }),

                Select::make('busTripId')
                    ->label('Rute Trip')
                    ->placeholder('Semua Trip')
                    ->options(
                        BusTrip::where('is_active', true)
                            ->orderBy('trip_number')
                            ->get()
                            ->mapWithKeys(fn ($trip) => [
                                $trip->id => "{$trip->trip_number} - {$trip->class_type}",
                            ])
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->busTripId = $state ? (int) $state : null;
                        $this->resetTable();
                    }),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredQuery())
            ->columns([
                TextColumn::make('booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Pemesan')
                    ->searchable(),

                TextColumn::make('schedule.busTrip.trip_number')
                    ->label('Trip'),

                TextColumn::make('schedule.busTrip.class_type')
                    ->label('Kelas')
                    ->badge(),

                TextColumn::make('schedule.departure_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('boardingStop.city')
                    ->label('Naik'),

                TextColumn::make('dropStop.city')
                    ->label('Turun'),

                TextColumn::make('passengers_count')
                    ->label('Penumpang')
                    ->counts('passengers'),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('discount_amount')
                    ->label('Diskon')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'   => 'Pending',
                        'paid'      => 'Lunas',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                        'expired'   => 'Expired',
                        default     => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid', 'confirmed' => 'success',
                        'pending'           => 'warning',
                        'cancelled'         => 'danger',
                        'expired'           => 'gray',
                        default             => 'secondary',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getFilteredQuery(): Builder
    {
        return Booking::query()
            ->with(['schedule.busTrip', 'user', 'boardingStop', 'dropStop'])
            ->when($this->dateFrom, fn ($q) => $q->whereHas('schedule', fn ($sq) =>
                $sq->whereDate('departure_date', '>=', $this->dateFrom)
            ))
            ->when($this->dateTo, fn ($q) => $q->whereHas('schedule', fn ($sq) =>
                $sq->whereDate('departure_date', '<=', $this->dateTo)
            ))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->busTripId, fn ($q) => $q->whereHas('schedule', fn ($sq) =>
                $sq->where('bus_trip_id', $this->busTripId)
            ));
    }

    public function getSummary(): array
    {
        $query = $this->getFilteredQuery();

        return [
            'total_bookings'   => (clone $query)->count(),
            'total_revenue'    => (clone $query)->whereIn('status', ['paid', 'confirmed'])->sum('total_price'),
            'total_passengers' => (clone $query)->whereIn('status', ['paid', 'confirmed'])->withCount('passengers')->get()->sum('passengers_count'),
            'confirmed_count'  => (clone $query)->where('status', 'confirmed')->count(),
            'total_discount'   => (clone $query)->whereIn('status', ['paid', 'confirmed'])->sum('discount_amount'),
            'cancelled_count'  => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }
}
