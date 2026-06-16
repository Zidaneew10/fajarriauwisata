<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filter Laporan Booking</x-slot>
            {{ $this->form }}
        </x-filament::section>

        @php $summary = $this->getSummary(); @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <x-heroicon-o-ticket class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Booking</p>
                        <p class="text-xl font-bold">{{ number_format($summary['total_bookings']) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <x-heroicon-o-banknotes class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Pendapatan</p>
                        <p class="text-xl font-bold">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <x-heroicon-o-users class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Penumpang</p>
                        <p class="text-xl font-bold">{{ number_format($summary['total_passengers']) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <x-heroicon-o-check-badge class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dikonfirmasi</p>
                        <p class="text-xl font-bold">{{ number_format($summary['confirmed_count']) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <x-heroicon-o-tag class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Diskon</p>
                        <p class="text-xl font-bold">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dibatalkan</p>
                        <p class="text-xl font-bold">{{ number_format($summary['cancelled_count']) }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
