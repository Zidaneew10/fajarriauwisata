<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $chart = $this->chartData;
            $monthDate = $chart['monthDate'];
            $daysInMonth = $chart['daysInMonth'];
            $chartData = $chart['data'];
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-lg overflow-x-auto">
            <div class="flex justify-between items-center mb-8">
                <x-filament::button wire:click="previousMonth" color="gray" size="sm">
                    &laquo; Bulan Sebelumnya
                </x-filament::button>

                <h1 class="text-4xl font-bold text-center uppercase tracking-widest text-gray-800 dark:text-gray-100">
                    {{ $monthDate->translatedFormat('F Y') }}
                </h1>

                <x-filament::button wire:click="nextMonth" color="gray" size="sm">
                    Bulan Berikutnya &raquo;
                </x-filament::button>
            </div>
            
            <div class="min-w-max space-y-2">
                @foreach($chartData as $index => $row)
                    @php $iteration = $loop->iteration; @endphp
                    <div class="flex items-center text-sm border-b border-gray-100 dark:border-gray-800 pb-2">
                        <!-- Bus Info -->
                        <div class="w-48 flex-shrink-0 flex items-center pr-4">
                            <span class="font-bold w-6">{{ $iteration }}.</span>
                            <span class="font-bold uppercase whitespace-nowrap overflow-hidden text-ellipsis" title="{{ $row['bus']->plate_number }} - {{ $row['bus']->class_type }}">
                                {{ $row['bus']->plate_number }}
                            </span>
                        </div>
                        
                        <!-- Days -->
                        <div class="flex flex-1">
                            @for($day = 1; $day <= $daysInMonth; $day++)
                                @php 
                                    $cell = $row['schedule'][$day]; 
                                @endphp
                                
                                @if($cell)
                                    @php
                                        // Determine rounding
                                        $roundedClass = '';
                                        if ($cell['isStart'] && $cell['isEnd']) {
                                            $roundedClass = 'rounded-md';
                                        } elseif ($cell['isStart']) {
                                            $roundedClass = 'rounded-l-md';
                                        } elseif ($cell['isEnd']) {
                                            $roundedClass = 'rounded-r-md';
                                        }
                                        
                                        $bgStyle = 'background-color: ' . $cell['color'] . ';';
                                        $textClass = 'text-white font-semibold shadow-sm';
                                        
                                        if ($cell['color'] === '#eab308') {
                                            $textClass = 'text-black font-semibold shadow-sm';
                                        }
                                    @endphp
                                    <div class="w-8 h-8 flex items-center justify-center cursor-pointer group relative {{ $textClass }} {{ $roundedClass }}" style="{{ $bgStyle }}" title="{{ $cell['reservation']->customer_name }} ({{ ucfirst($cell['reservation']->payment_status) }})">
                                        {{ $day }}
                                        
                                        <!-- Tooltip -->
                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block w-48 bg-gray-900 text-white text-xs rounded p-3 z-50 shadow-xl whitespace-normal font-normal text-left">
                                            <p class="font-bold text-sm mb-1">{{ $cell['reservation']->customer_name }}</p>
                                            <p class="mb-1"><span class="text-gray-400">Tujuan:</span> {{ $cell['reservation']->destination }}</p>
                                            <p class="mb-1"><span class="text-gray-400">Tanggal:</span> {{ $cell['reservation']->departure_date->format('d M Y') }} - {{ $cell['reservation']->return_date ? $cell['reservation']->return_date->format('d M Y') : $cell['reservation']->departure_date->format('d M Y') }}</p>
                                            <p><span class="text-gray-400">Pembayaran:</span> {{ strtoupper($cell['reservation']->payment_status) }}</p>
                                            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-8 h-8 flex items-center justify-center text-gray-500 dark:text-gray-400 font-medium">
                                        {{ $day }}
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex items-center justify-center gap-6 text-sm font-medium">
                <div class="flex items-center gap-2"><div class="w-4 h-4 rounded" style="background-color: #ef4444;"></div> Belum Lunas (Unpaid)</div>
                <div class="flex items-center gap-2"><div class="w-4 h-4 rounded" style="background-color: #eab308;"></div> Uang Muka (DP)</div>
                <div class="flex items-center gap-2"><div class="w-4 h-4 rounded" style="background-color: #22c55e;"></div> Lunas (Paid)</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
