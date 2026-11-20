@forelse ($iocs as $ioc)
    <tr class="hover:bg-slate-50/80 transition-colors border-b border-slate-100 last:border-0 group">
        <!-- Tipe IOC -->
        <td class="px-4 py-3 whitespace-nowrap text-xs">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold tracking-wide
                @if($ioc->type === 'url') bg-blue-50 text-blue-700 border border-blue-200
                @elseif($ioc->type === 'domain') bg-purple-50 text-purple-700 border border-purple-200
                @elseif($ioc->type === 'ip') bg-emerald-50 text-emerald-700 border border-emerald-200
                @else bg-amber-50 text-amber-700 border border-amber-200
                @endif">
                {{ $ioc->type_label }}
            </span>
        </td>

        <!-- Nilai IOC (Defanged - Bukan Hyperlink Aktif demi keamanan) -->
        <td class="px-4 py-3 text-xs">
            <div class="flex items-center space-x-2 max-w-md lg:max-w-xl">
                <code class="font-mono text-[11px] text-slate-800 bg-slate-100 px-2 py-1 rounded border border-slate-200 truncate select-all" title="Defanged: {{ $ioc->defanged_value }}">
                    {{ $ioc->defanged_value }}
                </code>
                <button type="button" 
                        onclick="copyToClipboard('{{ addslashes($ioc->defanged_value) }}', 'Defanged IOC')"
                        title="Salin string defanged"
                        class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded transition-colors opacity-70 group-hover:opacity-100 flex-shrink-0">
                    <!-- Copy Icon -->
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
            @if($ioc->host_domain && $ioc->type === 'url')
                <div class="text-[10px] text-slate-400 font-mono mt-0.5 pl-1">
                    Host: {{ $ioc->host_domain }}
                </div>
            @endif
        </td>

        <!-- Tags / Kategori Ancaman -->
        <td class="px-4 py-3 whitespace-nowrap text-xs">
            <div class="flex flex-wrap gap-1 max-w-xs">
                @if(!empty($ioc->tags) && is_array($ioc->tags))
                    @foreach(array_slice($ioc->tags, 0, 3) as $tag)
                        <button type="button" 
                                onclick="filterByTag('{{ ltrim($tag, '#') }}')"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 transition-colors">
                            #{{ ltrim($tag, '#') }}
                        </button>
                    @endforeach
                    @if(count($ioc->tags) > 3)
                        <span class="text-[10px] text-slate-400 self-center">+{{ count($ioc->tags) - 3 }}</span>
                    @endif
                @else
                    <span class="text-[11px] text-slate-400 italic">{{ $ioc->category ?: 'General' }}</span>
                @endif
            </div>
        </td>

        <!-- Tingkat Keparahan (Severity) -->
        <td class="px-4 py-3 whitespace-nowrap text-xs">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $ioc->severity_color }}">
                {{ $ioc->severity_badge }}
            </span>
        </td>

        <!-- Sumber & Waktu Pelaporan -->
        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">
            <div class="flex flex-col">
                <span class="text-slate-800 font-medium text-[11px]">
                    @if($ioc->source_user)
                        <span class="text-blue-600">@</span>{{ $ioc->source_user }}
                    @else
                        TweetFeed Bot
                    @endif
                </span>
                <span class="text-[10px] text-slate-400" title="{{ $ioc->source_date ? $ioc->source_date->format('Y-m-d H:i:s') : '' }}">
                    {{ $ioc->source_date ? $ioc->source_date->diffForHumans() : '-' }}
                </span>
            </div>
        </td>

        <!-- Tombol Aksi (Detail) -->
        <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
            <button type="button"
                    onclick="viewIocDetail({{ $ioc->id }})"
                    class="inline-flex items-center px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors border border-slate-200">
                Detail
                <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
            <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Tidak ada indikator ancaman yang cocok dengan filter atau kata kunci saat ini.
        </td>
    </tr>
@endforelse
