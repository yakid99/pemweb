@extends('layouts.app')

@section('title', 'Manajemen Watchlist & Whitelist — ThreatMetrics')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Manajemen Watchlist & Whitelist Manual</h1>
            <p class="text-xs text-slate-500">Kelola indikator ancaman internal organisasi serta domain yang disetujui aman secara resmi.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
                &larr; Kembali ke Dashboard
            </a>
            <button type="button" onclick="openAddWatchlistModal()" class="px-3.5 py-2 text-xs font-semibold rounded-md bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors">
                + Tambah Indikator Baru
            </button>
        </div>
    </div>

    <!-- Watchlist Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-5">
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
                                        class="inline-flex items-center text-[11px] font-medium {{ $wl->is_active ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-500 bg-slate-100 border-slate-200' }} px-2 py-0.5 rounded border transition-colors">
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

        <div class="mt-4 pt-3 border-t border-slate-100">
            {{ $watchlists->links() }}
        </div>
    </div>
</div>
@endsection
