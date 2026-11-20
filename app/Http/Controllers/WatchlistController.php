<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\ThreatIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    /**
     * Menampilkan daftar watchlist & whitelist
     */
    public function index(Request $request)
    {
        $query = Watchlist::query();

        if ($request->filled('list_type')) {
            $query->where('list_type', $request->string('list_type')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('search')) {
            $term = '%' . trim($request->string('search')->toString()) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('value', 'like', $term)
                    ->orWhere('defanged_value', 'like', $term)
                    ->orWhere('category', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $watchlists = $query->orderByDesc('id')->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($watchlists);
        }

        return view('watchlists.index', compact('watchlists'));
    }

    /**
     * Menyimpan indikator baru ke Watchlist atau Whitelist
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:url,domain,ip,sha256,md5',
            'value' => 'required|string|max:500',
            'list_type' => 'required|in:watchlist,whitelist',
            'category' => 'nullable|string|max:100',
            'severity' => 'required|in:info,warning,critical,trusted',
            'notes' => 'nullable|string|max:1000',
        ]);

        $threatService = app(ThreatIntelligenceService::class);
        $normalized = $threatService->normalizeInput($data['value']);

        $cleanValue = $normalized['clean'];
        $hostDomain = $normalized['host'];
        $defanged = ThreatIntelligenceService::defang($data['type'], $cleanValue);

        $watchlist = Watchlist::create([
            'type' => $data['type'],
            'value' => $cleanValue,
            'defanged_value' => $defanged,
            'host_domain' => $hostDomain,
            'list_type' => $data['list_type'],
            'category' => $data['category'] ?: ($data['list_type'] === 'whitelist' ? 'Trusted Asset' : 'Suspicious Asset'),
            'severity' => $data['severity'],
            'notes' => $data['notes'],
            'created_by' => 'SecOps Analyst',
            'is_active' => true,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Indikator berhasil ditambahkan ke ' . ucfirst($data['list_type']),
                'data' => $watchlist,
            ]);
        }

        return back()->with('success', 'Indikator berhasil ditambahkan ke ' . ucfirst($data['list_type']));
    }

    /**
     * Memperbarui catatan / metadata
     */
    public function update(Request $request, Watchlist $watchlist)
    {
        $data = $request->validate([
            'category' => 'nullable|string|max:100',
            'severity' => 'required|in:info,warning,critical,trusted',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $watchlist->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $watchlist,
            ]);
        }

        return back()->with('success', 'Data watchlist berhasil diperbarui');
    }

    /**
     * Toggle status aktif / non-aktif
     */
    public function toggle(Watchlist $watchlist): JsonResponse
    {
        $watchlist->is_active = !$watchlist->is_active;
        $watchlist->save();

        return response()->json([
            'success' => true,
            'is_active' => $watchlist->is_active,
            'message' => 'Status berhasil diubah menjadi: ' . ($watchlist->is_active ? 'Aktif' : 'Nonaktif'),
        ]);
    }

    /**
     * Menghapus indikator dari watchlist / whitelist
     */
    public function destroy(Request $request, Watchlist $watchlist)
    {
        $watchlist->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Indikator berhasil dihapus',
            ]);
        }

        return back()->with('success', 'Indikator berhasil dihapus');
    }
}
