<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ThreatMetrics — Cyber Threat Intelligence & Link Scanner')</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#F0F9FF',
                            100: '#E0F2FE',
                            500: '#0EA5E9',
                            600: '#0284C7',
                            700: '#0369A1',
                            900: '#0C4A6E',
                        },
                        soc: {
                            danger: '#DC2626',
                            warning: '#D97706',
                            clean: '#059669',
                            slate: '#0F172A',
                            surface: '#FFFFFF',
                            subtle: '#F8FAFC',
                            border: '#E2E8F0',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Subtle scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        /* Tooltip simple styling */
        .soc-tooltip {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: help;
        }
        .soc-tooltip .soc-tooltip-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #0F172A;
            color: #F8FAFC;
            text-align: left;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 11px;
            line-height: 1.4;
            white-space: normal;
            width: 240px;
            z-index: 50;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transition: opacity 0.2s, visibility 0.2s;
            pointer-events: none;
        }
        .soc-tooltip:hover .soc-tooltip-content {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body class="min-h-full flex flex-col bg-slate-50 text-slate-900 selection:bg-blue-100 selection:text-blue-900">

    <!-- Top Navigation Bar (Editorial SOC Aesthetic) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm backdrop-blur-sm bg-white/95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo & Brand Identity -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 group">
                        <div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-white shadow-sm group-hover:bg-blue-600 transition-colors">
                            <!-- Shield Threat Radar Icon -->
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-lg font-bold tracking-tight text-slate-900 group-hover:text-blue-600 transition-colors">ThreatMetrics</span>
                            <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">CTI v2.4</span>
                        </div>
                    </a>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex items-center space-x-1 pl-6">
                        <a href="{{ route('dashboard') }}" class="px-3 py-1.5 text-xs font-semibold rounded-md {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }} transition-colors">
                            Dashboard & Scanner
                        </a>
                        <a href="#tabel-ioc" class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors">
                            Feed IOC
                        </a>
                        <a href="#watchlist-section" class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors">
                            Watchlist / Whitelist
                        </a>
                        <a href="{{ route('reports.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors">
                            Ekspor Laporan
                        </a>
                    </nav>
                </div>

                <!-- Right Utility Bar: Status, Last Update, Manual Sync, Guide -->
                <div class="flex items-center space-x-3">

                    <!-- Live TweetFeed Status Indicator -->
                    <div class="hidden lg:flex items-center space-x-2 px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-xs">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-[11px] font-medium text-slate-700">TweetFeed API: <strong class="text-emerald-700">ONLINE</strong></span>
                    </div>

                    <!-- Terakhir Diperbarui -->
                    <div id="last-updated-badge" class="hidden sm:flex items-center text-[11px] text-slate-500 font-medium">
                        <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="last-updated-text">{{ $lastUpdatedText ?? 'Sinkronisasi Aktif' }}</span>
                    </div>

                    <!-- Tombol Manual Sync -->
                    <button id="btn-sync-manual" onclick="triggerManualSync()" title="Sinkronkan data terbaru dari TweetFeed API (Cache 15 menit)" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500">
                        <svg id="sync-icon" class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span id="sync-button-text">Sync Manual</span>
                    </button>

                    <!-- Tombol Panduan & Edukasi (Modal Trigger) -->
                    <button onclick="openGuideModal()" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 shadow-sm transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Panduan & Edukasi</span>
                    </button>

                </div>
            </div>
        </div>
    </header>

    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Main Content Area -->
    <main class="flex-grow">
        @if (session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-900">&times;</button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Modern SOC Footer -->
    <footer class="bg-white border-t border-slate-200 mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-slate-900">ThreatMetrics</span>
                    <span>&bull;</span>
                    <span>Platform Cyber Threat Intelligence & Link Scanner</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Sumber Data: <strong class="text-slate-700">TweetFeed.live API</strong></span>
                    <span>&bull;</span>
                    <span>Arsitektur: <strong class="text-slate-700">Laravel 13 + MySQL + SOC Heuristics</strong></span>
                    <span>&bull;</span>
                    <span>Defanged Security Mode: <strong class="text-emerald-600">Aktif</strong></span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 text-[11px] text-slate-400 text-center">
                Peringatan: Seluruh data IOC bersumber dari laporan analis komunitas keamanan siber global. Dilarang mengklik atau mengeksekusi langsung tautan mentah tanpa isolasi lingkungan sandbox yang terverifikasi.
            </div>
        </div>
    </footer>

    <!-- MODAL PANDUAN & EDUKASI ONBOARDING -->
    <div id="guide-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-labelledby="guide-title" role="dialog" aria-modal="true">
        <div class="min-h-screen px-4 text-center flex items-center justify-center p-4">
            <div class="relative bg-white rounded-xl max-w-2xl w-full p-6 text-left shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900" id="guide-title">Panduan & Cara Kerja ThreatMetrics</h3>
                            <p class="text-xs text-slate-500">Membantu Anda memahami cara membaca data intelijen ancaman siber dan memeriksa link secara aman.</p>
                        </div>
                    </div>
                    <button onclick="closeGuideModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body: Tabs / Sections -->
                <div class="mt-5 space-y-5 text-xs text-slate-600 leading-relaxed max-h-[65vh] overflow-y-auto pr-1">
                    
                    <!-- Section 1: Cara Kerja Scanner -->
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                        <h4 class="font-bold text-slate-900 flex items-center mb-1.5 text-xs">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-800 inline-flex items-center justify-center mr-2 text-[11px] font-bold">1</span>
                            Normalisasi Link & Pencocokan Host
                        </h4>
                        <p class="text-slate-600">
                            Ketika Anda memasukkan URL lengkap seperti <code class="font-mono text-[11px] bg-white px-1.5 py-0.5 rounded border border-slate-300">https://phishing-site.com/login?id=123</code>, sistem tidak hanya mencocokkan URL persisnya, melainkan secara cerdas mengekstrak <strong>domain utamanya</strong> (<code class="font-mono text-[11px] bg-white px-1 py-0.5 rounded border border-slate-300">phishing-site.com</code>). Hal ini menjamin ancaman tetap terdeteksi meski penyerang mengubah path atau token URL.
                        </p>
                    </div>

                    <!-- Section 2: Defanging Keamanan -->
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                        <h4 class="font-bold text-slate-900 flex items-center mb-1.5 text-xs">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-800 inline-flex items-center justify-center mr-2 text-[11px] font-bold">2</span>
                            Prinsip Defanging Keamanan (Anti-Click)
                        </h4>
                        <p class="text-slate-600">
                            Untuk melindungi pengguna dari bahaya tidak sengaja mengeklik tautan berbahaya, seluruh indikator disajikan dalam format <em>defanged</em>:
                        </p>
                        <ul class="list-disc list-inside mt-1.5 space-y-1 text-slate-600 pl-2">
                            <li>Protokol diubah: <code class="font-mono text-[11px] text-red-600 bg-red-50 px-1 rounded">http://</code> menjadi <code class="font-mono text-[11px] text-slate-800 bg-white border px-1 rounded">hxxp://</code></li>
                            <li>Titik domain/IP diamankan: <code class="font-mono text-[11px] text-red-600 bg-red-50 px-1 rounded">evil.com</code> menjadi <code class="font-mono text-[11px] text-slate-800 bg-white border px-1 rounded">evil[.]com</code></li>
                            <li>Tautan tidak pernah dibuat sebagai hyperlink aktif (dapat disalin via tombol <strong>"Salin Defanged"</strong>).</li>
                        </ul>
                    </div>

                    <!-- Section 3: Istilah Teknis (Glossary) -->
                    <div class="p-3.5 rounded-lg bg-blue-50/50 border border-blue-200">
                        <h4 class="font-bold text-slate-900 mb-2 text-xs flex items-center text-blue-900">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Glosarium Istilah Keamanan Siber
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                            <div class="bg-white p-2 rounded border border-blue-100">
                                <strong class="text-slate-900">IOC (Indicator of Compromise):</strong> Bukti digital forensik (seperti URL, IP, atau hash file) yang menandakan adanya infeksi malware atau serangan siber.
                            </div>
                            <div class="bg-white p-2 rounded border border-blue-100">
                                <strong class="text-slate-900">C2 (Command & Control):</strong> Server terpusat milik penyerang yang digunakan untuk mengendalikan ribuan komputer/botnet yang telah terinfeksi.
                            </div>
                            <div class="bg-white p-2 rounded border border-blue-100">
                                <strong class="text-slate-900">SHA-256 / MD5:</strong> Sidik jari kriptografi unik sebuah file. Memungkinkan pendeteksian malware meski nama filenya telah diganti.
                            </div>
                            <div class="bg-white p-2 rounded border border-blue-100">
                                <strong class="text-slate-900">Watchlist vs Whitelist:</strong> Watchlist adalah daftar ancaman kustom yang diawasi analis, sedangkan Whitelist adalah domain/IP resmi yang telah dijamin aman.
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Mekanisme Sinkronisasi -->
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                        <h4 class="font-bold text-slate-900 flex items-center mb-1.5 text-xs">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-800 inline-flex items-center justify-center mr-2 text-[11px] font-bold">3</span>
                            Backend Caching & Sinkronisasi 15 Menit
                        </h4>
                        <p class="text-slate-600">
                            Aplikasi menerapkan caching database lokal MySQL dengan sinkronisasi otomatis berkala setiap 15 menit dari TweetFeed API. Hal ini memastikan pemindaian berlangsung cepat tanpa hambatan CORS di browser pengguna. Tombol <strong>"Sync Manual"</strong> di header dapat digunakan kapan saja untuk menarik data terbaru.
                        </p>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="mt-6 pt-3 border-t border-slate-100 flex justify-end">
                    <button onclick="closeGuideModal()" class="px-4 py-2 text-xs font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm">
                        Saya Mengerti
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Script Global: Modal, Sync & Toast -->
    <script>
        function openGuideModal() {
            document.getElementById('guide-modal').classList.remove('hidden');
        }

        function closeGuideModal() {
            document.getElementById('guide-modal').classList.add('hidden');
        }

        // Close modal on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeGuideModal();
            }
        });

        // Toast notification utility
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const isError = type === 'error';
            const bgColor = isError ? 'bg-red-900 text-white border-red-700' : 'bg-slate-900 text-white border-slate-800';
            const iconSvg = isError 
                ? '<svg class="w-4 h-4 text-red-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<svg class="w-4 h-4 text-emerald-400 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

            toast.className = `flex items-center px-4 py-3 rounded-lg shadow-lg border text-xs font-medium ${bgColor} transform transition-all duration-300 translate-y-2 opacity-0 pointer-events-auto`;
            toast.innerHTML = `${iconSvg}<span>${message}</span>`;
            
            container.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            // Animate out
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Copy to clipboard with toast
        function copyToClipboard(text, label = 'Teks') {
            navigator.clipboard.writeText(text).then(() => {
                showToast(`${label} berhasil disalin ke clipboard!`);
            }).catch(() => {
                showToast('Gagal menyalin ke clipboard', 'error');
            });
        }

        // Trigger Manual Sync via AJAX
        async function triggerManualSync() {
            const btn = document.getElementById('btn-sync-manual');
            const icon = document.getElementById('sync-icon');
            const text = document.getElementById('sync-button-text');
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            btn.disabled = true;
            icon.classList.add('animate-spin');
            text.textContent = 'Menyinkronkan...';

            try {
                const response = await fetch('{{ route("sync") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ mode: 'today' })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showToast(data.message);
                    if (data.last_updated) {
                        const updatedBadge = document.getElementById('last-updated-text');
                        if (updatedBadge) updatedBadge.textContent = data.last_updated;
                    }
                    // Refresh data table or page if needed
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Sinkronisasi gagal dilakukan', 'error');
                }
            } catch (err) {
                showToast('Gagal terhubung ke endpoint sinkronisasi backend', 'error');
            } finally {
                btn.disabled = false;
                icon.classList.remove('animate-spin');
                text.textContent = 'Sync Manual';
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
