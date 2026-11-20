@extends('layouts.app')

@section('title', 'ThreatMetrics — Cyber Threat Intelligence & Link Scanner')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

    <!-- ========================================================================= -->
    <!-- 1. HERO SECTION: INTERACTIVE LINK & IOC SCANNER                           -->
    <!-- ========================================================================= -->
    <section class="relative bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-10 overflow-hidden">
        <!-- Subtle Grid Pattern Accent -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#f1f5f9_1px,transparent_1px),linear-gradient(to_bottom,#f1f5f9_1px,transparent_1px)] bg-[size:32px_32px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] opacity-60 pointer-events-none"></div>

        <div class="relative max-w-3xl mx-auto text-center space-y-4">
            <!-- Badge Kategori -->
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span>Analisis Heuristik & Ekstraksi Domain Real-Time</span>
            </div>

            <!-- Headline Utama -->
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                Cyber Threat Intelligence & Link Scanner
            </h1>

            <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Periksa apakah sebuah link URL, domain, alamat IP, atau hash file terindikasi berbahaya 
                (<span class="text-red-600 font-semibold">malicious</span>) atau aman (<span class="text-emerald-600 font-semibold">clean</span>). 
                Didukung feed otomatis TweetFeed API dan deteksi domain induk cerdas.
            </p>

            <!-- Scanner Form Input -->
            <form id="scanner-form" onsubmit="handleScanSubmit(event)" class="mt-6 space-y-3">
                <div class="relative flex flex-col sm:flex-row items-stretch sm:items-center rounded-xl bg-slate-50 border-2 border-slate-300 focus-within:border-blue-600 focus-within:bg-white transition-all shadow-sm overflow-hidden p-1">
                    
                    <!-- Search Icon -->
                    <div class="hidden sm:flex pl-3.5 pr-1 text-slate-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Input Box -->
                    <input type="text" 
                           id="scan-input" 
                           name="query" 
                           required 
                           autocomplete="off"
                           placeholder="Masukkan URL lengkap, domain, IP, atau hash (misal: https://phishing-site.com/login atau 192.168.1.1)..." 
                           class="w-full bg-transparent border-0 px-3 py-3 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0 font-mono">

                    <!-- Submit Button -->
                    <button type="submit" 
                            id="scan-submit-btn" 
                            class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-semibold shadow-sm transition-all flex-shrink-0">
                        <span id="scan-btn-spinner" class="hidden mr-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </span>
                        <span id="scan-btn-text">Pindai Ancaman</span>
                    </button>
                </div>
            </form>

            <!-- Quick Sample Test Buttons (Mengambil Sampel Nyata dari DB Lokal) -->
            <div class="flex flex-wrap items-center justify-center gap-2 pt-2 text-xs">
                <span class="text-slate-400 font-medium">Uji Cepat:</span>
                
                <!-- Coba Contoh Link Berbahaya -->
                <button type="button" 
                        onclick="testSample('{{ addslashes($sampleMalicious['raw_value']) }}')" 
                        class="inline-flex items-center px-3 py-1 rounded-md bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium transition-colors">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                    Coba Contoh Link Berbahaya
                </button>

                <!-- Coba Contoh Aman -->
                <button type="button" 
                        onclick="testSample('{{ addslashes($sampleSafe['raw_value']) }}')" 
                        class="inline-flex items-center px-3 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-medium transition-colors">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                    Coba Contoh Aman
                </button>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SCAN RESULT CARD CONTAINER (DYNAMICALLY POPULATED VIA JS / AJAX)          -->
        <!-- ========================================================================= -->
        <div id="scan-result-card" class="hidden mt-8 max-w-4xl mx-auto rounded-xl border p-6 transition-all duration-300">
            <!-- Isi kartu hasil pemindaian diisi dinamis oleh JavaScript -->
        </div>
    </section>


    <!-- ========================================================================= -->
    <!-- 2. DASHBOARD KPI CARDS                                                    -->
    <!-- ========================================================================= -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
        
        <!-- Total IOC Aktif -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total IOC Aktif</span>
                <span class="soc-tooltip p-1 text-slate-400 hover:text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="soc-tooltip-content">Jumlah total indikator ancaman unik yang tersimpan di database lokal dari TweetFeed.</span>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono tracking-tight">{{ number_format($totalActiveIocs) }}</span>
                <span class="inline-flex items-center text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                    Live Feed
                </span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">Tersinkronisasi otomatis setiap 15m</div>
        </div>

        <!-- URL Berbahaya -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between cursor-pointer hover:border-slate-300 transition-colors" onclick="filterByType('url')">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">URL Berbahaya</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono tracking-tight">{{ number_format($maliciousUrlsCount) }}</span>
                <span class="text-xs text-blue-600 font-medium">Klik filter &rarr;</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">Tautan phishing & distribusi malware</div>
        </div>

        <!-- IP Berbahaya -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between cursor-pointer hover:border-slate-300 transition-colors" onclick="filterByType('ip')">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">IP Berbahaya</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono tracking-tight">{{ number_format($maliciousIpsCount) }}</span>
                <span class="text-xs text-emerald-600 font-medium">Klik filter &rarr;</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">Server C2 & pemindai botnet aktif</div>
        </div>

        <!-- Jenis Ancaman Terbanyak -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ancaman Terbanyak</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">Top Tag</span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-extrabold text-slate-900 truncate">#{{ ltrim($topThreatCategory, '#') }}</span>
                <span class="text-xs text-slate-500 font-mono">{{ number_format($topCategoryCount) }} laporan</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500">Kategori ancaman paling dominan</div>
        </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 3. GRAFIK STATISTIK INTERAKTIF (CLICK-TO-FILTER TABLE)                    -->
    <!-- ========================================================================= -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Dashboard Visual & Tren Ancaman</h2>
                <p class="text-xs text-slate-500">
                    <strong class="text-blue-600 font-semibold">Fitur Interaktif:</strong> Klik salah satu bagian diagram (donat tipe atau batang kategori) untuk otomatis memfilter tabel di bawah.
                </p>
            </div>
            <button onclick="resetAllFilters()" class="text-xs text-slate-500 hover:text-slate-800 font-medium underline">
                Reset Filter Tabel
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            
            <!-- Grafik 1: Distribusi Jenis IOC (Doughnut) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Distribusi Jenis IOC</h3>
                    <span class="text-[10px] text-blue-600 font-medium bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">Klik segmen</span>
                </div>
                <div class="relative h-56 flex items-center justify-center">
                    <canvas id="chart-types"></canvas>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 text-center">
                    Klik irisan diagram untuk menyaring tabel berdasarkan tipe indikator.
                </div>
            </div>

            <!-- Grafik 2: Top 5 Kategori / Tag Ancaman (Horizontal Bar) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Top 5 Kategori Ancaman</h3>
                    <span class="text-[10px] text-blue-600 font-medium bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">Klik batang</span>
                </div>
                <div class="relative h-56">
                    <canvas id="chart-categories"></canvas>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 text-center">
                    Klik batang untuk memfilter tabel berdasarkan tag ancaman terpilih.
                </div>
            </div>

            <!-- Grafik 3: Tren Laporan Ancaman (Line / Area Chart 24 Jam) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tren Laporan Ancaman (24 Jam)</h3>
                    <span class="text-[10px] text-slate-500 font-mono">WIB</span>
                </div>
                <div class="relative h-56">
                    <canvas id="chart-trend"></canvas>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 text-center">
                    Frekuensi ancaman baru yang dilaporkan komunitas per jam.
                </div>
            </div>

        </div>
    </section>


    <!-- ========================================================================= -->
    <!-- 4. TABEL DATA IOC TERKELOMPOK DENGAN LIVE SEARCH & FILTER                 -->
    <!-- ========================================================================= -->
    <section id="tabel-ioc" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden scroll-mt-20">
        
        <!-- Table Header & Navigation Tabs -->
        <div class="p-5 border-b border-slate-200 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center">
                        <span>Database Feed Indikator Ancaman (IOC)</span>
                        <span id="active-filter-badge" class="hidden ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800"></span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Daftar indikator ancaman siber aktif yang telah diamankan dengan format defanged.
                    </p>
                </div>

                <!-- Action Shortcut -->
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="openAddIocModal()" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah IOC Manual
                    </button>
                </div>
            </div>

            <!-- Group Navigation Tabs (Semua, URL, Domain, IP, File Hash) -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs font-medium text-slate-600">
                    <button type="button" onclick="filterByType('all')" id="tab-all" class="ioc-tab px-3 py-1.5 rounded-md bg-white text-slate-900 font-semibold shadow-sm transition-all">
                        Semua
                    </button>
                    <button type="button" onclick="filterByType('url')" id="tab-url" class="ioc-tab px-3 py-1.5 rounded-md hover:text-slate-900 transition-all">
                        URL
                    </button>
                    <button type="button" onclick="filterByType('domain')" id="tab-domain" class="ioc-tab px-3 py-1.5 rounded-md hover:text-slate-900 transition-all">
                        Domain
                    </button>
                    <button type="button" onclick="filterByType('ip')" id="tab-ip" class="ioc-tab px-3 py-1.5 rounded-md hover:text-slate-900 transition-all">
                        IP Address
                    </button>
                    <button type="button" onclick="filterByType('hash')" id="tab-hash" class="ioc-tab px-3 py-1.5 rounded-md hover:text-slate-900 transition-all">
                        File Hash
                    </button>
                </div>

                <!-- Live Search Box & Dropdown Filters -->
                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <div class="relative flex-grow sm:w-64">
                        <input type="text" 
                               id="table-search-input" 
                               onkeyup="handleTableSearch(event)"
                               placeholder="Cari IOC, host, atau pelapor..." 
                               class="w-full bg-slate-50 border border-slate-300 rounded-md pl-8 pr-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:bg-white font-mono">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <button type="button" onclick="resetAllFilters()" title="Reset semua filter" class="p-1.5 text-slate-400 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <!-- Table Data Container -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3">Tipe</th>
                        <th scope="col" class="px-4 py-3">Nilai Indikator (Defanged)</th>
                        <th scope="col" class="px-4 py-3">Tags & Kategori</th>
                        <th scope="col" class="px-4 py-3">Severity</th>
                        <th scope="col" class="px-4 py-3">Sumber & Waktu</th>
                        <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="ioc-table-body" class="divide-y divide-slate-100 bg-white">
                    @include('partials.ioc_table_rows', ['iocs' => $iocs])
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Container -->
        <div id="ioc-pagination-container">
            @include('partials.pagination', ['iocs' => $iocs])
        </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 5. WATCHLIST & WHITELIST MANAGEMENT (FITUR CRUD DATABASE)                 -->
    <!-- ========================================================================= -->
    <section id="watchlist-section" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 scroll-mt-20 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center">
                    <span>Manajemen Watchlist & Whitelist Manual</span>
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">CRUD Aktif</span>
                </h2>
                <p class="text-xs text-slate-500">
                    Kelola indikator ancaman internal organisasi (Watchlist) serta domain terverifikasi aman (Whitelist) untuk interaksi database penuh.
                </p>
            </div>

            <button type="button" onclick="openAddWatchlistModal()" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                + Tambah Indikator Baru
            </button>
        </div>

        <!-- Watchlist Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2.5">Klasifikasi</th>
                        <th class="px-4 py-2.5">Nilai (Defanged)</th>
                        <th class="px-4 py-2.5">Kategori / Catatan</th>
                        <th class="px-4 py-2.5">Tingkat Risiko</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($watchlists as $wl)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $wl->list_badge }}">
                                    {{ $wl->list_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="font-mono text-[11px] text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ $wl->defanged_value }}
                                </code>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 text-xs">{{ $wl->category ?: '-' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $wl->notes ?: 'Tidak ada catatan.' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase
                                    @if($wl->severity === 'critical') bg-red-100 text-red-800
                                    @elseif($wl->severity === 'warning') bg-amber-100 text-amber-800
                                    @elseif($wl->severity === 'trusted') bg-blue-100 text-blue-800
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ $wl->severity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button type="button" 
                                        onclick="toggleWatchlistStatus({{ $wl->id }}, this)"
                                        class="inline-flex items-center text-[11px] font-medium {{ $wl->is_active ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-500 bg-slate-100 border-slate-200' }} px-2 py-0.5 rounded border transition-colors" title="Klik untuk ubah status aktif">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $wl->is_active ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1.5"></span>
                                    <span>{{ $wl->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </button>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right space-x-2">
                                <button type="button" onclick="deleteWatchlist({{ $wl->id }})" class="text-red-600 hover:text-red-800 text-xs font-medium">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                Belum ada indikator dalam watchlist atau whitelist.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 6. RIWAYAT PEMINDAIAN PENGGUNA TERBARU (SCAN HISTORIES)                   -->
    <!-- ========================================================================= -->
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900">Riwayat Pemindaian Terakhir (Audit Log)</h2>
                <p class="text-xs text-slate-500">Mencatat seluruh link, domain, atau IP yang telah dicek oleh pengguna.</p>
            </div>
            <span class="text-xs text-slate-500 font-mono">Tersimpan di tabel <code class="bg-slate-100 px-1 py-0.5 rounded">scan_histories</code></span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2.5">Waktu Pengecekan</th>
                        <th class="px-4 py-2.5">Query Input</th>
                        <th class="px-4 py-2.5">Tipe</th>
                        <th class="px-4 py-2.5">Hasil (Verdict)</th>
                        <th class="px-4 py-2.5">Skor Risiko</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="scan-history-tbody" class="divide-y divide-slate-100 bg-white">
                    @forelse ($recentScans as $scan)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                                {{ $scan->created_at ? $scan->created_at->setTimezone('Asia/Jakarta')->format('d M H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-800 max-w-xs truncate">
                                {{ \App\Services\ThreatIntelligenceService::defang($scan->query_type, $scan->query) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap uppercase text-[10px] font-semibold text-slate-600">
                                {{ $scan->query_type }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $scan->verdict_color }}">
                                    {{ $scan->verdict_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                                <span class="{{ $scan->risk_score >= 70 ? 'text-red-600 font-bold' : ($scan->risk_score > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                                    {{ $scan->risk_score }}/100
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <button type="button" onclick="testSample('{{ addslashes($scan->query) }}')" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                    Cek Ulang
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                Belum ada riwayat pemindaian tersimpan. Coba ketik link di hero scanner di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>


<!-- ========================================================================= -->
<!-- MODAL: DETAIL IOC                                                         -->
<!-- ========================================================================= -->
<div id="detail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
    <div class="min-h-screen px-4 text-center flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl max-w-xl w-full p-6 text-left shadow-2xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900" id="modal-ioc-title">Detail Indikator Ancaman (IOC)</h3>
                    <p class="text-xs text-slate-500">Informasi forensik digital & metadata pelaporan ancaman.</p>
                </div>
                <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md">&times;</button>
            </div>

            <div id="modal-ioc-content" class="mt-4 space-y-4 text-xs text-slate-700">
                <!-- Konten detail diisi via AJAX -->
            </div>

            <div class="mt-6 pt-3 border-t border-slate-100 flex justify-end">
                <button onclick="closeDetailModal()" class="px-4 py-2 text-xs font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ========================================================================= -->
<!-- MODAL: TAMBAH WATCHLIST / WHITELIST BARU                                  -->
<!-- ========================================================================= -->
<div id="add-watchlist-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
    <div class="min-h-screen px-4 text-center flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tambah Entri Watchlist / Whitelist</h3>
                    <p class="text-xs text-slate-500">Simpan indikator kustom ke database lokal untuk aturan inspeksi.</p>
                </div>
                <button onclick="closeAddWatchlistModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md">&times;</button>
            </div>

            <form onsubmit="handleAddWatchlist(event)" class="mt-4 space-y-3.5 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Klasifikasi Daftar</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="list_type" value="watchlist" checked class="text-amber-600 focus:ring-amber-500">
                            <span class="ml-2 font-medium text-slate-800">Watchlist (Ancaman Internal)</span>
                        </label>
                        <label class="flex items-center p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="list_type" value="whitelist" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 font-medium text-slate-800">Whitelist (Aset Terpercaya)</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe Indikator</label>
                        <select name="type" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                            <option value="url">URL</option>
                            <option value="domain" selected>Domain</option>
                            <option value="ip">IP Address</option>
                            <option value="sha256">SHA-256 Hash</option>
                            <option value="md5">MD5 Hash</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tingkat Risiko</label>
                        <select name="severity" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                            <option value="warning" selected>Warning</option>
                            <option value="critical">Critical</option>
                            <option value="info">Info</option>
                            <option value="trusted">Trusted (Khusus Whitelist)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nilai Indikator (URL, Domain, atau IP)</label>
                    <input type="text" name="value" required placeholder="misal: suspicious-portal.net atau 194.26.29.112" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 font-mono focus:ring-blue-500 focus:bg-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Kategori / Label Singkat</label>
                    <input type="text" name="category" placeholder="misal: Phishing Tiruan Kampus atau Public DNS" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Catatan Analis</label>
                    <textarea name="notes" rows="2" placeholder="Tuliskan catatan teknis atau alasan mengapa indikator ini dipantau/dizinkan..." class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="closeAddWatchlistModal()" class="px-3.5 py-2 text-xs font-medium rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                        Simpan ke Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ========================================================================= -->
<!-- MODAL: TAMBAH IOC MANUAL                                                  -->
<!-- ========================================================================= -->
<div id="add-ioc-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm" aria-modal="true">
    <div class="min-h-screen px-4 text-center flex items-center justify-center p-4">
        <div class="relative bg-white rounded-xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-200 overflow-hidden my-8">
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tambah Indikator Ancaman (IOC) Manual</h3>
                    <p class="text-xs text-slate-500">Menyimpan temuan ancaman baru langsung ke tabel threat_iocs.</p>
                </div>
                <button onclick="closeAddIocModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md">&times;</button>
            </div>

            <form onsubmit="handleAddIoc(event)" class="mt-4 space-y-3.5 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipe IOC</label>
                        <select name="type" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                            <option value="url">URL</option>
                            <option value="domain">Domain</option>
                            <option value="ip">IP Address</option>
                            <option value="sha256">SHA-256</option>
                            <option value="md5">MD5</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Severity</label>
                        <select name="severity" required class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                            <option value="warning" selected>Warning</option>
                            <option value="critical">Critical</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nilai Indikator (Value)</label>
                    <input type="text" name="value" required placeholder="https://phishing-baru.com/auth" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 font-mono focus:ring-blue-500 focus:bg-white">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tags (Pisahkan koma)</label>
                    <input type="text" name="tags" placeholder="phishing, malware, c2" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kategori</label>
                        <input type="text" name="category" placeholder="Phishing Credential" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" class="w-full bg-slate-50 border border-slate-300 rounded-md px-3 py-2 text-xs text-slate-900 focus:ring-blue-500 focus:bg-white">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="false_positive">False Positive</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="closeAddIocModal()" class="px-3.5 py-2 text-xs font-medium rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 shadow-sm">
                        Simpan IOC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    /*
    |--------------------------------------------------------------------------
    | CHART.JS INISIALISASI & INTERAKSI (CLICK-TO-FILTER TABLE)
    |--------------------------------------------------------------------------
    */
    const chartTypeKeys = @json($chartTypeKeys);
    const chartTypeLabels = @json($chartTypeLabels);
    const chartTypeValues = @json($chartTypeValues);

    const chartCatLabels = @json($chartCatLabels);
    const chartCatValues = @json($chartCatValues);

    const trendLabels = @json($trendLabels);
    const trendValues = @json($trendValues);

    let typeChart = null;
    let catChart = null;
    let trendChart = null;

    document.addEventListener('DOMContentLoaded', function () {
        initCharts();
    });

    function initCharts() {
        // 1. Chart Distribusi Tipe IOC (Doughnut)
        const ctxType = document.getElementById('chart-types')?.getContext('2d');
        if (ctxType) {
            typeChart = new Chart(ctxType, {
                type: 'doughnut',
                data: {
                    labels: chartTypeLabels,
                    datasets: [{
                        data: chartTypeValues,
                        backgroundColor: [
                            '#3B82F6', // URL (Blue)
                            '#8B5CF6', // Domain (Purple)
                            '#10B981', // IP (Emerald)
                            '#F59E0B', // SHA-256 (Amber)
                            '#64748B', // MD5 (Slate)
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: { size: 10, family: 'Inter' },
                                padding: 8
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.label}: ${context.raw} IOC`;
                                }
                            }
                        }
                    },
                    // CLICK-TO-FILTER TABEL
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const selectedType = chartTypeKeys[index];
                            filterByType(selectedType);
                            scrollToTable();
                            showToast(`Tabel difilter berdasarkan tipe: ${chartTypeLabels[index]}`);
                        }
                    }
                }
            });
        }

        // 2. Chart Top 5 Kategori (Bar Horizontal)
        const ctxCat = document.getElementById('chart-categories')?.getContext('2d');
        if (ctxCat) {
            catChart = new Chart(ctxCat, {
                type: 'bar',
                data: {
                    labels: chartCatLabels.map(l => '#' + l.replace('#', '')),
                    datasets: [{
                        label: 'Jumlah Ancaman',
                        data: chartCatValues,
                        backgroundColor: '#EF4444',
                        borderRadius: 4,
                        barThickness: 16
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (c) => ` ${c.raw} laporan`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: '#F1F5F9' },
                            ticks: { font: { size: 10, family: 'Inter' } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 10, family: 'Inter', weight: 'bold' } }
                        }
                    },
                    // CLICK-TO-FILTER TABEL
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const selectedTag = chartCatLabels[index];
                            filterByTag(selectedTag);
                            scrollToTable();
                            showToast(`Tabel difilter berdasarkan kategori: #${selectedTag}`);
                        }
                    }
                }
            });
        }

        // 3. Chart Tren Ancaman 24 Jam (Line / Area)
        const ctxTrend = document.getElementById('chart-trend')?.getContext('2d');
        if (ctxTrend) {
            trendChart = new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Laporan per Jam',
                        data: trendValues,
                        borderColor: '#0F172A',
                        backgroundColor: 'rgba(15, 23, 42, 0.05)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 1,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 9, family: 'Inter' }, maxTicksLimit: 8 }
                        },
                        y: {
                            grid: { color: '#F1F5F9' },
                            ticks: { font: { size: 9, family: 'Inter' }, precision: 0 }
                        }
                    }
                }
            });
        }
    }

    function scrollToTable() {
        document.getElementById('tabel-ioc')?.scrollIntoView({ behavior: 'smooth' });
    }

    /*
    |--------------------------------------------------------------------------
    | HERO SCANNER SUBMIT & QUICK SAMPLES
    |--------------------------------------------------------------------------
    */
    function testSample(value) {
        const input = document.getElementById('scan-input');
        input.value = value;
        input.focus();
        // Trigger submit
        const form = document.getElementById('scanner-form');
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }

    async function handleScanSubmit(event) {
        event.preventDefault();
        const input = document.getElementById('scan-input');
        const query = input.value.trim();
        if (!query) return;

        const btn = document.getElementById('scan-submit-btn');
        const spinner = document.getElementById('scan-btn-spinner');
        const btnText = document.getElementById('scan-btn-text');
        const resultCard = document.getElementById('scan-result-card');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        btn.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = 'Memeriksa Ancaman...';

        try {
            const response = await fetch('{{ route("scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ query: query })
            });

            const json = await response.json();

            if (response.ok && json.success) {
                renderScanResult(json.data);
                refreshScanHistory();
            } else {
                showToast(json.message || 'Gagal memindai indikator', 'error');
            }
        } catch (err) {
            showToast('Terjadi kesalahan jaringan saat memindai.', 'error');
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Pindai Ancaman';
        }
    }

    function renderScanResult(data) {
        const card = document.getElementById('scan-result-card');
        card.classList.remove('hidden');

        let borderClass = 'border-slate-200 bg-white';
        let statusBadge = '';
        let headerTitle = '';
        let riskColor = 'text-slate-700';

        if (data.is_malicious) {
            borderClass = 'border-red-300 bg-red-50/50';
            statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-red-600 text-white shadow-sm">TERINDIKASI BERBAHAYA (MALICIOUS)</span>';
            headerTitle = 'Ancaman Siber Terdeteksi dalam Feed TweetFeed';
            riskColor = 'text-red-700';
        } else if (data.is_watchlist) {
            borderClass = 'border-amber-300 bg-amber-50/50';
            statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-amber-600 text-white shadow-sm">TERDAFTAR DALAM WATCHLIST PEMANTAUAN</span>';
            headerTitle = 'Indikator Dipantau oleh Tim SecOps Internal';
            riskColor = 'text-amber-700';
        } else if (data.is_whitelist) {
            borderClass = 'border-blue-300 bg-blue-50/50';
            statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-blue-600 text-white shadow-sm">WHITELIST TERPERCAYA (TRUSTED)</span>';
            headerTitle = 'Aset Resmi Terverifikasi Aman';
            riskColor = 'text-blue-700';
        } else {
            borderClass = 'border-emerald-300 bg-emerald-50/50';
            statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-600 text-white shadow-sm">TIDAK DITEMUKAN DI FEED ANCAMAN (BERSIH)</span>';
            headerTitle = 'Indikator Bersih / Belum Terindeks';
            riskColor = 'text-emerald-700';
        }

        // Tags HTML
        let tagsHtml = '';
        if (data.matched_ioc && data.matched_ioc.tags) {
            tagsHtml = data.matched_ioc.tags.map(t => 
                `<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-800">#${t.replace('#', '')}</span>`
            ).join(' ');
        }

        card.className = `mt-8 max-w-4xl mx-auto rounded-xl border p-6 transition-all duration-300 ${borderClass}`;
        
        card.innerHTML = `
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-200/70">
                <div class="flex items-center space-x-3">
                    ${statusBadge}
                    <span class="text-xs text-slate-500 font-mono">Tipe: ${data.query_type.toUpperCase()}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-slate-500 font-medium">Skor Risiko:</span>
                    <span class="text-base font-extrabold font-mono ${riskColor}">${data.risk_score}/100</span>
                </div>
            </div>

            <!-- Penjelasan Bahasa Awam -->
            <div class="mt-4 p-4 rounded-lg bg-white border border-slate-200/80 shadow-xs">
                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700 mb-1 flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Penjelasan Analisis Risiko (Bahasa Awam)
                </h4>
                <p class="text-xs text-slate-700 leading-relaxed">
                    ${data.risk_explanation}
                </p>
            </div>

            <!-- Metadata Teknis Grid -->
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
                
                <!-- String Defanged -->
                <div class="p-3 bg-white rounded-lg border border-slate-200 sm:col-span-2">
                    <span class="text-[11px] font-medium text-slate-500 block mb-1">String Defanged (Aman untuk Dianalisis):</span>
                    <div class="flex items-center justify-between space-x-2">
                        <code class="font-mono text-xs text-slate-900 bg-slate-50 px-2 py-1 rounded border border-slate-200 truncate select-all">
                            ${data.defanged_query}
                        </code>
                        <button type="button" 
                                onclick="copyToClipboard('${data.defanged_query}', 'String Defanged')"
                                class="inline-flex items-center px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px] flex-shrink-0 transition-colors">
                            Salin Defanged
                        </button>
                    </div>
                </div>

                <!-- Host Domain Diekstrak -->
                <div class="p-3 bg-white rounded-lg border border-slate-200">
                    <span class="text-[11px] font-medium text-slate-500 block mb-1">Host/Domain Utama:</span>
                    <span class="font-mono text-xs font-semibold text-slate-900">${data.host || '-'}</span>
                    ${data.match_type ? `<div class="text-[10px] text-blue-600 font-medium mt-0.5">${data.match_type}</div>` : ''}
                </div>

                <!-- Informasi Pelapor (Jika ada) -->
                ${data.matched_ioc ? `
                    <div class="p-3 bg-white rounded-lg border border-slate-200">
                        <span class="text-[11px] font-medium text-slate-500 block mb-1">Akun Pelapor:</span>
                        <span class="font-medium text-blue-600">@${data.matched_ioc.source_user || 'TweetFeed'}</span>
                    </div>

                    <div class="p-3 bg-white rounded-lg border border-slate-200">
                        <span class="text-[11px] font-medium text-slate-500 block mb-1">Waktu Pelaporan Komunitas:</span>
                        <span class="text-slate-800">${data.matched_ioc.source_date || '-'}</span>
                    </div>

                    <div class="p-3 bg-white rounded-lg border border-slate-200 sm:col-span-2 lg:col-span-1">
                        <span class="text-[11px] font-medium text-slate-500 block mb-1">Tags Ancaman:</span>
                        <div class="flex flex-wrap gap-1">${tagsHtml || '<span class="text-slate-400 italic">Tanpa tag</span>'}</div>
                    </div>
                ` : ''}

            </div>
        `;

        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE FILTERING & SERVER-SIDE AJAX
    |--------------------------------------------------------------------------
    */
    let currentFilterType = 'all';
    let currentFilterTag = '';
    let currentSearchTerm = '';

    function filterByType(type) {
        currentFilterType = type;
        
        // Update tab styles
        document.querySelectorAll('.ioc-tab').forEach(tab => {
            tab.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
        });
        const activeTab = document.getElementById(`tab-${type}`);
        if (activeTab) {
            activeTab.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
        }

        updateFilterBadge();
        fetchTableData();
    }

    function filterByTag(tag) {
        currentFilterTag = tag;
        updateFilterBadge();
        fetchTableData();
    }

    let searchTimeout = null;
    function handleTableSearch(event) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearchTerm = event.target.value.trim();
            fetchTableData();
        }, 300);
    }

    function resetAllFilters() {
        currentFilterType = 'all';
        currentFilterTag = '';
        currentSearchTerm = '';
        document.getElementById('table-search-input').value = '';
        
        document.querySelectorAll('.ioc-tab').forEach(tab => {
            tab.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
        });
        document.getElementById('tab-all')?.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');

        updateFilterBadge();
        fetchTableData();
    }

    function updateFilterBadge() {
        const badge = document.getElementById('active-filter-badge');
        if (!badge) return;

        let parts = [];
        if (currentFilterType !== 'all') parts.push(`Tipe: ${currentFilterType.toUpperCase()}`);
        if (currentFilterTag) parts.push(`Tag: #${currentFilterTag}`);

        if (parts.length > 0) {
            badge.textContent = parts.join(' | ');
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    async function fetchTableData(url = null) {
        const tbody = document.getElementById('ioc-table-body');
        const paginationContainer = document.getElementById('ioc-pagination-container');

        let targetUrl = url || '{{ route("threat-iocs.index") }}';
        const params = new URLSearchParams();

        if (currentFilterType !== 'all') params.append('type', currentFilterType);
        if (currentFilterTag) params.append('tag', currentFilterTag);
        if (currentSearchTerm) params.append('search', currentSearchTerm);

        const separator = targetUrl.includes('?') ? '&' : '?';
        targetUrl = targetUrl + (params.toString() ? separator + params.toString() : '');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.html) {
                tbody.innerHTML = data.html;
            }
            if (data.pagination) {
                paginationContainer.innerHTML = data.pagination;
            }
        } catch (err) {
            console.error('Error fetching table data:', err);
        }
    }

    function loadPage(event, url) {
        event.preventDefault();
        fetchTableData(url);
        scrollToTable();
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL MODAL
    |--------------------------------------------------------------------------
    */
    async function viewIocDetail(iocId) {
        const modal = document.getElementById('detail-modal');
        const content = document.getElementById('modal-ioc-content');

        content.innerHTML = `
            <div class="py-12 text-center text-slate-400">
                <svg class="animate-spin h-6 w-6 mx-auto text-blue-600 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Memuat data detail IOC...
            </div>
        `;
        modal.classList.remove('hidden');

        try {
            const response = await fetch(`{{ url('/threat-iocs') }}/${iocId}`);
            const data = await response.json();

            content.innerHTML = `
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                        <span class="text-[11px] font-semibold text-slate-500 uppercase block mb-1">Nilai Defanged (Aman)</span>
                        <div class="flex items-center justify-between">
                            <code class="font-mono text-xs text-slate-900 bg-white px-2 py-1 rounded border border-slate-300 truncate select-all">
                                ${data.defanged_value}
                            </code>
                            <button type="button" onclick="copyToClipboard('${data.defanged_value}', 'Defanged IOC')" class="ml-2 px-2 py-1 text-[11px] font-semibold rounded bg-slate-200 hover:bg-slate-300 text-slate-800 transition-colors">
                                Salin
                            </button>
                        </div>
                    </div>

                    <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                        <span class="text-[11px] font-semibold text-red-800 uppercase block mb-1">Analisis Risiko Keamanan</span>
                        <p class="text-xs text-red-900 leading-relaxed">${data.risk_explanation}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                            <span class="text-slate-500 text-[10px] block">Tipe Indikator</span>
                            <strong class="text-slate-900">${data.type_label}</strong>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                            <span class="text-slate-500 text-[10px] block">Tingkat Keparahan</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border ${data.severity_color}">
                                ${data.severity_badge}
                            </span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                            <span class="text-slate-500 text-[10px] block">Akun Pelapor</span>
                            <span class="text-blue-600 font-medium">@${data.source_user}</span>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                            <span class="text-slate-500 text-[10px] block">Waktu Laporan</span>
                            <span class="text-slate-800">${data.source_date}</span>
                        </div>
                    </div>

                    ${data.tags && data.tags.length > 0 ? `
                        <div>
                            <span class="text-slate-500 text-[10px] block mb-1">Tags Asli:</span>
                            <div class="flex flex-wrap gap-1">
                                ${data.tags.map(t => `<span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border">#${t.replace('#', '')}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}

                    ${data.source_tweet ? `
                        <div class="pt-2">
                            <a href="${data.source_tweet}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline text-xs inline-flex items-center">
                                Lihat Laporan Asli di X (Twitter) &rarr;
                            </a>
                        </div>
                    ` : ''}
                </div>
            `;
        } catch (err) {
            content.innerHTML = `<div class="p-4 text-center text-red-600">Gagal memuat detail IOC.</div>`;
        }
    }

    function closeDetailModal() {
        document.getElementById('detail-modal').classList.add('hidden');
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL TAMBAH IOC MANUAL & TAMBAH WATCHLIST
    |--------------------------------------------------------------------------
    */
    function openAddIocModal() { document.getElementById('add-ioc-modal').classList.remove('hidden'); }
    function closeAddIocModal() { document.getElementById('add-ioc-modal').classList.add('hidden'); }

    function openAddWatchlistModal() { document.getElementById('add-watchlist-modal').classList.remove('hidden'); }
    function closeAddWatchlistModal() { document.getElementById('add-watchlist-modal').classList.add('hidden'); }

    async function handleAddIoc(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch('{{ route("threat-iocs.store") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: formData
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message);
                closeAddIocModal();
                form.reset();
                fetchTableData();
            } else {
                showToast(data.message || 'Gagal menyimpan IOC', 'error');
            }
        } catch (err) {
            showToast('Terjadi kesalahan sistem saat menyimpan IOC', 'error');
        }
    }

    async function handleAddWatchlist(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch('{{ route("watchlists.store") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: formData
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message);
                closeAddWatchlistModal();
                form.reset();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Gagal menyimpan watchlist', 'error');
            }
        } catch (err) {
            showToast('Terjadi kesalahan sistem saat menyimpan watchlist', 'error');
        }
    }

    async function toggleWatchlistStatus(id, btn) {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            const response = await fetch(`{{ url('/watchlists') }}/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                }
            });
            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            showToast('Gagal mengubah status', 'error');
        }
    }

    async function deleteWatchlist(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus indikator ini dari daftar?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch(`{{ url('/watchlists') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                }
            });
            const data = await response.json();
            if (response.ok && data.success) {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            showToast('Gagal menghapus entri', 'error');
        }
    }

    async function refreshScanHistory() {
        try {
            const response = await fetch('{{ route("scanner.history") }}');
            const json = await response.json();
            if (json.success && json.data) {
                const tbody = document.getElementById('scan-history-tbody');
                if (!tbody) return;

                tbody.innerHTML = json.data.slice(0, 6).map(scan => `
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                            Baru saja
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-800 max-w-xs truncate">
                            ${scan.query}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap uppercase text-[10px] font-semibold text-slate-600">
                            ${scan.query_type}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border ${getVerdictColor(scan.verdict)}">
                                ${getVerdictLabel(scan.verdict)}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                            <span class="${scan.risk_score >= 70 ? 'text-red-600 font-bold' : (scan.risk_score > 0 ? 'text-amber-600' : 'text-emerald-600')}">
                                ${scan.risk_score}/100
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <button type="button" onclick="testSample('${scan.query.replace(/'/g, "\\'")}')" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                Cek Ulang
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (err) {
            console.error(err);
        }
    }

    function getVerdictColor(verdict) {
        if (verdict === 'malicious') return 'text-red-700 bg-red-50 border-red-200';
        if (verdict === 'watchlist') return 'text-amber-700 bg-amber-50 border-amber-200';
        if (verdict === 'whitelist') return 'text-blue-700 bg-blue-50 border-blue-200';
        return 'text-emerald-700 bg-emerald-50 border-emerald-200';
    }

    function getVerdictLabel(verdict) {
        if (verdict === 'malicious') return 'Terindikasi Berbahaya';
        if (verdict === 'watchlist') return 'Watchlist Internal';
        if (verdict === 'whitelist') return 'Whitelist Terpercaya';
        return 'Aman / Bersih';
    }
</script>
@endpush
