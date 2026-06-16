<x-filament-panels::page>
    <div
        x-data="qrScanner(@this)"
        x-init="init()"
        class="max-w-2xl mx-auto space-y-6"
    >
        {{-- Scanner Area --}}
        <div x-show="isScanning" x-transition class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                <x-heroicon-o-camera class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Kamera Scanner</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Arahkan kamera ke QR Code penumpang</p>
                            </div>
                        </div>
                        {{-- Camera Switch Button --}}
                        <button
                            x-show="cameras.length > 1"
                            @click="switchCamera()"
                            class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200"
                            title="Ganti Kamera"
                        >
                            <x-heroicon-o-arrow-path class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                        </button>
                    </div>
                    {{-- Camera Name Indicator --}}
                    <div x-show="cameras.length > 1" class="mt-2">
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-text="'Kamera: ' + currentCameraLabel"></p>
                    </div>
                </div>

                <div class="relative">
                    <div id="qr-reader" class="w-full" style="min-height: 350px;"></div>
                    {{-- Scan Frame Overlay --}}
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center" x-show="!isLoading && scannerReady">
                        <div class="w-56 h-56 relative">
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-amber-500 rounded-tl-lg"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-amber-500 rounded-tr-lg"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-amber-500 rounded-bl-lg"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-amber-500 rounded-br-lg"></div>
                            {{-- Scanning animation line --}}
                            <div class="absolute left-2 right-2 h-0.5 bg-amber-500/80 animate-scan-line"></div>
                        </div>
                    </div>
                    {{-- Loading overlay --}}
                    <div x-show="isLoading" x-transition class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-10 backdrop-blur-sm">
                        <div class="flex flex-col items-center gap-3">
                            <div class="relative">
                                <svg class="animate-spin h-10 w-10 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Memverifikasi QR Code...</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual Input --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Input Manual</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Atau paste data QR code secara manual</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <input
                        type="text"
                        x-model="manualInput"
                        placeholder="Paste data QR code di sini..."
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm"
                        @keydown.enter="submitManual()"
                    />
                    <button
                        @click="submitManual()"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2"
                    >
                        <x-heroicon-o-paper-airplane class="w-4 h-4" />
                        Kirim
                    </button>
                </div>
            </div>
        </div>

        {{-- Scan Result --}}
        <div x-show="!isScanning" x-transition x-cloak class="space-y-4">

            {{-- SUCCESS Result --}}
            <template x-if="scanResult && scanResult.valid">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 border-green-400 dark:border-green-500 overflow-hidden animate-result-appear">
                    {{-- Success Header --}}
                    <div class="bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 p-8 text-center relative overflow-hidden">
                        {{-- Background decoration --}}
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-4 left-4 w-16 h-16 rounded-full border-4 border-white"></div>
                            <div class="absolute bottom-4 right-4 w-24 h-24 rounded-full border-4 border-white"></div>
                            <div class="absolute top-1/2 right-8 w-8 h-8 rounded-full bg-white"></div>
                        </div>
                        {{-- Animated Check --}}
                        <div class="relative inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4 animate-success-bounce">
                            <div class="w-16 h-16 bg-white/30 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-white animate-check-draw" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-white" x-text="scanResult.message"></h2>
                        <p class="text-green-100 text-sm mt-2">Verifikasi boarding berhasil</p>
                    </div>

                    {{-- Passenger Data --}}
                    <div class="p-6 space-y-4" x-show="scanResult.data">
                        {{-- Trip Info Bar --}}
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700/50">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-truck class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                    <span class="font-bold text-amber-800 dark:text-amber-200" x-text="scanResult.data?.trip_number ?? '-'"></span>
                                    <span class="px-2 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-full text-xs font-medium" x-text="scanResult.data?.class_type ?? '-'"></span>
                                </div>
                                <div class="text-sm text-amber-700 dark:text-amber-300 font-medium" x-text="scanResult.data?.route ?? '-'"></div>
                            </div>
                        </div>

                        {{-- Main Data Grid --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 col-span-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Penumpang</p>
                                </div>
                                <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.passenger_name ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-squares-2x2 class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kursi</p>
                                </div>
                                <p class="text-xl font-bold text-amber-600 dark:text-amber-400" x-text="scanResult.data?.seat ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-document-text class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode Booking</p>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.booking_code ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-shield-check class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Booking</p>
                                </div>
                                <p class="font-bold"
                                   :class="scanResult.data?.booking_status === 'confirmed' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400'"
                                   x-text="scanResult.data?.booking_status === 'confirmed' ? '✅ Dikonfirmasi' : (scanResult.data?.booking_status === 'paid' ? '💰 Lunas' : (scanResult.data?.booking_status ?? '-'))"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keberangkatan</p>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.departure ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 col-span-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Naik / Turun</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.boarding ?? '-'"></span>
                                    <div class="flex items-center gap-1 text-gray-400">
                                        <div class="w-6 h-px bg-gray-300 dark:bg-gray-600"></div>
                                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                                        <div class="w-6 h-px bg-gray-300 dark:bg-gray-600"></div>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.drop ?? '-'"></span>
                                </div>
                            </div>
                        </div>

                        <template x-if="scanResult.data?.scanned_at">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 text-center border border-green-200 dark:border-green-800">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <x-heroicon-o-clock class="w-4 h-4 text-green-500" />
                                    <p class="text-xs text-green-600 dark:text-green-400 uppercase tracking-wider">Waktu Scan</p>
                                </div>
                                <p class="font-bold text-green-700 dark:text-green-300 text-lg" x-text="scanResult.data.scanned_at"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- ERROR Result --}}
            <template x-if="scanResult && !scanResult.valid">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 border-red-400 dark:border-red-500 overflow-hidden animate-result-appear">
                    {{-- Error Header --}}
                    <div class="bg-gradient-to-br from-red-500 via-rose-500 to-pink-500 p-8 text-center relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-4 right-4 w-16 h-16 rounded-full border-4 border-white"></div>
                            <div class="absolute bottom-4 left-4 w-24 h-24 rounded-full border-4 border-white"></div>
                        </div>
                        <div class="relative inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4 animate-error-shake">
                            <div class="w-16 h-16 bg-white/30 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-white" x-text="scanResult.message"></h2>
                        <p x-show="scanResult.status" class="text-red-100 text-sm mt-2">
                            Status: <span class="font-medium" x-text="scanResult.status === 'already_used' ? 'Sudah Digunakan' : (scanResult.status === 'cancelled' ? 'Dibatalkan' : (scanResult.status ?? ''))"></span>
                        </p>
                    </div>

                    {{-- Data if available --}}
                    <div class="p-6 space-y-3" x-show="scanResult.data">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Penumpang</p>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.passenger_name ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-document-text class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kode Booking</p>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.booking_code ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-qr-code class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status QR</p>
                                </div>
                                <p class="font-bold" :class="scanResult.data?.qr_status === 'used' ? 'text-amber-600' : 'text-red-600'" x-text="scanResult.data?.qr_status === 'used' ? 'Sudah Digunakan' : (scanResult.data?.qr_status === 'cancelled' ? 'Dibatalkan' : (scanResult.data?.qr_status ?? '-'))"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-shield-check class="w-4 h-4 text-gray-400" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Booking</p>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="scanResult.data?.booking_status ?? '-'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Scan Again Button --}}
            <div class="flex justify-center pt-2">
                <button
                    @click="resetAndScanAgain()"
                    class="group px-8 py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-2xl text-sm font-bold transition-all duration-300 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] flex items-center gap-3"
                >
                    <x-heroicon-o-qr-code class="w-6 h-6 group-hover:rotate-12 transition-transform duration-300" />
                    Scan QR Berikutnya
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('qrScanner', (livewireComponent) => ({
                isScanning: true,
                isLoading: false,
                scannerReady: false,
                manualInput: '',
                scanResult: null,
                scanner: null,
                cameras: [],
                currentCameraIndex: 0,
                currentCameraLabel: 'Memuat...',

                init() {
                    this.$watch('isScanning', (value) => {
                        if (value) {
                            this.$nextTick(() => this.startScanner());
                        } else {
                            this.stopScanner();
                        }
                    });

                    // Listen for Livewire updates
                    livewireComponent.on('scanCompleted', (result) => {
                        this.scanResult = result[0];
                        this.isScanning = false;
                        this.isLoading = false;
                    });

                    this.$nextTick(() => this.initCameras());
                },

                async initCameras() {
                    try {
                        const devices = await Html5Qrcode.getCameras();
                        this.cameras = devices || [];

                        if (this.cameras.length > 0) {
                            // Prefer back camera
                            const backIdx = this.cameras.findIndex(c =>
                                /back|rear|environment/i.test(c.label)
                            );
                            this.currentCameraIndex = backIdx >= 0 ? backIdx : 0;
                            this.currentCameraLabel = this.cameras[this.currentCameraIndex]?.label || 'Kamera ' + (this.currentCameraIndex + 1);
                            this.startScanner();
                        } else {
                            this.showCameraError();
                        }
                    } catch (err) {
                        console.error('Camera init error:', err);
                        this.showCameraError();
                    }
                },

                async switchCamera() {
                    if (this.cameras.length <= 1) return;

                    await this.stopScanner();
                    this.scannerReady = false;

                    this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
                    this.currentCameraLabel = this.cameras[this.currentCameraIndex]?.label || 'Kamera ' + (this.currentCameraIndex + 1);

                    this.$nextTick(() => this.startScanner());
                },

                async startScanner() {
                    const readerEl = document.getElementById('qr-reader');
                    if (!readerEl) return;

                    // Cleanup previous instance
                    if (this.scanner) {
                        try { await this.scanner.stop(); } catch(e) {}
                        try { await this.scanner.clear(); } catch(e) {}
                        this.scanner = null;
                    }

                    readerEl.innerHTML = '';

                    try {
                        this.scanner = new Html5Qrcode('qr-reader', {
                            verbose: false,
                        });

                        const cameraId = this.cameras[this.currentCameraIndex]?.id;
                        const startConfig = cameraId
                            ? cameraId
                            : { facingMode: 'environment' };

                        await this.scanner.start(
                            startConfig,
                            {
                                fps: 10,
                                qrbox: { width: 250, height: 250 },
                                aspectRatio: 1.0,
                                showTorchButtonIfSupported: false,
                                showZoomSliderIfSupported: false,
                            },
                            (decodedText) => {
                                this.onScanSuccess(decodedText);
                            },
                            (errorMessage) => {
                                // ignore scan errors (no QR found in frame)
                            }
                        );

                        this.scannerReady = true;

                        // Hide all built-in dashboard elements
                        this.$nextTick(() => {
                            const dashboard = document.getElementById('qr-reader__dashboard');
                            if (dashboard) dashboard.style.display = 'none';
                            const headerMsg = document.getElementById('qr-reader__header_message');
                            if (headerMsg) headerMsg.style.display = 'none';
                        });

                    } catch (err) {
                        console.error('Camera error:', err);
                        this.showCameraError();
                    }
                },

                showCameraError() {
                    const readerEl = document.getElementById('qr-reader');
                    if (!readerEl) return;
                    readerEl.innerHTML = '<div class="flex flex-col items-center justify-center p-8 text-center">' +
                        '<div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">' +
                        '<svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>' +
                        '</div>' +
                        '<p class="text-gray-500 dark:text-gray-400 font-semibold text-lg">Kamera tidak tersedia</p>' +
                        '<p class="text-sm text-gray-400 dark:text-gray-500 mt-2 max-w-sm">Pastikan browser memiliki izin akses kamera, atau gunakan input manual di bawah</p>' +
                        '</div>';
                },

                async stopScanner() {
                    this.scannerReady = false;
                    if (this.scanner) {
                        try { await this.scanner.stop(); } catch(e) {}
                        try { await this.scanner.clear(); } catch(e) {}
                        this.scanner = null;
                    }
                },

                onScanSuccess(decodedText) {
                    if (this.isLoading) return; // prevent double scans

                    this.isLoading = true;

                    // Play success beep
                    this.playBeep(1200, 0.15);

                    // Call Livewire method
                    livewireComponent.call('scanQrCode', decodedText).then(() => {
                        // Get the result from Livewire component
                        this.scanResult = livewireComponent.get('scanResult');
                        this.isScanning = livewireComponent.get('isScanning');
                        this.isLoading = false;

                        // Play result sound
                        if (this.scanResult?.valid) {
                            this.playBeep(880, 0.1);
                            setTimeout(() => this.playBeep(1320, 0.15), 120);
                        } else {
                            this.playBeep(300, 0.3);
                        }
                    }).catch(() => {
                        this.isLoading = false;
                        this.playBeep(300, 0.3);
                    });
                },

                playBeep(frequency, duration) {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.frequency.value = frequency;
                        gain.gain.value = 0.3;
                        osc.start();
                        osc.stop(ctx.currentTime + duration);
                    } catch(e) {}
                },

                submitManual() {
                    if (!this.manualInput.trim()) return;
                    this.onScanSuccess(this.manualInput.trim());
                    this.manualInput = '';
                },

                resetAndScanAgain() {
                    this.scanResult = null;
                    this.isScanning = true;
                    this.isLoading = false;
                    this.scannerReady = false;
                    livewireComponent.call('resetScanner');
                },

                destroy() {
                    this.stopScanner();
                }
            }));
        });
    </script>
    @endpush

    @push('styles')
    <style>
        /* Hide html5-qrcode built-in UI elements */
        #qr-reader {
            border: none !important;
            background: transparent !important;
        }
        #qr-reader video {
            border-radius: 0 !important;
            object-fit: cover !important;
        }
        #qr-reader__scan_region {
            min-height: 300px;
        }
        #qr-reader__dashboard {
            display: none !important;
        }
        #qr-reader__header_message {
            display: none !important;
        }
        #qr-reader__status_span {
            display: none !important;
        }
        #qr-reader__camera_selection {
            display: none !important;
        }
        #qr-reader img {
            display: none !important;
        }

        /* Scan line animation */
        @keyframes scan-line {
            0% { top: 8px; }
            50% { top: calc(100% - 8px); }
            100% { top: 8px; }
        }
        .animate-scan-line {
            animation: scan-line 2.5s ease-in-out infinite;
        }

        /* Result card appear animation */
        @keyframes result-appear {
            0% { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-result-appear {
            animation: result-appear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Success bounce animation */
        @keyframes success-bounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
        .animate-success-bounce {
            animation: success-bounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Check draw animation */
        @keyframes check-draw {
            0% { stroke-dashoffset: 30; opacity: 0; }
            50% { opacity: 1; }
            100% { stroke-dashoffset: 0; opacity: 1; }
        }
        .animate-check-draw {
            stroke-dasharray: 30;
            animation: check-draw 0.5s ease-out 0.3s forwards;
            opacity: 0;
        }

        /* Error shake animation */
        @keyframes error-shake {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            60% { transform: scale(1) rotate(-5deg); }
            70% { transform: rotate(5deg); }
            80% { transform: rotate(-3deg); }
            90% { transform: rotate(2deg); }
            100% { transform: rotate(0); }
        }
        .animate-error-shake {
            animation: error-shake 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        [x-cloak] { display: none !important; }
    </style>
    @endpush
</x-filament-panels::page>
