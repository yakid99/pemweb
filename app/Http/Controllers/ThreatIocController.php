<?php

namespace App\Http\Controllers;

use App\Models\ThreatIoc;
use App\Services\ThreatIntelligenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreatIocController extends Controller
{
    /**
     * Menampilkan daftar IOC terkelompok dengan pencarian, filter, dan pagination
     */
    public function index(Request $request)
    {
        $query = ThreatIoc::query();

        // Filter Grup Tab: all, url, domain, ip, hash
        if ($request->filled('type')) {
            $type = strtolower($request->string('type')->toString());
            $query->byType($type);
        }

        // Filter Tag
        if ($request->filled('tag')) {
            $tag = trim($request->string('tag')->toString());
            $tag = ltrim($tag, '#');
            $query->whereRaw('CAST(tags AS CHAR) LIKE ?', ['%' . $tag . '%']);
        }

        // Filter Severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity')->toString());
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->active();
        }

        // Pencarian Teks
        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        $iocs = $query->orderByDesc('source_date')
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'html' => view('partials.ioc_table_rows', compact('iocs'))->render(),
                'pagination' => view('partials.pagination', compact('iocs'))->render(),
                'total' => $iocs->total(),
                'current_page' => $iocs->currentPage(),
                'last_page' => $iocs->lastPage(),
            ]);
        }

        return view('iocs.index', compact('iocs'));
    }

    /**
     * Menampilkan detail lengkap IOC untuk modal
     */
    public function show(ThreatIoc $threatIoc): JsonResponse
    {
        $threatService = app(ThreatIntelligenceService::class);
        $tags = is_array($threatIoc->tags) ? $threatIoc->tags : [];
        $riskExplanation = $threatService->generateRiskExplanation($tags, $threatIoc->type);
        $riskScore = $threatService->calculateRiskScore($threatIoc);

        return response()->json([
            'id' => $threatIoc->id,
            'type' => $threatIoc->type,
            'type_label' => $threatIoc->type_label,
            'value' => $threatIoc->value,
            'defanged_value' => $threatIoc->defanged_value,
            'host_domain' => $threatIoc->host_domain,
            'source_user' => $threatIoc->source_user ?: 'Anonim (TweetFeed)',
            'source_tweet' => $threatIoc->source_tweet,
            'tags' => $tags,
            'category' => $threatIoc->category ?: 'General Threat',
            'severity' => $threatIoc->severity,
            'severity_badge' => $threatIoc->severity_badge,
            'severity_color' => $threatIoc->severity_color,
            'status' => $threatIoc->status,
            'source_date' => $threatIoc->source_date ? $threatIoc->source_date->format('d M Y, H:i') . ' WIB' : '-',
            'time_ago' => $threatIoc->source_date ? $threatIoc->source_date->diffForHumans() : '-',
            'source' => ucfirst($threatIoc->source),
            'risk_score' => $riskScore,
            'risk_explanation' => $riskExplanation,
        ]);
    }

    /**
     * Menambahkan IOC manual
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:url,domain,ip,sha256,md5',
            'value' => 'required|string|max:2000',
            'source_user' => 'nullable|string|max:150',
            'source_tweet' => 'nullable|url|max:2000',
            'tags' => 'nullable|string|max:2000',
            'category' => 'nullable|string|max:100',
            'severity' => 'required|in:info,warning,critical',
            'status' => 'required|in:active,inactive,false_positive',
        ]);

        $threatService = app(ThreatIntelligenceService::class);
        $normalized = $threatService->normalizeInput($data['value']);
        $cleanValue = $normalized['clean'];
        $hostDomain = $normalized['host'];
        $defanged = ThreatIntelligenceService::defang($data['type'], $cleanValue);

        $tags = collect(explode(',', $data['tags'] ?? ''))
            ->map(fn($t) => trim($t))
            ->filter()
            ->values()
            ->all();

        $sourceKey = hash('sha256', 'manual|' . $data['type'] . '|' . $cleanValue . '|' . time());

        $ioc = ThreatIoc::create([
            'source_key' => $sourceKey,
            'type' => $data['type'],
            'value' => $cleanValue,
            'defanged_value' => $defanged,
            'host_domain' => $hostDomain,
            'source_user' => $data['source_user'] ?: 'Internal SecOps',
            'source_tweet' => $data['source_tweet'] ?: null,
            'tags' => $tags,
            'category' => $data['category'] ?: (isset($tags[0]) ? ltrim($tags[0], '#') : 'Manual Entry'),
            'severity' => $data['severity'],
            'status' => $data['status'],
            'source_date' => now('Asia/Jakarta'),
            'source' => 'manual',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Indikator manual berhasil disimpan',
                'data' => $ioc,
            ]);
        }

        return back()->with('success', 'Indikator manual berhasil disimpan.');
    }

    /**
     * Menghapus IOC
     */
    public function destroy(Request $request, ThreatIoc $threatIoc)
    {
        $threatIoc->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'IOC berhasil dihapus',
            ]);
        }

        return back()->with('success', 'IOC berhasil dihapus.');
    }
}
