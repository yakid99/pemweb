
@extends('layouts.app')

@section('title', 'Laporan & Ekspor | ThreatMetrics')

@section('content')

@php

$typeLabels = [
    'url' => 'URL',
    'domain' => 'Domain',
    'ip' => 'Alamat IP',
    'sha256' => 'SHA-256',
    'md5' => 'MD5',
];

@endphp

<!-- HEADER -->

<div class="mb-7">

    <div class="flex items-center gap-2 mb-2">
        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>

        <span class="text-[10px] tracking-[3px] text-cyan-400 font-semibold">
            PUSAT PELAPORAN
        </span>
    </div>

    <h1 class="text-2xl lg:text-3xl font-bold text-white">
        Laporan & Ekspor Data
    </h1>

    <p class="text-sm text-slate-400 mt-2">
        Buat, filter, dan unduh laporan intelijen ancaman siber.
    </p>

</div>


<!-- STATISTIK -->

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

    <div class="panel p-5">
        <p class="text-xs text-slate-400">
            Total Indikator Ancaman
        </p>

        <h2 class="text-3xl font-bold text-white mt-3">
            {{ number_format($metrics['total']) }}
        </h2>

        <p class="text-[10px] text-slate-500 mt-2">
            Berdasarkan filter aktif
        </p>
    </div>

    <div class="panel p-5">
        <p class="text-xs text-slate-400">
            Indikator Aktif
        </p>

        <h2 class="text-3xl font-bold text-emerald-400 mt-3">
            {{ number_format($metrics['active']) }}
        </h2>

        <p class="text-[10px] text-slate-500 mt-2">
            Status aktif
        </p>
    </div>

    <div class="panel p-5">
        <p class="text-xs text-slate-400">
            Ancaman Kritis
        </p>

        <h2 class="text-3xl font-bold text-red-400 mt-3">
            {{ number_format($metrics['critical']) }}
        </h2>

        <p class="text-[10px] text-slate-500 mt-2">
            Tingkat keparahan kritis
        </p>
    </div>

    <div class="panel p-5">
        <p class="text-xs text-slate-400">
            Positif Palsu
        </p>

        <h2 class="text-3xl font-bold text-amber-400 mt-3">
            {{ number_format($metrics['false_positive']) }}
        </h2>

        <p class="text-[10px] text-slate-500 mt-2">
            Indikator yang ditandai positif palsu
        </p>
    </div>

</div>


<!-- FILTER DAN EKSPOR -->

