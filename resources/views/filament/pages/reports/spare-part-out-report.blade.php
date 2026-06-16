<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filter Laporan</x-slot>
            {{ $this->form }}
        </x-filament::section>

        @php $summary = $this->getSummary(); @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
                <p class="text-2xl font-bold">{{ number_format($summary['total_records']) }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Item Keluar</p>
                <p class="text-2xl font-bold">{{ number_format($summary['total_items']) }}</p>
            </x-filament::section>
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Bus Terlibat</p>
                <p class="text-2xl font-bold">{{ number_format($summary['total_buses']) }}</p>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
