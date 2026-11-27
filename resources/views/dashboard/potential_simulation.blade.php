<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Simulasikan usaha armada parcel Amba Parcel: pilih armada, model usaha, pendanaan, lalu lihat estimasi paket, profit, dan balik modal.">
    <title>{{ $pageTitle }}</title>

    {{-- Tandai JS aktif lebih awal agar elemen reveal tidak berkedip. --}}
    <script>document.documentElement.classList.add('js');</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#eef6f5', 100: '#d6ebe8', 200: '#b0d7d1', 700: '#176a63', 800: '#0f4f4a', 900: '#0a3935' },
                        accent: { 300: '#f9c668', 400: '#f5a524', 500: '#e8890c', 600: '#b45f06', 700: '#8f4a05' }
                    }
                }
            }
        };
    </script>

    {{-- Alpine.js, GSAP + ScrollTrigger, Lenis (semua defer, urutan dipertahankan). --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Lenis */
        html.lenis, html.lenis body { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
        .lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }
        .lenis.lenis-stopped { overflow: clip; }
        .lenis.lenis-scrolling iframe { pointer-events: none; }
        html:not(.lenis) { scroll-behavior: smooth; }

        /* Judul per kata (GSAP SplitWords) */
        .split-word { display: inline-block; overflow: hidden; vertical-align: top; padding-bottom: .14em; margin-bottom: -.14em; }
        .split-inner { display: inline-block; will-change: transform; }

        /* Keadaan awal animasi masuk (Motion). Dilepas jika Motion gagal dimuat. */
        .js [data-reveal], .js [data-reveal-item] { opacity: 0; }
        .motion-fallback [data-reveal], .motion-fallback [data-reveal-item] { opacity: 1 !important; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; scroll-behavior: auto !important; }
            .js [data-reveal], .js [data-reveal-item] { opacity: 1; }
        }
    </style>
</head>

@php
    // Format Rupiah ringkas untuk render awal di server (Alpine memakai rupiah() di sisi klien).
    $juta = static function (int|float $n): string {
        $v = $n / 1000000;
        return 'Rp ' . rtrim(rtrim(number_format($v, 1, ',', '.'), '0'), ',') . ' Juta';
    };
@endphp

<body class="min-h-screen bg-gradient-to-b from-slate-50 via-brand-50 to-white font-sans text-slate-700 antialiased"
      x-data="simulatorPage()">
      
      

{{-- ===================== NAVBAR ===================== --}}
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/85 backdrop-blur">
    
    <x-navbar></x-navbar>
</header>

{{-- ===================== SIMULASI & FAQ ===================== --}}
<main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
    <div class="grid gap-8 lg:grid-cols-5">

        {{-- Kolom kiri: pengantar + ringkasan + asumsi --}}
        <section class="lg:col-span-2 lg:self-start lg:sticky lg:top-24" aria-labelledby="judul-halaman">
            <h1 id="judul-halaman" data-split class="text-4xl font-extrabold leading-[1.1] text-brand-900 sm:text-5xl">
                Simulasi Usaha Armada Parcel <br class="hidden sm:block">&amp; FAQ
            </h1>
            <p class="mt-4 max-w-md text-lg text-slate-600" data-reveal>
                Pilih armada, tentukan skema usaha, lalu lihat estimasi paket, profit, dan balik modal secara langsung.
            </p>

            <a href="#kalkulator" data-hover="pop" data-reveal
               class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/25 transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                Mulai Simulasi
            </a>

            <div class="mt-8 space-y-4" data-reveal-group>
                {{-- Ringkasan cepat (reaktif) --}}
                <div data-reveal-item data-hover class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm" aria-live="polite">
                    <h2 class="text-sm font-semibold text-brand-900">Ringkasan cepat</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        <span x-text="units"></span> unit <span x-text="fleet.name"></span> &middot; <span x-text="model.short"></span>
                    </p>
                    <dl class="mt-3 grid grid-cols-3 gap-3">
                        <div>
                            <dt class="text-[11px] text-slate-500">Net profit / bulan</dt>
                            <dd class="mt-0.5 text-sm font-bold" x-bind:class="r.net >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="rupiah(r.net)"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-slate-500">Estimasi BEP</dt>
                            <dd class="mt-0.5 text-sm font-bold text-brand-900" x-text="r.bepText"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-slate-500">Paket / bulan</dt>
                            <dd class="mt-0.5 text-sm font-bold text-brand-900" x-text="num(r.parcelsMonth)"></dd>
                        </div>
                    </dl>
                </div>

                {{-- Asumsi model --}}
                <div data-reveal-item data-hover class="rounded-2xl border border-brand-100 bg-white/70 p-5">
                    <h2 class="text-sm font-semibold text-brand-900">Asumsi model (ilustratif)</h2>
                    <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                        <li>&bull; Acuan: utilisasi <b x-text="base.base_utilization"></b>% dan <b x-text="base.base_days"></b> hari operasi per bulan</li>
                        <li>&bull; Biaya tetap <b x-text="Math.round(mgmt.fixed_cost_share * 100)"></b>% dari biaya operasional pada skema <span x-text="mgmt.short"></span></li>
                        <li>&bull; Biaya mencakup BBM, kurir/driver, servis, asuransi, pajak, dan fee manajemen</li>
                        <li>&bull; Nilai sisa unit tidak dihitung (konservatif)</li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- Kolom kanan: simulator + FAQ --}}
        <div class="space-y-6 lg:col-span-3">

            {{-- ---------- Kartu simulator ---------- --}}
            <section id="kalkulator" data-reveal class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-md sm:p-6"
                     aria-labelledby="judul-simulasi">
                <h2 id="judul-simulasi" data-split class="text-xl font-bold text-brand-900">Simulasi Potensi Usaha Armada</h2>

                {{-- ===== Pilihan armada ===== --}}
                <h3 data-split class="mt-6 text-base font-bold text-brand-900">Pilih armada</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Pilihan armada parcel" data-reveal-group>
                    @foreach ($fleets as $f)
                        <label class="relative block cursor-pointer" data-reveal-item data-hover>
                            <input type="radio" name="fleet" value="{{ $f['key'] }}" x-model="fleetKey" class="peer sr-only">
                            <span class="pointer-events-none absolute right-3 top-3 hidden h-6 w-6 items-center justify-center rounded-full bg-brand-900 text-white peer-checked:flex" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                            </span>
                            <div class="h-full rounded-2xl border border-slate-200 bg-white p-4 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-900 peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                <p class="pr-8 text-sm font-bold text-brand-900">{{ $f['name'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $f['type'] }} &middot; {{ $f['capacity'] }} &middot; {{ $f['volume'] }}</p>
                                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Kapasitas</dt>
                                        <dd class="font-semibold text-slate-700">±{{ number_format($f['parcels_per_day'], 0, ',', '.') }} paket/hari</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-slate-500">Investasi</dt>
                                        <dd class="font-semibold text-brand-900">{{ $juta($f['price']) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Spesifikasi armada terpilih --}}
                <div class="mt-4 rounded-2xl border border-brand-100 bg-brand-50/60 p-4" aria-live="polite">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h4 class="text-sm font-bold text-brand-900" x-text="fleet.name"></h4>
                        <span class="text-[11px] text-slate-500">Spesifikasi kunci per unit</span>
                    </div>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600" x-text="fleet.desc"></p>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-[11px] text-slate-500">Kapasitas berat</dt><dd class="font-semibold text-brand-900" x-text="fleet.capacity"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Volume / kubikasi</dt><dd class="font-semibold text-brand-900" x-text="fleet.volume"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Kapasitas paket / hari</dt><dd class="font-semibold text-brand-900"><span x-text="num(fleet.parcels_per_day)"></span> paket</dd></div>
                        <div><dt class="text-[11px] text-slate-500">Ritase / hari</dt><dd class="font-semibold text-brand-900"><span x-text="fleet.trips_per_day"></span> rit</dd></div>
                        <div><dt class="text-[11px] text-slate-500">Investasi / unit</dt><dd class="font-semibold text-brand-900" x-text="rupiah(fleet.price)"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Cocok untuk</dt>
                            <dd class="mt-0.5 flex flex-wrap gap-1">
                                <template x-for="tag in fleet.tags" x-bind:key="tag">
                                    <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-brand-800 ring-1 ring-brand-100" x-text="tag"></span>
                                </template>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- ===== Skema usaha armada ===== --}}
                <h3 data-split class="mt-8 text-base font-bold text-brand-900">Skema usaha armada</h3>

                <fieldset class="mt-3">
                    <legend class="mb-2 text-xs font-semibold text-slate-600">Model usaha (sisi pendapatan)</legend>
                    <div class="grid gap-3 sm:grid-cols-2" data-reveal-group>
                        @foreach ($models as $m)
                            <label class="relative block cursor-pointer" data-reveal-item data-hover>
                                <input type="radio" name="model" value="{{ $m['key'] }}" x-model="modelKey" class="peer sr-only">
                                <div class="h-full rounded-2xl border border-slate-200 bg-white p-4 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-900 peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                    <p class="text-sm font-bold text-brand-900">{{ $m['label'] }}</p>
                                    <span class="mt-1 inline-block rounded-full bg-accent-300/40 px-2 py-0.5 text-[11px] font-semibold text-accent-700">{{ $m['badge'] }}</span>
                                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $m['desc'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="mt-4">
                    <legend class="mb-2 text-xs font-semibold text-slate-600">Skema pengelolaan (sisi biaya)</legend>
                    <div class="grid gap-3 sm:grid-cols-2" data-reveal-group>
                        @foreach ($managements as $g)
                            <label class="relative block cursor-pointer" data-reveal-item data-hover>
                                <input type="radio" name="management" value="{{ $g['key'] }}" x-model="mgmtKey" class="peer sr-only">
                                <div class="h-full rounded-2xl border border-slate-200 bg-white p-4 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-900 peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                    <p class="text-sm font-bold text-brand-900">{{ $g['label'] }}</p>
                                    <p class="mt-1 text-[11px] font-semibold text-accent-700">Fee manajemen {{ (int) round($g['fee_pct'] * 100) }}% dari pendapatan</p>
                                    <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $g['desc'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- ===== Operasional & pendanaan ===== --}}
                <h3 data-split class="mt-8 text-base font-bold text-brand-900">Operasional &amp; pendanaan</h3>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    {{-- Jumlah armada --}}
                    <div>
                        <label for="unitChoice" class="mb-1 block text-xs font-semibold text-slate-600">Jumlah Armada</label>
                        <select id="unitChoice" x-model="unitChoice"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            @for ($i = 1; $i <= 9; $i++)
                                <option value="{{ $i }}">{{ $i }} Unit</option>
                            @endfor
                            <option value="many">10+ Unit</option>
                        </select>
                        <div x-show="unitChoice === 'many'" x-cloak class="mt-2">
                            <input type="number" min="10" max="500" x-model.number="customUnits"
                                   aria-label="Jumlah unit (10 atau lebih)"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <p class="mt-1 text-[11px] text-slate-500">Masukkan 10 sampai 500 unit.</p>
                        </div>
                    </div>

                    {{-- Hari operasi --}}
                    <fieldset>
                        <legend class="mb-1 block text-xs font-semibold text-slate-600">Hari Operasi / Bulan</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($days as $d)
                                <label class="relative block cursor-pointer" data-hover>
                                    <input type="radio" name="days" value="{{ $d['days'] }}" x-model.number="days" class="peer sr-only">
                                    <div class="rounded-xl border border-slate-300 bg-white px-2 py-2 text-center text-sm font-semibold text-slate-600 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                        {{ $d['label'] }}
                                        <span class="block text-[10px] font-medium opacity-80">{{ $d['note'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Jangka waktu kontrak --}}
                    <fieldset>
                        <legend class="mb-1 block text-xs font-semibold text-slate-600">Jangka Waktu Kontrak</legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($contracts as $contract)
                                <label class="relative block cursor-pointer" data-hover>
                                    <input type="radio" name="years" value="{{ $contract['years'] }}" x-model.number="years" class="peer sr-only">
                                    <div class="rounded-xl border border-slate-300 bg-white px-2 py-2.5 text-center text-sm font-semibold text-slate-600 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                        {{ $contract['label'] }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Slider utilisasi --}}
                    <div>
                        <label for="utilization" class="mb-1 flex justify-between text-xs font-semibold text-slate-600">
                            <span>Tingkat Utilisasi Unit</span>
                            <span class="text-accent-600"><span x-text="utilization"></span>%</span>
                        </label>
                        <input id="utilization" type="range" min="50" max="100" step="1" x-model.number="utilization"
                               class="mt-2 w-full accent-orange-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">
                        <div class="flex justify-between text-[11px] text-slate-500">
                            <span>Konservatif 50%</span><span>Optimis 100%</span>
                        </div>
                    </div>
                </div>

                {{-- Pendanaan --}}
                <fieldset class="mt-4">
                    <legend class="mb-2 text-xs font-semibold text-slate-600">Skema kepemilikan / pendanaan</legend>
                    <div class="grid gap-3 sm:grid-cols-2" data-reveal-group>
                        @foreach ($fundings as $fd)
                            <label class="relative block cursor-pointer" data-reveal-item data-hover>
                                <input type="radio" name="funding" value="{{ $fd['key'] }}" x-model="fundingKey" class="peer sr-only">
                                <div class="h-full rounded-2xl border border-slate-200 bg-white p-4 transition-colors peer-checked:border-brand-900 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-900 peer-focus-visible:ring-2 peer-focus-visible:ring-accent-500 peer-focus-visible:ring-offset-2">
                                    <p class="text-sm font-bold text-brand-900">{{ $fd['label'] }}</p>
                                    <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $fd['desc'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- Aksi utama --}}
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <button type="button" x-on:click="calculate()" data-hover="pop"
                            class="w-full rounded-full bg-brand-900 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/25 transition hover:bg-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                        Hitung Sekarang
                    </button>
                    <button type="button" x-on:click="reset()" data-hover="pop"
                            class="shrink-0 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                        Reset
                    </button>
                </div>
                <p class="mt-1.5 text-center text-[11px] text-slate-500">Hasil diperbarui otomatis setiap Anda mengubah input.</p>

                {{-- ===== Hasil ===== --}}
                <h3 data-split class="mt-8 text-base font-bold text-brand-900">Hasil simulasi</h3>

                <div class="mt-3 grid grid-cols-2 gap-3 transition-opacity duration-200 xl:grid-cols-4"
                     x-bind:class="pulse ? 'opacity-60' : 'opacity-100'" aria-live="polite" data-reveal-group>
                    <div data-reveal-item data-hover class="rounded-2xl bg-brand-50 p-4">
                        <p class="text-[11px] font-medium text-slate-500">Pendapatan Kotor / Bulan</p>
                        <p class="mt-1 text-base font-bold text-brand-900" x-text="rupiah(r.revenue)"></p>
                    </div>
                    <div data-reveal-item data-hover class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-[11px] font-medium text-slate-500">Biaya Operasional / Bulan</p>
                        <p class="mt-1 text-base font-bold text-slate-700" x-text="rupiah(r.opex)"></p>
                    </div>
                    <div data-reveal-item data-hover class="rounded-2xl bg-emerald-50 p-4">
                        <p class="text-[11px] font-medium text-slate-500">Net Profit / Bulan</p>
                        <p class="mt-1 text-base font-bold" x-bind:class="r.net >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="rupiah(r.net)"></p>
                    </div>
                    <div data-reveal-item data-hover class="rounded-2xl bg-orange-50 p-4">
                        <p class="text-[11px] font-medium text-slate-500">ROI Tahunan</p>
                        <p class="mt-1 text-base font-bold text-accent-600" x-text="pct(r.annualRoi)"></p>
                    </div>
                </div>

                {{-- Rincian biaya tetap vs variabel --}}
                <div class="mt-4 rounded-2xl border border-slate-200 p-4" data-reveal>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-brand-900">Rincian biaya operasional / bulan</h4>
                        <span class="text-sm font-bold text-slate-700" x-text="rupiah(r.opex)"></span>
                    </div>
                    <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-slate-100" role="img"
                         x-bind:aria-label="'Biaya tetap ' + Math.round(r.pctFixed) + ' persen, variabel ' + Math.round(r.pctVar) + ' persen, fee manajemen ' + Math.round(r.pctFee) + ' persen'">
                        <div class="bg-brand-700 transition-all duration-500" x-bind:style="'width:' + r.pctFixed + '%'"></div>
                        <div class="bg-accent-400 transition-all duration-500" x-bind:style="'width:' + r.pctVar + '%'"></div>
                        <div class="bg-slate-400 transition-all duration-500" x-bind:style="'width:' + r.pctFee + '%'"></div>
                    </div>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="flex items-center gap-1.5 text-[11px] text-slate-500"><span class="h-2.5 w-2.5 rounded-full bg-brand-700"></span>Biaya tetap</dt>
                            <dd class="font-semibold text-brand-900" x-text="rupiah(r.fixed)"></dd>
                            <dd class="text-[11px] text-slate-500">Gaji pokok kurir/driver, asuransi, pajak</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-[11px] text-slate-500"><span class="h-2.5 w-2.5 rounded-full bg-accent-400"></span>Biaya variabel</dt>
                            <dd class="font-semibold text-brand-900" x-text="rupiah(r.variable)"></dd>
                            <dd class="text-[11px] text-slate-500">BBM, ritase, servis, tol dan parkir</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-[11px] text-slate-500"><span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>Fee manajemen</dt>
                            <dd class="font-semibold text-brand-900" x-text="rupiah(r.fee)"></dd>
                            <dd class="text-[11px] text-slate-500"><span x-text="Math.round(mgmt.fee_pct * 100)"></span>% dari pendapatan kotor</dd>
                        </div>
                    </dl>
                </div>

                {{-- Metrik operasional parcel --}}
                <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-brand-900">Metrik operasional parcel</h4>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3" data-reveal-group>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Estimasi paket / bulan</dt><dd class="font-semibold text-brand-900" x-text="num(r.parcelsMonth)"></dd></div>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Rata-rata paket / hari</dt><dd class="font-semibold text-brand-900" x-text="num(r.parcelsDay)"></dd></div>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Volume terangkut / bulan</dt><dd class="font-semibold text-brand-900"><span x-text="num(r.volumeMonth, 0)"></span> m³</dd></div>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Pendapatan / paket</dt><dd class="font-semibold text-brand-900" x-text="rupiah(r.revPerParcel)"></dd></div>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Biaya / paket</dt><dd class="font-semibold text-brand-900" x-text="rupiah(r.costPerParcel)"></dd></div>
                        <div data-reveal-item><dt class="text-[11px] text-slate-500">Profit / paket</dt><dd class="font-semibold" x-bind:class="r.profitPerParcel >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="rupiah(r.profitPerParcel)"></dd></div>
                    </dl>
                </div>

                {{-- Progress BEP --}}
                <div class="mt-4 rounded-2xl border border-slate-200 p-4" data-reveal>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="font-semibold text-brand-900">Pengembalian modal dalam kontrak</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                              x-bind:class="r.paidBack ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                              x-text="r.paidBack ? 'Balik modal' : 'Belum balik modal'"></span>
                    </div>
                    <div class="mt-3 h-2.5 w-full rounded-full bg-slate-200" role="progressbar"
                         aria-label="Progres pengembalian modal" aria-valuemin="0" aria-valuemax="100"
                         x-bind:aria-valuenow="r.progress">
                        <div class="h-2.5 rounded-full bg-gradient-to-r from-accent-500 to-accent-300 transition-all duration-500"
                             x-bind:style="'width:' + r.progress + '%'"></div>
                    </div>
                    <p class="mt-1 text-right text-[11px] text-slate-500"><span x-text="r.progress"></span>% dari <span x-text="isCredit ? 'total biaya kepemilikan' : 'modal'"></span> kembali dalam <span x-text="years"></span> tahun</p>

                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div>
                            <dt class="text-[11px] text-slate-500">Total Investasi</dt>
                            <dd class="font-semibold text-brand-900" x-text="rupiah(r.invest)"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-slate-500">Estimasi BEP</dt>
                            <dd class="font-semibold text-brand-900" x-text="r.bepText"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-slate-500">Net Profit Kontrak</dt>
                            <dd class="font-semibold text-brand-900" x-text="rupiah(r.totalNet)"></dd>
                        </div>
                        <div>
                            <dt class="text-[11px] text-slate-500">ROI Kontrak (setelah modal)</dt>
                            <dd class="font-semibold" x-bind:class="r.contractRoi >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="pct(r.contractRoi)"></dd>
                        </div>
                    </dl>
                </div>

                {{-- Panel pendanaan (hanya cicilan) --}}
                <div x-show="isCredit" x-cloak x-transition.opacity class="mt-4 rounded-2xl border border-accent-300 bg-accent-300/10 p-4">
                    <h4 class="text-sm font-semibold text-brand-900">Rincian cicilan / DP</h4>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-[11px] text-slate-500">Modal awal (DP)</dt><dd class="font-semibold text-brand-900" x-text="rupiah(r.upfront)"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Cicilan / bulan</dt><dd class="font-semibold text-brand-900" x-text="rupiah(r.installment)"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Total bunga</dt><dd class="font-semibold text-brand-900" x-text="rupiah(r.interest)"></dd></div>
                        <div><dt class="text-[11px] text-slate-500">Arus kas setelah cicilan / bulan</dt><dd class="font-semibold" x-bind:class="r.cashflow >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="rupiah(r.cashflow)"></dd></div>
                    </dl>
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-600">
                        Cicilan berjalan <span x-text="fundingOpt.tenor_months"></span> bulan. BEP dan ROI kontrak memakai total biaya kepemilikan (harga unit + bunga):
                        <b x-text="rupiah(r.tco)"></b>.
                    </p>
                    <p x-show="r.cashflow < 0" x-cloak class="mt-1 text-[11px] font-semibold text-rose-600">
                        Cicilan melebihi net profit bulanan. Naikkan utilisasi, perbesar DP, atau pilih skema lain.
                    </p>
                </div>

                {{-- Bandingkan skema --}}
                <div class="mt-4 rounded-2xl border border-slate-200 p-4" data-reveal>
                    <h4 class="text-sm font-semibold text-brand-900">Bandingkan skema usaha</h4>
                    <p class="mt-0.5 text-[11px] text-slate-500">Armada, jumlah unit, pendanaan, dan utilisasi tetap; hanya model usaha dan pengelolaan yang berubah.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <template x-for="c in combos" x-bind:key="c.model + c.mgmt">
                            <button type="button" x-on:click="applyCombo(c)" x-bind:aria-pressed="c.active" data-hover
                                    class="rounded-xl border px-3 py-2.5 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2"
                                    x-bind:class="c.active ? 'border-brand-900 bg-brand-50' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-brand-900" x-text="c.label"></span>
                                    <span x-show="c.best" x-cloak class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">Profit tertinggi</span>
                                </span>
                                <span class="mt-1 block text-sm font-semibold" x-bind:class="c.net >= 0 ? 'text-emerald-700' : 'text-rose-600'" x-text="rupiah(c.net) + ' / bulan'"></span>
                                <span class="block text-[11px] text-slate-500">BEP <span x-text="c.bepText"></span> &middot; ROI <span x-text="pct(c.roi)"></span></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Pembanding tersimpan --}}
                <div class="mt-4 rounded-2xl border border-slate-200 p-4" data-reveal>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-brand-900">Pembanding konfigurasi</h4>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="saveSnapshot()" data-hover="pop"
                                    class="rounded-full border border-brand-900 px-4 py-1.5 text-xs font-semibold text-brand-900 transition hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">
                                Simpan konfigurasi ini
                            </button>
                            <button type="button" x-show="snapshot" x-cloak x-on:click="snapshot = null"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                                Hapus
                            </button>
                        </div>
                    </div>
                    <p x-show="!snapshot" class="mt-2 text-[11px] text-slate-500">
                        Simpan konfigurasi sekarang, ubah input, lalu lihat selisih hasilnya di sini.
                    </p>
                    <div x-show="snapshot" x-cloak class="mt-3">
                        <p class="text-[11px] text-slate-500">Pembanding: <b class="text-slate-700" x-text="snapshot ? snapshot.label : ''"></b></p>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full min-w-[420px] text-left text-sm">
                                <caption class="sr-only">Perbandingan konfigurasi tersimpan dengan konfigurasi saat ini</caption>
                                <thead>
                                    <tr class="text-[11px] text-slate-500">
                                        <th scope="col" class="py-1 font-medium">Metrik</th>
                                        <th scope="col" class="py-1 font-medium">Pembanding</th>
                                        <th scope="col" class="py-1 font-medium">Sekarang</th>
                                        <th scope="col" class="py-1 font-medium">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in compareRows" x-bind:key="row.label">
                                        <tr class="border-t border-slate-100">
                                            <th scope="row" class="py-1.5 pr-2 text-xs font-medium text-slate-600" x-text="row.label"></th>
                                            <td class="py-1.5 pr-2 text-xs text-slate-600" x-text="row.old"></td>
                                            <td class="py-1.5 pr-2 text-xs font-semibold text-brand-900" x-text="row.cur"></td>
                                            <td class="py-1.5 text-xs font-semibold"
                                                x-bind:class="row.tone === 'good' ? 'text-emerald-700' : (row.tone === 'bad' ? 'text-rose-600' : 'text-slate-500')"
                                                x-text="row.diff"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] leading-relaxed text-slate-500">Angka bersifat ilustratif untuk keperluan simulasi akademik dan bukan jaminan hasil.</p>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" x-on:click="copySummary()" data-hover="pop"
                                class="rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                            <span x-text="copied ? 'Tersalin' : 'Salin Ringkasan'"></span>
                        </button>
                        <button type="button" x-on:click="openConsult($event)" data-hover="pop"
                                class="rounded-full bg-accent-400 px-5 py-2.5 text-sm font-bold text-brand-900 transition hover:bg-accent-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-900 focus-visible:ring-offset-2">
                            Konsultasikan Hasil Ini
                        </button>
                    </div>
                </div>
                <p class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Ringkasan simulasi disalin ke clipboard' : ''"></p>
            </section>

            {{-- ---------- Kartu FAQ ---------- --}}
            <section id="faq" data-reveal class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-md sm:p-6"
                     aria-labelledby="judul-faq">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="judul-faq" data-split class="text-xl font-bold text-brand-900">FAQ</h2>
                    <div class="relative w-full sm:w-72">
                        <input type="search" x-model="search" aria-label="Cari pertanyaan"
                               placeholder="Cari pertanyaan, mis. SLA, rute, cicilan..."
                               class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                        <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path stroke-linecap="round" d="M21 21l-4.3-4.3"></path></svg>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    <span x-text="filteredFaqs.length"></span> dari <span x-text="faqs.length"></span> pertanyaan ditampilkan
                </p>

                <div class="mt-4 space-y-3">
                    <template x-for="item in filteredFaqs" x-bind:key="item.id">
                        <div data-hover class="rounded-xl border border-slate-200 bg-slate-50/70"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0">
                            <button type="button"
                                    class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500"
                                    x-on:click="toggleFaq(item.id)" x-bind:aria-expanded="openId === item.id"
                                    x-bind:aria-controls="'faq-panel-' + item.id">
                                <span class="text-sm font-semibold text-brand-900" x-text="item.q"></span>
                                <span class="text-xl font-bold leading-none text-brand-800" aria-hidden="true" x-text="openId === item.id ? '−' : '+'"></span>
                            </button>
                            <div x-show="openId === item.id" x-cloak x-bind:id="'faq-panel-' + item.id"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="border-t border-slate-200 px-4 py-3">
                                <p class="text-sm leading-relaxed text-slate-600" x-text="item.a"></p>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredFaqs.length === 0" x-cloak
                         class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                        Tidak ada pertanyaan yang cocok. Coba kata kunci lain atau gunakan tombol Konsultasi.
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
        &copy; {{ date('Y') }} {{ $company['name'] }}. Halaman simulasi untuk keperluan presentasi mata kuliah Manajemen Proyek; seluruh angka bersifat ilustratif.
    </div>