<div class="panel p-5 mb-6">

    <div class="mb-5">

        <h2 class="text-sm font-semibold text-white">
            Pengaturan Laporan
        </h2>

        <p class="text-xs text-slate-500 mt-1">
            Tentukan data yang ingin dimasukkan ke laporan.
        </p>

    </div>


    <form
        action="{{ route('reports.index') }}"
        method="GET"
        class="space-y-5"
    >

        <!-- FILTER -->

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">

            <!-- PENCARIAN -->

            <div>

                <label class="block text-xs text-slate-400 mb-2">
                    Pencarian
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Cari IOC..."
                    class="w-full bg-dark-900 border border-slate-700
                           rounded-lg px-3 py-3 text-sm text-white
                           focus:outline-none focus:border-cyan-400"
                >

            </div>


            <!-- JENIS IOC -->

            <div>

                <label class="block text-xs text-slate-400 mb-2">
                    Jenis IOC
                </label>

                <select
                    name="type"
                    class="w-full bg-dark-900 border border-slate-700
                           rounded-lg px-3 py-3 text-sm text-white"
                >

                    <option value="">Semua Jenis</option>

                    @foreach($typeLabels as $type => $label)

                        <option
                            value="{{ $type }}"
                            @selected(($filters['type'] ?? '') === $type)
                        >
                            {{ $label }}
                        </option>

                    @endforeach

                </select>

            </div>


            <!-- STATUS -->

            <div>

                <label class="block text-xs text-slate-400 mb-2">
                    Status IOC
                </label>

                <select
                    name="status"
                    class="w-full bg-dark-900 border border-slate-700
                           rounded-lg px-3 py-3 text-sm text-white"
                >

                    <option value="">Semua Status</option>

                    <option value="active"
                        @selected(($filters['status'] ?? '') === 'active')>
                        Aktif
                    </option>

                    <option value="inactive"
                        @selected(($filters['status'] ?? '') === 'inactive')>
                        Tidak Aktif
                    </option>

                    <option value="false_positive"
                        @selected(($filters['status'] ?? '') === 'false_positive')>
                        Positif Palsu
                    </option>

                </select>

            </div>


            <!-- TANGGAL AWAL -->

            <div>

                <label class="block text-xs text-slate-400 mb-2">
                    Dari Tanggal
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full bg-dark-900 border border-slate-700
                           rounded-lg px-3 py-3 text-sm text-white"
                >

            </div>


            <!-- TANGGAL AKHIR -->

            <div>

                <label class="block text-xs text-slate-400 mb-2">
                    Sampai Tanggal
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}"
                    class="w-full bg-dark-900 border border-slate-700
                           rounded-lg px-3 py-3 text-sm text-white"
                >

            </div>

        </div>


        <!-- TOMBOL FILTER -->

        <div class="flex flex-wrap gap-3">

            <button
                type="submit"
                class="px-5 py-2.5 rounded-lg bg-cyan-500
                       hover:bg-cyan-400 text-slate-950
                       font-bold text-xs"
            >
                Terapkan Filter
            </button>

            <a
                href="{{ route('reports.index') }}"
                class="px-5 py-2.5 rounded-lg border border-slate-700
                       text-slate-400 hover:text-white text-xs"
            >
                Atur Ulang
            </a>

        </div>


        <!-- GARIS PEMISAH -->

        <div class="border-t border-slate-800 pt-5">

            <h3 class="text-sm font-semibold text-white mb-2">
                Format Ekspor Laporan
            </h3>

            <p class="text-xs text-slate-500 mb-5">
                Semua ekspor menggunakan filter yang telah dipilih.
            </p>


            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">


                <!-- PDF -->

                <button
                    type="submit"
                    formaction="{{ route('reports.pdf') }}"
                    formtarget="_blank"
                    class="flex items-center gap-4 p-4 rounded-xl
                           border border-red-500/20 bg-red-500/5
                           hover:bg-red-500/10 transition text-left"
                >

                    <div class="w-12 h-12 rounded-xl bg-red-500/10
                                flex items-center justify-center
                                text-red-400 text-xl">
                        PDF
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-white">
                            Ekspor PDF
                        </p>

                        <p class="text-[10px] text-slate-500 mt-1">
                            Laporan siap cetak A4
                        </p>

                    </div>

                </button>


                <!-- EXCEL SPSS -->

                <button
                    type="submit"
                    formaction="{{ route('reports.spss') }}"
                    formtarget="_blank"
                    class="flex items-center gap-4 p-4 rounded-xl
                           border border-emerald-500/20 bg-emerald-500/5
                           hover:bg-emerald-500/10 transition text-left"
                >

                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10
                                flex items-center justify-center
                                text-emerald-400 text-xl">
                        XLS
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-white">
                            Ekspor Excel SPSS
                        </p>

                        <p class="text-[10px] text-slate-500 mt-1">
                            Dataset numerik .xlsx
                        </p>

                    </div>

                </button>


                <!-- CSV -->

                <button
                    type="submit"
                    formaction="{{ route('reports.csv') }}"
                    formtarget="_blank"
                    class="flex items-center gap-4 p-4 rounded-xl
                           border border-cyan-500/20 bg-cyan-500/5
                           hover:bg-cyan-500/10 transition text-left"
                >

                    <div class="w-12 h-12 rounded-xl bg-cyan-500/10
                                flex items-center justify-center
                                text-cyan-400 text-xl">
                        CSV
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-white">
                            Ekspor CSV
                        </p>

                        <p class="text-[10px] text-slate-500 mt-1">
                            Data untuk pengolahan lanjutan
                        </p>

                    </div>

                </button>

                <!-- SPSS SYNTAX -->
                <button
                    type="submit"
                    formaction="{{ route('reports.spss-syntax') }}"
                    formtarget="_blank"
                    class="flex items-center gap-4 p-4 rounded-xl
                        border border-blue-500/20 bg-blue-500/5
                        hover:bg-blue-500/10 transition text-left"
                >

                    <div class="w-12 h-12 rounded-xl bg-blue-500/10
                                flex items-center justify-center
                                text-blue-400 text-xl">

                        <i class="fa-solid fa-code"></i>

                    </div>

                    <div>

                        <p class="text-sm font-semibold text-white">
                            SPSS Syntax
                        </p>

                        <p class="text-[10px] text-slate-500 mt-1">
                            Pengaturan variabel otomatis (.sps)
                        </p>

                    </div>

                </button>



            </div>

        </div>

    </form>

</div>


<!-- DISTRIBUSI IOC -->

<div class="panel p-5">

    <div class="mb-5">

        <h2 class="text-sm font-semibold text-white">
            Distribusi Jenis Indikator
        </h2>

        <p class="text-xs text-slate-500 mt-1">
            Jumlah data berdasarkan jenis IOC dan filter aktif.
        </p>

    </div>


    <div class="space-y-5">

        @foreach($typeLabels as $type => $label)

            @php

                $count = $typeCounts[$type] ?? 0;

                $percentage = $metrics['total'] > 0
                    ? ($count / $metrics['total']) * 100
                    : 0;

                $barColors = [
                    'url' => 'bg-cyan-400',
                    'domain' => 'bg-blue-400',
                    'ip' => 'bg-emerald-400',
                    'sha256' => 'bg-amber-400',
                    'md5' => 'bg-purple-400',
                ];

            @endphp


            <div>

                <div class="flex justify-between items-center mb-2">

                    <span class="text-xs text-slate-300">
                        {{ $label }}
                    </span>

                    <span class="text-xs text-white font-semibold">
                        {{ number_format($count) }}
                        <span class="text-slate-500">
                            ({{ number_format($percentage, 1) }}%)
                        </span>
                    </span>

                </div>


                <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">

                    <div
                        class="h-full rounded-full {{ $barColors[$type] }}"
                        style="width: {{ $percentage }}%"
                    ></div>

                </div>

            </div>

        @endforeach

    </div>

</div>


<!-- CATATAN -->

<div class="mt-5 p-4 rounded-xl border border-cyan-500/20
            bg-cyan-500/5">

    <div class="flex gap-3">

        <span class="text-cyan-400 text-lg">ⓘ</span>

        <div>

            <h3 class="text-xs font-semibold text-cyan-400">
                Informasi Ekspor
            </h3>

            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                Data PDF digunakan untuk dokumentasi dan pelaporan.
                Format Excel SPSS menggunakan kode numerik untuk
                mendukung pengolahan data statistik. Pastikan data
                IOC telah diperiksa sebelum digunakan.
            </p>

        </div>

    </div>

</div>

@endsection
