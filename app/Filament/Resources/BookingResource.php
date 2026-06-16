<?php

namespace App\Filament\Resources;


use App\Filament\Traits\HasRoleAccess;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\BookingService;
use App\Services\RescheduleService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;

class BookingResource extends Resource
{
    use HasRoleAccess;


    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Tiket';

    protected static ?string $navigationLabel = 'Booking';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Booking';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([

            Section::make('Informasi Booking')
                ->schema([
                    Placeholder::make('booking_code')
                        ->label('Kode Booking')
                        ->content(fn (Booking $record): string => $record->booking_code ?? '-'),

                    Placeholder::make('status')
                        ->label('Status')
                        ->content(fn (Booking $record): string => match ($record->status) {
                            'pending' => '⏳ Pending',
                            'paid' => '💰 Lunas',
                            'confirmed' => '✅ Dikonfirmasi',
                            'cancelled' => '❌ Dibatalkan',
                            'expired' => '⌛ Expired',
                            default => $record->status ?? '-',
                        }),

                    Placeholder::make('user_name')
                        ->label('Nama Pemesan')
                        ->content(fn (Booking $record): string => $record->user?->name ?? '-'),

                    Placeholder::make('created_at_display')
                        ->label('Tanggal Booking')
                        ->content(fn (Booking $record): string => $record->created_at?->format('d M Y H:i') ?? '-'),
                ])
                ->columns(2),

            Section::make('Informasi Jadwal')
                ->schema([
                    Placeholder::make('trip_number')
                        ->label('Nomor Trip')
                        ->content(fn (Booking $record): string => $record->schedule?->busTrip?->trip_number ?? '-'),

                    Placeholder::make('class_type')
                        ->label('Kelas')
                        ->content(fn (Booking $record): string => $record->schedule?->busTrip?->class_type ?? '-'),

                    Placeholder::make('departure_date')
                        ->label('Tanggal Berangkat')
                        ->content(fn (Booking $record): string => $record->schedule?->departure_date?->format('d M Y') ?? '-'),

                    Placeholder::make('departure_time')
                        ->label('Jam Berangkat')
                        ->content(fn (Booking $record): string => substr((string) ($record->schedule?->departure_time ?? '-'), 0, 5)),
                ])
                ->columns(2),

            Section::make('Rute Perjalanan')
                ->schema([
                    Placeholder::make('boarding_stop')
                        ->label('Titik Naik')
                        ->content(fn (Booking $record): string => ($record->boardingStop?->city ?? '-') . ' — ' . ($record->boardingStop?->name ?? '')),

                    Placeholder::make('drop_stop')
                        ->label('Titik Turun')
                        ->content(fn (Booking $record): string => ($record->dropStop?->city ?? '-') . ' — ' . ($record->dropStop?->name ?? '')),
                ])
                ->columns(2),

            Section::make('Pembayaran')
                ->schema([
                    Placeholder::make('total_price')
                        ->label('Total Harga')
                        ->content(fn (Booking $record): string => 'Rp ' . number_format((float) $record->total_price, 0, ',', '.')),

                    Placeholder::make('discount_amount')
                        ->label('Diskon')
                        ->content(fn (Booking $record): string => 'Rp ' . number_format((float) $record->discount_amount, 0, ',', '.')),

                    Placeholder::make('promo_code_display')
                        ->label('Kode Promo')
                        ->content(fn (Booking $record): string => $record->promoCode?->code ?? 'Tidak ada'),
                ])
                ->columns(3),