</footer>

{{-- ===================== MODAL KONSULTASI ===================== --}}
<div x-show="consultOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="judul-konsultasi"
     x-on:keydown.escape.window="closeConsult()">
    <div class="absolute inset-0 bg-brand-900/60" x-on:click="closeConsult()"
         x-show="consultOpen" x-transition.opacity></div>
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl" data-lenis-prevent
         x-show="consultOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100">
        <button type="button" x-ref="closeBtn" class="absolute right-4 top-4 rounded text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500" aria-label="Tutup" x-on:click="closeConsult()">✕</button>

        <div x-show="!sent">
            <h2 id="judul-konsultasi" class="text-lg font-bold text-brand-900">Konsultasi Usaha Armada Parcel</h2>
            <p class="mt-1 text-sm text-slate-600">Ringkasan konfigurasi simulasi Anda akan ikut terkirim.</p>

            <dl class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50/60 px-4 text-sm">
                <template x-for="line in summaryLines" x-bind:key="line[0]">
                    <div class="flex justify-between gap-4 py-1.5">
                        <dt class="text-xs text-slate-500" x-text="line[0]"></dt>
                        <dd class="text-right text-xs font-semibold text-brand-900" x-text="line[1]"></dd>
                    </div>
                </template>
            </dl>
            <button type="button" x-on:click="copySummary()" class="mt-2 text-xs font-semibold text-brand-800 underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
                <span x-text="copied ? 'Ringkasan tersalin' : 'Salin ringkasan'"></span>
            </button>

            <form class="mt-4 space-y-3" x-on:submit.prevent="sent = true">
                <div>
                    <label for="cName" class="mb-1 block text-xs font-semibold text-slate-600">Nama</label>
                    <input id="cName" type="text" required x-model="form.name" x-ref="nameInput" autocomplete="name"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <div>
                    <label for="cWa" class="mb-1 block text-xs font-semibold text-slate-600">Nomor WhatsApp</label>
                    <input id="cWa" type="tel" required x-model="form.phone" placeholder="08xxxxxxxxxx" autocomplete="tel"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <div>
                    <label for="cArea" class="mb-1 block text-xs font-semibold text-slate-600">Area atau zona parcel yang dituju (opsional)</label>
                    <input id="cArea" type="text" x-model="form.area" placeholder="Mis. Jakarta Selatan, rute Bekasi–Cikarang"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                </div>
                <button type="submit" class="w-full rounded-full bg-accent-400 py-3 text-sm font-bold text-brand-900 transition hover:bg-accent-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-900 focus-visible:ring-offset-2">Kirim Permintaan</button>
            </form>
        </div>

        <div x-show="sent" x-cloak class="py-6 text-center">
            <p class="text-4xl" aria-hidden="true">✅</p>
            <h2 class="mt-3 text-lg font-bold text-brand-900">Terima kasih, <span x-text="form.name"></span>!</h2>
            <p class="mt-1 text-sm text-slate-600">Permintaan konsultasi (demo) telah tercatat. Tim Amba Parcel akan menghubungi Anda.</p>
            <button type="button" x-on:click="closeConsult()" class="mt-5 rounded-full bg-brand-900 px-6 py-2.5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">Tutup</button>
        </div>
    </div>
