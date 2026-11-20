<?php

namespace App\Services;

use App\Models\ApiSyncState;
use App\Models\ThreatIoc;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TweetFeedSyncService
{
    private string $baseUrl;

    public function __construct()
    {
        // Support both api.tweetfeed.live and tweetfeed.live/api fallback
        $this->baseUrl = rtrim(config('services.tweetfeed.base_url', 'https://api.tweetfeed.live/v1'), '/');
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent('ThreatMetrics/2.0 (Cyber Threat Intelligence Platform)')
            ->connectTimeout(8)
            ->timeout(25)
            ->retry(2, 500);
    }

    /**
     * Memeriksa apakah data cache sudah berumur lebih dari 15 menit.
     * Jika ya, otomatis jalankan sync.
     */
    public function checkAndAutoSync(int $maxAgeMinutes = 15): array
    {
        $state = $this->state();
        $lastSync = $state->last_success_at;

        $shouldSync = false;
        if (!$lastSync) {
            $shouldSync = true;
        } elseif ($lastSync->diffInMinutes(now('Asia/Jakarta')) >= $maxAgeMinutes) {
            $shouldSync = true;
        }

        if ($shouldSync) {
            try {
                return $this->sync('today');
            } catch (Throwable $e) {
                Log::warning('Auto-sync TweetFeed gagal (akan dicoba lagi nanti): ' . $e->getMessage());
            }
        }

        return [
            'synced' => false,
            'message' => 'Cache masih segar (kurang dari ' . $maxAgeMinutes . ' menit)',
            'last_sync' => $lastSync,
        ];
    }

    /**
     * Teks waktu pembaruan terakhir yang ramah pengguna
     * Contoh: "Terakhir diperbarui: 4 menit lalu"
     */
    public function getLastUpdatedText(): string
    {
        $state = $this->state();
        if (!$state->last_success_at) {
            // Jika belum ada record di api_sync_states, cek source_date terbaru di threat_iocs
            $latestIoc = ThreatIoc::latest('source_date')->first();
            if ($latestIoc && $latestIoc->source_date) {
                return 'Terakhir diperbarui: ' . $latestIoc->source_date->diffForHumans();
            }
            return 'Belum pernah disinkronkan';
        }

        return 'Terakhir diperbarui: ' . $state->last_success_at->diffForHumans();
    }

    /**
     * Menjalankan sinkronisasi data dari TweetFeed API
     * Mode: 'today' (default) atau 'week'
     */
    public function sync(string $mode = 'today'): array
    {
        $state = $this->state();
        $endpoint = $mode === 'week' ? 'week' : 'today';

        try {
            $response = $this->http()->get($this->baseUrl . '/' . $endpoint);

            if (!$response->successful()) {
                // Coba fallback ke mirror alternatif jika domain api berbeda
                $fallbackUrl = 'https://tweetfeed.live/api/v1/' . $endpoint;
                $response = $this->http()->get($fallbackUrl);
            }

            $response->throw();

            $rows = $response->json();

            if (!is_array($rows) || !array_is_list($rows)) {
                throw new RuntimeException('Format payload JSON dari TweetFeed tidak sesuai.');
            }

            $inserted = $this->storeThreatRows($rows);

            $state->update([
                'status' => 'online',
                'last_error' => null,
                'last_success_at' => now('Asia/Jakarta'),
                'records_last_sync' => $inserted,
            ]);

            return [
                'synced' => true,
                'mode' => $endpoint,
                'received' => count($rows),
                'inserted' => $inserted,
                'status' => 'online',
                'timestamp' => now('Asia/Jakarta')->toIso8601String(),
            ];

        } catch (Throwable $e) {
            $state->update([
                'status' => 'offline',
                'last_error' => mb_substr($e->getMessage(), 0, 5000),
            ]);

            throw $e;
        }
    }

    /**
     * Simpan baris data ke tabel threat_iocs dan iocs dengan normalisasi & defanging
     */
    public function storeThreatRows(array $rows): int
    {
        $records = [];
        $allowedTypes = ['url', 'domain', 'ip', 'sha256', 'md5'];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = strtolower(trim((string)($row['type'] ?? '')));
            $value = trim((string)($row['value'] ?? ''));
            $user = trim((string)($row['user'] ?? ''));
            $date = $row['date'] ?? null;

            if (!in_array($type, $allowedTypes, true) || $value === '' || !$date) {
                continue;
            }

            try {
                $sourceDate = Carbon::parse($date, 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            } catch (Throwable) {
                $sourceDate = now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }

            $tags = array_values(
                array_filter(
                    (array)($row['tags'] ?? []),
                    fn($tag) => is_string($tag) && trim($tag) !== ''
                )
            );

            $severity = $this->determineSeverity($tags);
            $category = isset($tags[0]) ? ltrim($tags[0], '#') : 'General';
            $category = mb_substr($category, 0, 100);

            $tweet = $row['tweet'] ?? null;
            if (!is_string($tweet) || !filter_var($tweet, FILTER_VALIDATE_URL)) {
                $tweet = null;
            }

            // Defang value
            $defangedValue = ThreatIntelligenceService::defang($type, $value);

            // Extract host domain
            $hostDomain = null;
            if ($type === 'url') {
                $parsed = parse_url($value);
                $hostDomain = $parsed['host'] ?? null;
                if ($hostDomain) {
                    $hostDomain = strtolower(preg_replace('/^www\./i', '', $hostDomain));
                }
            } elseif ($type === 'domain') {
                $hostDomain = strtolower(preg_replace('/^www\./i', '', $value));
            }

            // Unique dedupe hash
            $sourceKey = hash('sha256', $sourceDate . '|' . $user . '|' . $type . '|' . $value);

            $records[] = [
                'source_key' => $sourceKey,
                'type' => $type,
                'value' => $value,
                'defanged_value' => $defangedValue,
                'host_domain' => $hostDomain,
                'source_user' => $user ?: null,
                'source_tweet' => $tweet,
                'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                'category' => $category,
                'severity' => $severity,
                'status' => 'active',
                'source_date' => $sourceDate,
                'source' => 'tweetfeed',
                'created_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'updated_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ];
        }

        $inserted = 0;
        foreach (array_chunk($records, 250) as $chunk) {
            $inserted += DB::table('threat_iocs')->insertOrIgnore($chunk);
        }

        return $inserted;
    }

    /**
     * Klasifikasi tingkat keparahan berdasarkan tag
     */
    private function determineSeverity(array $tags): string
    {
        $normalized = array_map(fn($t) => mb_strtolower(ltrim(trim($t), '#')), $tags);

        $critical = ['apt', 'c2', 'ransomware', 'lockbit', 'ransomhub', 'cobaltstrike'];
        $warning = ['malware', 'phishing', 'trojan', 'stealer', 'botnet', 'rat', 'mirai'];

        foreach ($normalized as $t) {
            if (in_array($t, $critical, true)) {
                return 'critical';
            }
        }

        foreach ($normalized as $t) {
            if (in_array($t, $warning, true)) {
                return 'warning';
            }
        }

        return 'info';
    }

    public function state(): ApiSyncState
    {
        return ApiSyncState::firstOrCreate(
            ['id' => 1],
            [
                'endpoint' => $this->baseUrl,
                'status' => 'online',
                'last_success_at' => now('Asia/Jakarta'),
            ]
        );
    }
}
