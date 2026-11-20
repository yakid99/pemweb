<?php

namespace App\Http\Controllers;

use App\Models\ScanHistory;
use App\Models\ThreatIoc;
use App\Services\ThreatIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerController extends Controller
{
    public function __construct(
        protected ThreatIntelligenceService $threatService
    ) {}

    /**
     * Menjalankan pemindaian link, domain, IP, atau hash
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:2000',
        ]);

        $query = trim($request->string('query')->toString());

        $result = $this->threatService->scan(
            $query,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Mengambil contoh indikator berbahaya dan aman untuk tombol quick test
     */
    public function samples(): JsonResponse
    {
        $malicious = $this->threatService->getSampleMalicious();
        $safe = $this->threatService->getSampleSafe();

        return response()->json([
            'malicious' => $malicious,
            'safe' => $safe,
        ]);
    }

    /**
     * Riwayat pemindaian terakhir
     */
    public function history(): JsonResponse
    {
        $history = ScanHistory::with('matchedThreatIoc')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