</div>

{{-- ===================== LOGIKA SIMULATOR (Alpine.js) ===================== --}}
<script>
    // Data dari controller: fleets, models, managements, fundings, assumptions, defaults, faqs.
    window.SIM_DATA = @json($config);

    function simulatorPage() {
        const data = window.SIM_DATA;
        const d = data.defaults;

        return {
            // ---- state umum ----
            consultOpen: false,
            sent: false,
            copied: false,
            opener: null,
            form: { name: '', phone: '', area: '' },

            // ---- data referensi ----
            fleets: data.fleets,
            models: data.models,
            managements: data.managements,
            fundings: data.fundings,
            base: data.assumptions,

            // ---- state simulasi ----
            unitChoice: d.units,
            customUnits: 10,
            fleetKey: d.fleet,
            modelKey: d.model,
            mgmtKey: d.management,
            fundingKey: d.funding,
            years: d.years,
            days: d.days,
            utilization: d.utilization,
            pulse: false,
            snapshot: null,

            // ---- state FAQ ----
            faqs: data.faqs,
            search: '',
            openId: null,

            init() {
                const self = this;
                // Kunci scroll latar saat modal terbuka (Lenis + body).
                this.$watch('consultOpen', function (open) {
                    document.body.classList.toggle('overflow-hidden', open);
                    if (window.__lenis) { open ? window.__lenis.stop() : window.__lenis.start(); }
                    if (open) {
                        self.$nextTick(function () {
                            const target = self.sent ? self.$refs.closeBtn : self.$refs.nameInput;
                            if (target) { target.focus(); }
                        });
                    } else if (self.opener && self.opener.focus) {
                        self.opener.focus();
                    }
                });
            },

            // ---- getter dasar ----
            get units() {
                if (this.unitChoice === 'many') {
                    const n = parseInt(this.customUnits, 10);
                    if (isNaN(n)) { return 10; }
                    return Math.min(500, Math.max(10, n));
                }
                return parseInt(this.unitChoice, 10) || 1;
            },
            pick(list, key) {
                return list.find(function (x) { return x.key === key; }) || list[0];
            },
            get fleet() { return this.pick(this.fleets, this.fleetKey); },
            get model() { return this.pick(this.models, this.modelKey); },
            get mgmt() { return this.pick(this.managements, this.mgmtKey); },
            get fundingOpt() { return this.pick(this.fundings, this.fundingKey); },
            get isCredit() { return this.fundingOpt.dp_pct < 1; },

            // Konfigurasi aktif sebagai objek murni, dipakai calc() dan pembanding.
            get cfg() {
                return {
                    units: this.units,
                    fleet: this.fleetKey,
                    model: this.modelKey,
                    mgmt: this.mgmtKey,
                    funding: this.fundingKey,
                    years: Number(this.years),
                    days: Number(this.days),
                    utilization: Number(this.utilization)
                };
            },
            // Hasil perhitungan konfigurasi aktif.
            get r() { return this.calc(this.cfg); },

            /**
             * Perhitungan inti. Harus selaras dengan monthlyNet() di controller
             * (saat utilisasi dan hari operasi sama dengan acuan).
             */
            calc(c) {
                const f = this.pick(this.fleets, c.fleet);
                const m = this.pick(this.models, c.model);
                const g = this.pick(this.managements, c.mgmt);
                const fd = this.pick(this.fundings, c.funding);

                const utilFactor = c.utilization / this.base.base_utilization;
                const dayFactor = c.days / this.base.base_days;
                const s = m.util_sensitivity;

                // Pendapatan: hari operasi berpengaruh penuh, utilisasi sesuai sensitivitas model.
                const revenue = c.units * f.revenue * m.revenue_mult * dayFactor * (1 - s + s * utilFactor);

                // Biaya: bagian tetap konstan, bagian variabel ikut hari dan utilisasi.
                const baseOpex = c.units * f.opex * g.opex_mult;
                const fixed = baseOpex * g.fixed_cost_share;
                const variable = baseOpex * (1 - g.fixed_cost_share) * dayFactor * utilFactor;
                const fee = revenue * g.fee_pct;
                const opex = fixed + variable + fee;
                const net = revenue - opex;

                // Investasi dan pendanaan (bunga flat).
                const invest = c.units * f.price;
                const upfront = invest * fd.dp_pct;
                const loan = invest - upfront;
                const tenor = fd.tenor_months || 0;
                const installment = (loan > 0 && tenor > 0) ? loan * (1 + fd.flat_rate * tenor / 12) / tenor : 0;
                const interest = installment > 0 ? installment * tenor - loan : 0;
                const tco = invest + interest;
                const cashflow = net - installment;

                // Hasil terhadap modal dan kontrak.
                const totalNet = net * 12 * c.years;
                const bep = net > 0 ? tco / net : null;
                const progress = (tco > 0 && totalNet > 0) ? Math.min(100, Math.round((totalNet / tco) * 100)) : 0;

                // Metrik operasional parcel.
                const parcelsDay = c.units * f.parcels_per_day * (c.utilization / 100);
                const parcelsMonth = parcelsDay * c.days;
                const volumeMonth = c.units * f.volume_m3 * f.trips_per_day * (c.utilization / 100) * c.days;

                return {
                    revenue: revenue, opex: opex, net: net, fixed: fixed, variable: variable, fee: fee,
                    pctFixed: opex > 0 ? (fixed / opex) * 100 : 0,
                    pctVar: opex > 0 ? (variable / opex) * 100 : 0,
                    pctFee: opex > 0 ? (fee / opex) * 100 : 0,
                    invest: invest, upfront: upfront, installment: installment, interest: interest, tco: tco, cashflow: cashflow,
                    annualRoi: invest > 0 ? (net * 12 / invest) * 100 : 0,
                    totalNet: totalNet,
                    contractRoi: tco > 0 ? ((totalNet - tco) / tco) * 100 : 0,
                    bep: bep,
                    bepText: this.bepLabel(bep),
                    paidBack: bep !== null && bep <= c.years * 12,
                    progress: progress,
                    parcelsDay: parcelsDay, parcelsMonth: parcelsMonth, volumeMonth: volumeMonth,
                    revPerParcel: parcelsMonth > 0 ? revenue / parcelsMonth : 0,
                    costPerParcel: parcelsMonth > 0 ? opex / parcelsMonth : 0,
                    profitPerParcel: parcelsMonth > 0 ? net / parcelsMonth : 0
                };
            },
            bepLabel(bep) {
                if (bep === null) { return 'Tidak balik modal'; }
                return bep.toFixed(1).replace('.', ',') + ' bulan';
            },

            // ---- perbandingan skema (model usaha x pengelolaan) ----
            get combos() {
                const self = this;
                const out = [];
                this.models.forEach(function (m) {
                    self.managements.forEach(function (g) {
                        const x = self.calc(Object.assign({}, self.cfg, { model: m.key, mgmt: g.key }));
                        out.push({
                            model: m.key, mgmt: g.key,
                            label: m.short + ' + ' + g.short,
                            net: x.net, roi: x.annualRoi, bepText: x.bepText,
                            active: m.key === self.modelKey && g.key === self.mgmtKey,
                            best: false
                        });
                    });
                });
                const top = Math.max.apply(null, out.map(function (o) { return o.net; }));
                out.forEach(function (o) { o.best = o.net === top; });
                return out;
            },
            applyCombo(c) {
                this.modelKey = c.model;
                this.mgmtKey = c.mgmt;
            },

            // ---- pembanding tersimpan ----
            saveSnapshot() {
                const c = Object.assign({}, this.cfg);
                this.snapshot = {
                    label: c.units + ' unit ' + this.fleet.name + ', ' + this.model.short + ', ' + this.mgmt.short + ', ' + this.fundingOpt.short,
                    cfg: c,
                    res: this.calc(c)
                };
            },
            get compareRows() {
                if (!this.snapshot) { return []; }
                const o = this.snapshot.res;
                const n = this.r;
                const self = this;

                // higherIsBetter: true/false menentukan warna selisih.
                function row(label, oldV, curV, fmt, higherIsBetter) {
                    const delta = curV - oldV;
                    let tone = 'neutral';
                    if (Math.abs(delta) > 0.0001) { tone = (delta > 0) === higherIsBetter ? 'good' : 'bad'; }
                    const sign = delta > 0 ? '+' : (delta < 0 ? '-' : '');
                    return {
                        label: label, old: fmt(oldV), cur: fmt(curV), tone: tone,
                        diff: Math.abs(delta) > 0.0001 ? sign + fmt(Math.abs(delta)) : 'Sama'
                    };
                }

                const rows = [
                    row('Net profit / bulan', o.net, n.net, function (v) { return self.rupiah(v); }, true),
                    row('ROI tahunan', o.annualRoi, n.annualRoi, function (v) { return self.pct(v); }, true),
                    row('Paket / bulan', o.parcelsMonth, n.parcelsMonth, function (v) { return self.num(v); }, true),
                    row('Total investasi', o.invest, n.invest, function (v) { return self.rupiah(v); }, false)
                ];

                if (o.bep !== null && n.bep !== null) {
                    rows.splice(2, 0, row('Estimasi BEP (bulan)', o.bep, n.bep, function (v) { return v.toFixed(1).replace('.', ','); }, false));
                } else {
                    rows.splice(2, 0, { label: 'Estimasi BEP', old: o.bepText, cur: n.bepText, diff: '-', tone: 'neutral' });
                }
                return rows;
            },

            // ---- ringkasan konfigurasi (modal dan salin) ----
            get summaryLines() {
                const r = this.r;
                return [
                    ['Jenis armada', this.fleet.name],
                    ['Jumlah unit', this.units + ' unit'],
                    ['Model usaha', this.model.label],
                    ['Pengelolaan', this.mgmt.label],
                    ['Pendanaan', this.fundingOpt.label],
                    ['Hari operasi', this.days + ' hari / bulan'],
                    ['Kontrak', this.years + ' tahun'],
                    ['Utilisasi', this.utilization + '%'],
                    ['Estimasi paket / bulan', this.num(r.parcelsMonth)],
                    ['Net profit / bulan', this.rupiah(r.net)],
                    ['Estimasi BEP', r.bepText]
                ];
            },
            summaryText() {
                const lines = this.summaryLines.map(function (l) { return l[0] + ': ' + l[1]; });
                return ['Simulasi Usaha Armada Amba Parcel (ilustratif)'].concat(lines).join('\n');
            },
            legacyCopy(text) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                let ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                document.body.removeChild(ta);
                return ok;
            },
            async copySummary() {
                const text = this.summaryText();
                let ok = false;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                        ok = true;
                    }
                } catch (e) { ok = false; }
                if (!ok) { ok = this.legacyCopy(text); }
                if (ok) {
                    const self = this;
                    this.copied = true;
                    setTimeout(function () { self.copied = false; }, 2000);
                }
            },

            // ---- getter FAQ ----
            get filteredFaqs() {
                const keyword = this.search.trim().toLowerCase();
                if (keyword === '') { return this.faqs; }
                return this.faqs.filter(function (f) {
                    return (f.q + ' ' + f.a + ' ' + f.tags).toLowerCase().indexOf(keyword) !== -1;
                });
            },

            // ---- aksi ----
            calculate() {
                // Hasil sudah reaktif; tombol hanya memberi umpan balik visual singkat.
                const self = this;
                this.pulse = true;
                setTimeout(function () { self.pulse = false; }, 250);
            },
            reset() {
                this.unitChoice = d.units;
                this.customUnits = 10;
                this.fleetKey = d.fleet;
                this.modelKey = d.model;
                this.mgmtKey = d.management;
                this.fundingKey = d.funding;
                this.years = d.years;
                this.days = d.days;
                this.utilization = d.utilization;
            },
            toggleFaq(id) {
                this.openId = this.openId === id ? null : id;
            },
            openConsult(ev) {
                this.opener = ev && ev.currentTarget ? ev.currentTarget : null;
                this.sent = false;
                this.consultOpen = true;
            },
            closeConsult() { this.consultOpen = false; },

            // ---- format angka ----
            rupiah(n) {
                const v = Math.round(n);
                const abs = Math.abs(v);
                const sign = v < 0 ? '-' : '';
                if (abs >= 1000000000) {
                    return sign + 'Rp ' + (abs / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' Miliar';
                }
                if (abs >= 1000000) {
                    return sign + 'Rp ' + (abs / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Juta';
                }
                return sign + 'Rp ' + abs.toLocaleString('id-ID');
            },
            pct(n) {
                return n.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%';
            },
            num(n, digits) {
                return Number(n).toLocaleString('id-ID', { maximumFractionDigits: digits === undefined ? 0 : digits });
            }
        };
    }
