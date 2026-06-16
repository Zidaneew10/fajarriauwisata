<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filter Laporan</x-slot>
            {{ $this->form }}
        </x-filament::section>

        @php $summary = $this->getSummary(); @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Reservasi</p>
                <p class="text-2xl font-bold">{{ number_format($summary['total_reservations']) }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pendapatan</p>
                <p class="text-2xl font-bold">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Penumpang</p>
                <p class="text-2xl font-bold">{{ number_format($summary['total_passengers']) }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Terkonfirmasi</p>
                <p class="text-2xl font-bold">{{ number_format($summary['confirmed_count']) }}</p>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
