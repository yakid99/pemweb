@extends('layouts.app')

@section('title', 'Database Feed IOC — ThreatMetrics')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Database Feed Indikator Ancaman (IOC)</h1>
            <p class="text-xs text-slate-500">Pencarian, filter, dan investigasi mendalam terhadap seluruh data feed intelijen ancaman siber.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 shadow-sm transition-colors">
            &larr; Kembali ke Dashboard & Scanner
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3">
        <form method="GET" action="{{ route('threat-iocs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Cari Indikator / Host</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Tipe IOC</label>
                <select name="type" class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-slate-900 focus:bg-white focus:outline-none">
                    <option value="">Semua Tipe</option>
                    <option value="url" {{ request('type') === 'url' ? 'selected' : '' }}>URL</option>
                    <option value="domain" {{ request('type') === 'domain' ? 'selected' : '' }}>Domain</option>
                    <option value="ip" {{ request('type') === 'ip' ? 'selected' : '' }}>IP Address</option>
                    <option value="hash" {{ request('type') === 'hash' ? 'selected' : '' }}>File Hash (SHA256/MD5)</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Tingkat Severity</label>
                <select name="severity" class="w-full bg-slate-50 border border-slate-300 rounded px-3 py-1.5 text-slate-900 focus:bg-white focus:outline-none">
                    <option value="">Semua Severity</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full px-4 py-2 text-xs font-semibold rounded bg-slate-900 hover:bg-slate-800 text-white transition-colors">
                    Terapkan Filter
                </button>
                <a href="{{ route('threat-iocs.index') }}" class="px-3 py-2 text-xs font-medium rounded border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Nilai Indikator (Defanged)</th>
                        <th class="px-4 py-3">Tags & Kategori</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Sumber & Waktu</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @include('partials.ioc_table_rows', ['iocs' => $iocs])
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200">
            @include('partials.pagination', ['iocs' => $iocs])
        </div>
    </div>
</div>
@endsection
