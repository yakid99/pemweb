<?php

namespace App\Http\Controllers;

use App\Models\ApiSyncState;
use App\Models\ScanHistory;
use App\Models\ThreatIoc;
use App\Models\Watchlist;
use App\Services\ThreatIntelligenceService;
use App\Services\TweetFeedSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected ThreatIntelligenceService $threatService,
        protected TweetFeedSyncService $syncService
    ) {}

    public function index(Request $request)
    {
        // 1. Cek Auto-Sync Cache 15 Menit (Background/Quick Check)
        $this->syncService->checkAndAutoSync(15);

        $now = now('Asia/Jakarta');

        // 2. KPI Cards Data
        $totalActiveIocs = ThreatIoc::active()->count();
        $maliciousUrlsCount = ThreatIoc::where('type', 'url')->active()->count();
        $maliciousIpsCount = ThreatIoc::where('type', 'ip')->active()->count();
        $totalScansCount = ScanHistory::count();

        // Cari Kategori / Tag Paling Aktif
        $topTagRow = DB::table('threat_iocs')
            ->select('category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $topThreatCategory = $topTagRow ? $topTagRow->category : 'Phishing';
        $topCategoryCount = $topTagRow ? $topTagRow->total : 0;

        // 3. Data Grafik Distribusi Tipe IOC (url, domain, ip, sha256, md5)
        $typeDistribution = ThreatIoc::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $typeLabels = ['url' => 'URL', 'domain' => 'Domain', 'ip' => 'IP Address', 'sha256' => 'SHA-256', 'md5' => 'MD5'];
        $chartTypeLabels = [];
        $chartTypeValues = [];
        $chartTypeKeys = [];

        foreach ($typeLabels as $key => $label) {
            $chartTypeKeys[] = $key;
            $chartTypeLabels[] = $label;
            $chartTypeValues[] = (int) ($typeDistribution[$key] ?? 0);
        }

        // 4. Data Grafik Top 5 Kategori / Tag Ancaman
        $topCategories = DB::table('threat_iocs')
            ->select('category', DB::raw('COUNT(*) as total'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $chartCatLabels = $topCategories->pluck('category')->toArray();
        $chartCatValues = $topCategories->pluck('total')->toArray();

        // 5. Data Grafik Tren Laporan 24 Jam Terakhir
        $startTime = $now->copy()->subHours(23)->startOfHour();

        $hourlyData = ThreatIoc::selectRaw(
            "DATE_FORMAT(source_date, '%Y-%m-%d %H:00:00') as bucket, COUNT(*) as total"
        )
        ->whereBetween('source_date', [$startTime, $now])
        ->groupBy('bucket')
        ->pluck('total', 'bucket');

        $trendLabels = [];
        $trendValues = [];

        for ($i = 23; $i >= 0; $i--) {
            $h = $now->copy()->subHours($i)->startOfHour();
            $key = $h->format('Y-m-d H:00:00');
            $trendLabels[] = $h->format('H:i');
            $trendValues[] = (int) ($hourlyData[$key] ?? 0);
        }

        // 6. Sampel Contoh Link untuk Hero Section
        $sampleMalicious = $this->threatService->getSampleMalicious();
        $sampleSafe = $this->threatService->getSampleSafe();

        // 7. Tabel IOC Terkelompok dengan Filter & Pagination
        $iocQuery = ThreatIoc::query();

        if ($request->filled('type') && $request->type !== 'all' && $request->type !== 'semua') {
            $iocQuery->byType($request->type);
        }

        if ($request->filled('tag')) {
            $tag = trim($request->tag);
            $iocQuery->whereRaw('CAST(tags AS CHAR) LIKE ?', ['%' . ltrim($tag, '#') . '%']);
        }

        if ($request->filled('search')) {
            $iocQuery->search($request->search);
        }

        if ($request->filled('severity')) {
            $iocQuery->where('severity', $request->severity);
        }

        $iocs = $iocQuery->orderByDesc('source_date')->paginate(12)->withQueryString();

        // 8. Riwayat Pengecekan Terbaru (Scan History)
        $recentScans = ScanHistory::orderByDesc('id')->limit(6)->get();

        // 9. Watchlists Terbaru
        $watchlists = Watchlist::orderByDesc('id')->limit(8)->get();

        // 10. Status Sinkronisasi
        $syncState = ApiSyncState::find(1);
        $lastUpdatedText = $this->syncService->getLastUpdatedText();

        return view('dashboard', compact(
            'totalActiveIocs',
            'maliciousUrlsCount',
            'maliciousIpsCount',
            'topThreatCategory',
            'topCategoryCount',
            'totalScansCount',
            'chartTypeKeys',
            'chartTypeLabels',
            'chartTypeValues',
            'chartCatLabels',
            'chartCatValues',
            'trendLabels',
            'trendValues',
            'sampleMalicious',
            'sampleSafe',
            'iocs',
            'recentScans',
            'watchlists',
            'syncState',
            'lastUpdatedText'
        ));
    }
}
