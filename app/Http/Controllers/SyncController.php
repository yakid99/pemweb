<?php

namespace App\Http\Controllers;

use App\Services\TweetFeedSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SyncController extends Controller
{
    /**
     * Sinkronisasi data TweetFeed API (Manual & Background)
     */
    public function __invoke(
        Request $request,
        TweetFeedSyncService $syncService
    ) {
        try {
            $mode = $request->input('mode', 'today');
            $result = $syncService->sync($mode);

            $received = $result['received'] ?? 0;
            $inserted = $result['inserted'] ?? 0;

            $message = "Sinkronisasi berhasil! Data diterima: {$received}, IOC baru tersimpan: {$inserted}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'last_updated' => $syncService->getLastUpdatedText(),
                    'received' => $received,
                    'inserted' => $inserted,
                ]);
            }

            return back()->with('success', $message);

        } catch (Throwable $e) {
            report($e);

            $errorMessage = 'Sinkronisasi gagal: ' . $e->getMessage();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }
}
