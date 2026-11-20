@if ($iocs->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between px-4 py-3 bg-white border-t border-slate-200 sm:px-6">
        <div class="hidden sm:block">
            <p class="text-xs text-slate-600">
                Menampilkan <span class="font-medium text-slate-900">{{ $iocs->firstItem() ?? 0 }}</span> sampai
                <span class="font-medium text-slate-900">{{ $iocs->lastItem() ?? 0 }}</span> dari
                <span class="font-medium text-slate-900">{{ number_format($iocs->total()) }}</span> total IOC
            </p>
        </div>

        <div class="flex justify-between sm:justify-end space-x-2 w-full sm:w-auto">
            {{-- Previous Page Link --}}
            @if ($iocs->onFirstPage())
                <span class="px-2.5 py-1 text-xs text-slate-400 bg-slate-50 border border-slate-200 rounded cursor-not-allowed">
                    &larr; Sebelumnya
                </span>
            @else
                <a href="{{ $iocs->previousPageUrl() }}" onclick="loadPage(event, '{{ $iocs->previousPageUrl() }}')" class="px-2.5 py-1 text-xs text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 font-medium transition-colors">
                    &larr; Sebelumnya
                </a>
            @endif

            {{-- Next Page Link --}}
            @if ($iocs->hasMorePages())
                <a href="{{ $iocs->nextPageUrl() }}" onclick="loadPage(event, '{{ $iocs->nextPageUrl() }}')" class="px-2.5 py-1 text-xs text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 font-medium transition-colors">
                    Berikutnya &rarr;
                </a>
            @else
                <span class="px-2.5 py-1 text-xs text-slate-400 bg-slate-50 border border-slate-200 rounded cursor-not-allowed">
                    Berikutnya &rarr;
                </span>
            @endif
        </div>
    </nav>
@endif