            Section::make('Daftar Penumpang')
                ->schema([
                    Placeholder::make('passengers_list')
                        ->label('')
                        ->content(function (Booking $record): \Illuminate\Support\HtmlString {
                            $record->load(['passengers.seat']);
                            $passengers = $record->passengers;

                            if ($passengers->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Tidak ada data penumpang</p>');
                            }

                            $html = '<div class="overflow-x-auto"><table class="w-full text-sm">'
                                . '<thead><tr class="border-b border-gray-200 dark:border-gray-700">'
                                . '<th class="text-left p-2 font-medium">Nama</th>'
                                . '<th class="text-left p-2 font-medium">Gender</th>'
                                . '<th class="text-left p-2 font-medium">Telepon</th>'
                                . '<th class="text-left p-2 font-medium">Kursi</th>'
                                . '<th class="text-left p-2 font-medium">Status QR</th>'
                                . '<th class="text-left p-2 font-medium">Waktu Scan</th>'
                                . '</tr></thead><tbody>';

                            foreach ($passengers as $p) {
                                $qrBadge = match ($p->qr_status) {
                                    'active' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Aktif</span>',
                                    'used' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Sudah Scan</span>',
                                    'cancelled' => '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Dibatalkan</span>',
                                    default => '<span class="text-gray-400">-</span>',
                                };

                                $html .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                                    . '<td class="p-2 font-medium">' . e($p->name) . '</td>'
                                    . '<td class="p-2">' . e($p->gender ?? '-') . '</td>'
                                    . '<td class="p-2">' . e($p->phone ?? '-') . '</td>'
                                    . '<td class="p-2"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">' . e($p->seat?->label ?? '-') . '</span></td>'
                                    . '<td class="p-2">' . $qrBadge . '</td>'
                                    . '<td class="p-2">' . ($p->scanned_at?->format('d M Y H:i') ?? '-') . '</td>'
                                    . '</tr>';
                            }

                            $html .= '</tbody></table></div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'schedule.busTrip',
                'boardingStop',
                'dropStop',
                'user',
            ]))
            ->columns([

                TextColumn::make('booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Nama Pemesan')
                    ->searchable(),

                TextColumn::make('schedule.busTrip.trip_number')
                    ->label('Nomor Trip'),

                TextColumn::make('schedule.departure_date')
                    ->label('Tanggal Berangkat')
                    ->date('d M Y'),

                TextColumn::make('schedule.departure_time')
                    ->label('Jam Berangkat'),

                TextColumn::make('boardingStop.city')
                    ->label('Naik Dari'),

                TextColumn::make('dropStop.city')
                    ->label('Tujuan'),

                TextColumn::make('passengers_count')
                    ->label('Jumlah Penumpang')
                    ->counts('passengers'),

                TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pending',
                        'paid' => 'Lunas',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid', 'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                    }),

            ])

            ->filters([

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Lunas',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                    ]),

                SelectFilter::make('schedule_id')
                    ->label('Jadwal / Schedule')
                    ->options(function () {
                        return Schedule::with('busTrip')
                            ->where('status', 'active')
                            ->orderByDesc('departure_date')
                            ->orderBy('departure_time')
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => $s->departure_date->format('d M Y')
                                    . ' ' . substr($s->departure_time, 0, 5)
                                    . ' — ' . ($s->busTrip?->trip_number ?? '-')
                                    . ' (' . ($s->busTrip?->class_type ?? '-') . ')',
                            ]);
                    })
                    ->searchable()
                    ->preload(),

            ])

            ->actions([

                ViewAction::make(),

                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) =>
                        $record->status === 'paid'
                    )
                    ->action(function (Booking $record) {

                        $record->update([
                            'status' => 'confirmed',
                        ]);

                        Notification::make()
                            ->title('Booking berhasil dikonfirmasi')
                            ->success()
                            ->send();
                    }),

                Action::make('reschedule')
                    ->label('Ganti Jadwal')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Booking $record) =>
                        in_array($record->status, ['paid', 'confirmed'])
                    )
                    ->form([
                        Select::make('schedule_id')
                            ->label('Jadwal Baru')
                            ->options(function (Booking $record) {
                                return Schedule::with('busTrip')
                                    ->bookable()
                                    ->where('id', '!=', $record->schedule_id)
                                    ->orderBy('departure_date')
                                    ->orderBy('departure_time')
                                    ->get()
                                    ->mapWithKeys(fn ($schedule) => [
                                        $schedule->id => $schedule->departure_date->format('d M Y')
                                            . ' ' . substr($schedule->departure_time, 0, 5)
                                            . ' — ' . $schedule->busTrip->trip_number
                                            . ' (' . $schedule->busTrip->class_type . ')',
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Kursi penumpang akan dipindah ke label kursi yang sama di jadwal baru. QR code akan di-generate ulang.'),
                    ])
                    ->action(function (Booking $record, array $data) {
                        app(RescheduleService::class)->reschedule($record, (int) $data['schedule_id']);

                        Notification::make()
                            ->title('Jadwal booking berhasil diganti')
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) =>
                        in_array($record->status, ['pending', 'paid'])
                    )
                    ->action(function (Booking $record) {

                        app(BookingService::class)->cancel($record);

                        Notification::make()
                            ->title('Booking berhasil dibatalkan')
                            ->warning()
                            ->send();
                    }),

            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
