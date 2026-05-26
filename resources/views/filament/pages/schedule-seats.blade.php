<x-filament-panels::page>
    <div class="p-4 bg-white rounded-xl shadow">
        <div class="mb-4 flex gap-6 text-sm">
            <span class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-blue-100 border border-blue-400 inline-block"></span> Tersedia
            </span>
            <span class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-gray-300 inline-block"></span> Terisi
            </span>
        </div>

        <div class="grid gap-2" style="grid-template-columns: repeat({{ count($this->record->busTrip->getSeatColumns()) + 1 }}, minmax(0, 1fr))">
            @foreach ($this->record->seats->groupBy('row') as $row => $seats)
                <div class="text-center text-xs text-gray-400 flex items-center justify-center font-bold">{{ $row }}</div>
                @foreach ($seats as $seat)
                    <div class="p-2 rounded text-center text-xs font-bold
                        {{ $seat->is_available ? 'bg-blue-100 border border-blue-400 text-blue-700' : 'bg-gray-300 text-gray-500' }}">
                        {{ $seat->label }}
                    </div>
                @endforeach
            @endforeach
        </div>

        <div class="mt-4 text-sm text-gray-500">
            Tersedia: {{ $this->record->seats->where('is_available', true)->count() }}
            / Total: {{ $this->record->busTrip->capacity }}
        </div>
    </div>
</x-filament-panels::page>
