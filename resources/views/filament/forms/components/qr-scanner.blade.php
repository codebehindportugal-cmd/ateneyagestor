<div
    x-data="qrScanner()"
    x-init="wire = $wire"
    class="col-span-full"
>
    {{-- Trigger button + hint --}}
    <div class="flex flex-wrap items-center gap-3">
        <button
            type="button"
            @click="openScanner()"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors"
            style="background-color:#7c3aed;"
            onmouseover="this.style.backgroundColor='#6d28d9'"
            onmouseout="this.style.backgroundColor='#7c3aed'"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4H4v8h8V4zM4 12v8h8v-8H4zm8 0h8V4h-8v8zm0 8h8v-8h-8v8z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 6h2v2H6V6zm10 0h2v2h-2V6zm0 10h2v2h-2v-2zM6 16h2v2H6v-2z"/>
            </svg>
            Ler QR Code AT
        </button>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Lê o QR code da fatura portuguesa para preencher os campos automaticamente
        </p>
    </div>

    {{-- ── Modal overlay ─────────────────────────────────────────────────────── --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display:none;position:fixed;inset:0;z-index:9999;overflow-y:auto;padding:5rem 1rem 2rem;"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0"
            style="background:rgba(0,0,0,0.6);"
            @click="closeScanner()"
        ></div>

        {{-- Panel --}}
        <div
            class="relative mx-auto w-full rounded-2xl shadow-2xl overflow-hidden"
            style="max-width:28rem;background:#fff;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b px-5 py-4" style="border-color:#e5e7eb;">
                <h2 class="text-base font-semibold" style="color:#111827;">Ler QR Code AT</h2>
                <button
                    type="button"
                    @click="closeScanner()"
                    class="rounded-lg p-1 transition-colors"
                    style="color:#9ca3af;"
                    onmouseover="this.style.color='#374151'"
                    onmouseout="this.style.color='#9ca3af'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Mode tabs --}}
            <div class="flex border-b" style="border-color:#e5e7eb;">
                <button
                    type="button"
                    @click="setMode('camera')"
                    class="flex-1 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                    :style="mode === 'camera'
                        ? 'border-color:#7c3aed;color:#7c3aed;'
                        : 'border-color:transparent;color:#6b7280;'"
                >
                    📷 Câmara
                </button>
                <button
                    type="button"
                    @click="setMode('file')"
                    class="flex-1 px-4 py-3 text-sm font-medium transition-colors border-b-2"
                    :style="mode === 'file'
                        ? 'border-color:#7c3aed;color:#7c3aed;'
                        : 'border-color:transparent;color:#6b7280;'"
                >
                    🖼 Upload de imagem
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-4">

                {{-- Camera mode --}}
                <div x-show="mode === 'camera'" class="space-y-2">
                    <div class="relative overflow-hidden rounded-xl" style="background:#000;aspect-ratio:1;">
                        <video
                            x-ref="video"
                            autoplay
                            playsinline
                            muted
                            class="w-full h-full object-cover"
                        ></video>
                        <canvas x-ref="canvas" style="display:none;"></canvas>
                        {{-- Corner guides --}}
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                            <div style="width:55%;height:55%;position:relative;">
                                <span style="position:absolute;top:-2px;left:-2px;width:20px;height:20px;border-top:3px solid #fff;border-left:3px solid #fff;border-radius:4px 0 0 0;"></span>
                                <span style="position:absolute;top:-2px;right:-2px;width:20px;height:20px;border-top:3px solid #fff;border-right:3px solid #fff;border-radius:0 4px 0 0;"></span>
                                <span style="position:absolute;bottom:-2px;left:-2px;width:20px;height:20px;border-bottom:3px solid #fff;border-left:3px solid #fff;border-radius:0 0 0 4px;"></span>
                                <span style="position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-bottom:3px solid #fff;border-right:3px solid #fff;border-radius:0 0 4px 0;"></span>
                            </div>
                        </div>
                    </div>
                    <p
                        x-show="!cameraActive && !error"
                        class="text-center text-sm"
                        style="color:#6b7280;"
                    >A iniciar câmara...</p>
                    <p
                        x-show="cameraActive"
                        class="text-center text-xs"
                        style="color:#9ca3af;"
                    >Aponta a câmara para o QR code da fatura</p>
                </div>

                {{-- File upload mode --}}
                <div x-show="mode === 'file'" class="space-y-2">
                    <label
                        class="flex flex-col items-center gap-3 cursor-pointer rounded-xl border-2 border-dashed p-8 transition-colors"
                        style="border-color:#d1d5db;"
                        onmouseover="this.style.borderColor='#7c3aed';this.style.background='#f5f3ff'"
                        onmouseout="this.style.borderColor='#d1d5db';this.style.background=''"
                    >
                        <svg class="h-10 w-10" style="color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div class="text-center">
                            <p class="text-sm font-medium" style="color:#374151;">Clica para selecionar imagem</p>
                            <p class="mt-1 text-xs" style="color:#9ca3af;">JPG, PNG ou WebP — screenshot ou foto da fatura</p>
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            style="display:none;"
                            @change="scanFile($event)"
                        >
                    </label>
                    <canvas x-ref="fileCanvas" style="display:none;"></canvas>
                </div>

                {{-- Error --}}
                <div
                    x-show="error"
                    x-transition
                    class="rounded-lg border p-3 space-y-1"
                    style="background:#fef2f2;border-color:#fecaca;"
                >
                    <p class="text-sm" style="color:#b91c1c;" x-text="error"></p>
                    <p class="text-xs" style="color:#ef4444;">Podes fechar este painel e preencher os campos manualmente.</p>
                </div>

                {{-- Result --}}
                <div
                    x-show="result"
                    x-transition
                    class="rounded-xl border p-4"
                    style="background:#f0fdf4;border-color:#bbf7d0;"
                >
                    <p class="flex items-center gap-2 text-sm font-semibold mb-3" style="color:#15803d;">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        QR code lido com sucesso
                    </p>
                    <dl class="space-y-1.5 text-xs mb-4">
                        <template x-if="result && result.invoice_number">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">Nº Fatura</dt>
                                <dd class="font-mono font-medium" style="color:#111827;" x-text="result.invoice_number"></dd>
                            </div>
                        </template>
                        <template x-if="result && result.date_formatted">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">Data</dt>
                                <dd style="color:#111827;" x-text="result.date_formatted"></dd>
                            </div>
                        </template>
                        <template x-if="result && result.amount">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">Total (c/ IVA)</dt>
                                <dd class="font-mono font-semibold" style="color:#111827;" x-text="result.amount + ' €'"></dd>
                            </div>
                        </template>
                        <template x-if="result && result.iva">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">Total IVA</dt>
                                <dd class="font-mono" style="color:#374151;" x-text="result.iva + ' €'"></dd>
                            </div>
                        </template>
                        <template x-if="result && result.nif">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">NIF Emitente</dt>
                                <dd class="font-mono" style="color:#111827;" x-text="result.nif"></dd>
                            </div>
                        </template>
                        <template x-if="result && result.doc_type">
                            <div class="flex gap-2">
                                <dt class="w-28 shrink-0" style="color:#6b7280;">Tipo</dt>
                                <dd style="color:#374151;" x-text="result.doc_type_label"></dd>
                            </div>
                        </template>
                    </dl>
                    <button
                        type="button"
                        @click="applyToForm()"
                        class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#7c3aed;"
                        onmouseover="this.style.backgroundColor='#6d28d9'"
                        onmouseout="this.style.backgroundColor='#7c3aed'"
                    >
                        Aplicar ao formulário
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- jsQR loaded lazily on first scanner open --}}
<script>
function qrScanner() {
    return {
        wire: null,
        isOpen: false,
        mode: 'camera',
        cameraActive: false,
        stream: null,
        scanInterval: null,
        result: null,
        error: null,

        DOC_TYPES: {
            FT: 'Fatura', FS: 'Fatura Simplificada', FR: 'Fatura-Recibo',
            ND: 'Nota de Débito', NC: 'Nota de Crédito',
            VD: 'Venda a Dinheiro', TV: 'Talão de Venda',
            TD: 'Talão de Devolução', RC: 'Recibo',
            RP: 'Recibo de Prémio', RE: 'Recibo de Encargos',
            CS: 'Imputação a Co-Seguradoras', LD: 'Imputação a Líder',
            RA: 'Recibo de Alarme',
        },

        loadJsQR() {
            if (window.jsQR) return Promise.resolve();
            return new Promise((resolve) => {
                if (document.getElementById('jsqr-script')) {
                    const wait = setInterval(() => {
                        if (window.jsQR) { clearInterval(wait); resolve(); }
                    }, 100);
                    return;
                }
                const s = document.createElement('script');
                s.id = 'jsqr-script';
                s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
                s.onload = resolve;
                document.head.appendChild(s);
            });
        },

        openScanner() {
            this.result = null;
            this.error = null;
            this.isOpen = true;
            this.loadJsQR();
            if (this.mode === 'camera') {
                this.$nextTick(() => this.startCamera());
            }
        },

        closeScanner() {
            this.stopCamera();
            this.isOpen = false;
        },

        setMode(mode) {
            if (mode === this.mode) return;
            this.stopCamera();
            this.result = null;
            this.error = null;
            this.mode = mode;
            if (mode === 'camera') {
                this.$nextTick(() => this.startCamera());
            }
        },

        async startCamera() {
            this.cameraActive = false;
            this.error = null;
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }
                });
                const video = this.$refs.video;
                video.srcObject = this.stream;
                await video.play();
                this.cameraActive = true;
                this.startFrameScan();
            } catch (err) {
                const msgs = {
                    NotAllowedError: 'Permissão de câmara negada. Usa o upload de imagem.',
                    NotFoundError: 'Câmara não encontrada neste dispositivo. Usa o upload de imagem.',
                };
                this.error = msgs[err.name] || ('Câmara não disponível (' + err.name + '). Usa o upload de imagem.');
                this.mode = 'file';
            }
        },

        stopCamera() {
            clearInterval(this.scanInterval);
            this.scanInterval = null;
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            this.cameraActive = false;
        },

        startFrameScan() {
            this.scanInterval = setInterval(() => {
                if (!window.jsQR) return;
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                if (!video || !canvas) return;
                if (video.readyState < video.HAVE_ENOUGH_DATA) return;

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);
                const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
                if (code) {
                    this.stopCamera();
                    this.parseQR(code.data);
                }
            }, 250);
        },

        scanFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.error = null;
            this.result = null;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = this.$refs.fileCanvas;
                    canvas.width = img.width;
                    canvas.height = img.height;
                    canvas.getContext('2d').drawImage(img, 0, 0);
                    const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);

                    if (!window.jsQR) {
                        this.error = 'Biblioteca QR ainda a carregar. Aguarda um momento e tenta novamente.';
                        return;
                    }
                    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'attemptBoth' });
                    if (code) {
                        this.parseQR(code.data);
                    } else {
                        this.error = 'Nenhum QR code encontrado na imagem. Verifica se a imagem está nítida e o QR code está bem visível.';
                    }
                };
                img.onerror = () => { this.error = 'Erro ao carregar a imagem.'; };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        parseQR(raw) {
            // Parse AT QR code: KEY:VALUE pairs separated by *
            const fields = {};
            raw.split('*').forEach(pair => {
                const idx = pair.indexOf(':');
                if (idx !== -1) {
                    fields[pair.substring(0, idx).trim()] = pair.substring(idx + 1).trim();
                }
            });

            // Must have NIF emitente (A), date (F), and total with taxes (O)
            if (!fields['A'] || !fields['F'] || !fields['O']) {
                this.error = 'QR code não reconhecido como fatura AT portuguesa (campos A, F ou O em falta). Preenche manualmente.';
                return;
            }

            // Date: YYYYMMDD → YYYY-MM-DD (Filament DatePicker ISO format)
            const rawDate = fields['F'];
            let date = null, dateFormatted = null;
            if (rawDate && rawDate.length === 8) {
                date          = rawDate.slice(0, 4) + '-' + rawDate.slice(4, 6) + '-' + rawDate.slice(6, 8);
                dateFormatted = rawDate.slice(6, 8) + '/' + rawDate.slice(4, 6) + '/' + rawDate.slice(0, 4);
            }

            const docType = fields['D'] || null;

            this.result = {
                nif:           fields['A'] || null,
                invoice_number: fields['G'] || null,
                date,
                date_formatted: dateFormatted,
                atcud:         fields['H'] || null,
                amount:        fields['O'] ? parseFloat(fields['O']).toFixed(2) : null,
                iva:           fields['N'] ? parseFloat(fields['N']).toFixed(2) : null,
                doc_type:      docType,
                doc_type_label: docType ? (this.DOC_TYPES[docType] || docType) : null,
            };
        },

        applyToForm() {
            if (!this.result || !this.wire) return;

            // Fields map to Livewire data.* (Filament form state uses 'data' prefix)
            if (this.result.invoice_number) this.wire.set('data.invoice_number', this.result.invoice_number);
            if (this.result.date)           this.wire.set('data.date', this.result.date);
            if (this.result.amount)         this.wire.set('data.amount_cents', this.result.amount);
            if (this.result.iva)            this.wire.set('data.iva_cents', this.result.iva);
            if (this.result.nif)            this.wire.set('data.supplier_nif', this.result.nif);
            if (this.result.atcud)          this.wire.set('data.atcud', this.result.atcud);

            this.closeScanner();
        },
    };
}
</script>
