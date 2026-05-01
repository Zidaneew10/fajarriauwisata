<div class="p-4 space-y-2">
    @forelse($scheduleBuses as $bus)
        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border">
            <div>
                <span class="font-semibold">{{ $bus->bus_code }}</span>
                <span class="text-sm text-gray-500 ml-2">{{ $bus->busClass->class_type }}</span>
            </div>
            <div class="text-sm text-gray-600">
                Rp{{ number_format($bus->busClass->price, 0, ',', '.') }}
                · {{ $bus->busClass->capacity }} kursi
            </div>
        </div>
    @empty
        <p class="text-gray-400 text-center py-4">Belum ada bus yang ditugaskan.</p>
    @endforelse
</div>