</script>

{{-- ===================== ANIMASI: Lenis + GSAP ScrollTrigger + Motion ===================== --}}
<script type="module">
    const root = document.documentElement;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let motionReady = false;

    // Jika Motion gagal dimuat, tampilkan semua elemen reveal agar konten tidak hilang.
    const fallbackTimer = setTimeout(function () {
        if (!motionReady) { root.classList.add('motion-fallback'); }
    }, 3500);

    const hasGsap = !!(window.gsap && window.ScrollTrigger);
    if (hasGsap) { gsap.registerPlugin(ScrollTrigger); }

    /* ---------- Lenis: smooth scroll ---------- */
    if (!reduceMotion && window.Lenis) {
        const lenis = new Lenis({ lerp: 0.1, smoothWheel: true });
        window.__lenis = lenis;

        if (hasGsap) {
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
            gsap.ticker.lagSmoothing(0);
        } else {
            const raf = function (time) { lenis.raf(time); requestAnimationFrame(raf); };
            requestAnimationFrame(raf);
        }

        // Tautan anchor (#kalkulator, #faq) ikut smooth scroll Lenis.
        document.querySelectorAll('a[href^="#"]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                const target = document.querySelector(a.getAttribute('href'));
                if (!target) { return; }
                e.preventDefault();
                lenis.scrollTo(target, { offset: -88, duration: 1.2 });
            });
        });
    }

    /* ---------- GSAP + ScrollTrigger: judul muncul per kata ---------- */
    function splitWords(el) {
        const label = el.textContent.replace(/\s+/g, ' ').trim();
        const inners = [];

        function walk(node) {
            Array.from(node.childNodes).forEach(function (child) {
                if (child.nodeType === 3) {
                    const frag = document.createDocumentFragment();
                    child.textContent.split(/(\s+)/).forEach(function (part) {
                        if (part === '') { return; }
                        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
                        const word = document.createElement('span');
                        word.className = 'split-word';
                        word.setAttribute('aria-hidden', 'true');
                        const inner = document.createElement('span');
                        inner.className = 'split-inner';
                        inner.textContent = part;
                        word.appendChild(inner);
                        frag.appendChild(word);
                        inners.push(inner);
                    });
                    child.replaceWith(frag);
                } else if (child.nodeType === 1 && child.tagName !== 'BR') {
                    walk(child);
                }
            });
        }

        walk(el);
        el.setAttribute('aria-label', label);
        return inners;
    }

    if (hasGsap && !reduceMotion) {
        document.querySelectorAll('[data-split]').forEach(function (el) {
            const words = splitWords(el);
            if (!words.length) { return; }
            gsap.from(words, {
                yPercent: 115,
                opacity: 0,
                duration: 0.8,
                ease: 'power3.out',
                stagger: 0.07,
                scrollTrigger: { trigger: el, start: 'top 88%', once: true }
            });
        });

        // Posisi trigger bergeser saat konten simulator berubah tinggi; hitung ulang dengan debounce.
        let refreshTimer = null;
        const main = document.querySelector('main');
        if (main && 'ResizeObserver' in window) {
            new ResizeObserver(function () {
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(function () { ScrollTrigger.refresh(); }, 250);
            }).observe(main);
        }
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
        }
    }

    /* ---------- Motion: animasi masuk card dan hover ---------- */
    try {
        const { animate, inView, stagger } = await import('https://cdn.jsdelivr.net/npm/motion@11.18.2/+esm');
        motionReady = true;
        clearTimeout(fallbackTimer);

        if (reduceMotion) {
            root.classList.add('motion-fallback');
        } else {
            const ease = [0.22, 1, 0.36, 1];
            const margin = '0px 0px -8% 0px';

            // Card tunggal.
            document.querySelectorAll('[data-reveal]').forEach(function (el) {
                inView(el, function () {
                    animate(el, { opacity: [0, 1], y: [28, 0] }, { duration: 0.7, ease: ease });
                }, { margin: margin });
            });

            // Kelompok card dengan stagger.
            document.querySelectorAll('[data-reveal-group]').forEach(function (group) {
                const items = group.querySelectorAll('[data-reveal-item]');
                if (!items.length) { return; }
                inView(group, function () {
                    animate(items, { opacity: [0, 1], y: [24, 0] }, { duration: 0.6, ease: ease, delay: stagger(0.07) });
                }, { margin: margin });
            });

            // Hover (delegasi event agar item FAQ dinamis dari Alpine juga ikut).
            let current = null;
            function enter(el) {
                const mode = el.getAttribute('data-hover');
                if (mode === 'pop') {
                    animate(el, { scale: 1.04 }, { duration: 0.2, ease: 'easeOut' });
                } else {
                    animate(el, { y: -4, scale: 1.012 }, { duration: 0.25, ease: 'easeOut' });
                }
            }
            function leave(el) {
                animate(el, { y: 0, scale: 1 }, { duration: 0.25, ease: 'easeOut' });
            }

            document.addEventListener('pointerover', function (e) {
                if (e.pointerType === 'touch') { return; }
                const el = e.target.closest ? e.target.closest('[data-hover]') : null;
                if (el === current) { return; }
                if (current) { leave(current); }
                current = el;
                if (el) { enter(el); }
            });
            root.addEventListener('pointerleave', function () {
                if (current) { leave(current); current = null; }
            });

            // Umpan balik tekan untuk tombol.
            document.addEventListener('pointerdown', function (e) {
                const el = e.target.closest ? e.target.closest('[data-hover="pop"]') : null;
                if (el) { animate(el, { scale: 0.96 }, { duration: 0.1 }); }
            });
            document.addEventListener('pointerup', function (e) {
                const el = e.target.closest ? e.target.closest('[data-hover="pop"]') : null;
                if (el) { animate(el, { scale: 1.04 }, { duration: 0.15 }); }
            });
        }
    } catch (err) {
        // Motion tidak termuat (offline/CDN diblokir): konten tetap tampil lewat fallback.
        root.classList.add('motion-fallback');
    }
</script>
<x-footer></x-footer>
</body>
</html>
