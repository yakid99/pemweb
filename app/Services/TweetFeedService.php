<?php

namespace App\Services;

use App\Models\ApiSyncState;
use App\Models\Ioc;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class TweetFeedService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.tweetfeed.base_url'),
            '/'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP CLIENT
    |--------------------------------------------------------------------------
    */

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent(
                'ThreatMetrics/1.0 (Laravel HTTP Client)'
            )
            ->connectTimeout(10)
            ->timeout(45)
            ->retry(3, 1000);
    }

    /*
    |--------------------------------------------------------------------------
    | API WINDOW REQUEST
    |--------------------------------------------------------------------------
    */

    private function getWindow(
        string $window,
        ?string $etag = null
    ): Response {

        $request = $this->http();

        if ($etag) {
            $request->withHeaders([
                'If-None-Match' => $etag
            ]);
        }

        return $request->get(
            $this->baseUrl . '/' . $window
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INITIAL AND INCREMENTAL SYNC
    |--------------------------------------------------------------------------
    */

    public function sync(): array
    {
        $state = $this->state();

        try {

            /*
             * INITIAL SYNC
             * Ambil data tujuh hari pertama.
             */

            if (!$state->last_window_end) {

                $response = $this->getWindow(
                    'week',
                    $state->etag_week
                );

                if ($response->status() === 304) {

                    $state->update([
                        'status' => 'online',
                        'last_success_at' => now('Asia/Jakarta'),
                        'last_error' => null,
                        'records_last_sync' => 0,
                    ]);

                    $this->refreshHealth();

                    return [
                        'mode' => 'initial_week',
                        'received' => 0,
                        'inserted' => 0,
                        'pages' => 1,
                    ];
                }

                $response->throw();

                $rows = $this->decodeRows($response);

                $inserted = $this->storeRows($rows);

                $windowEnd = $this->parseWindowEnd(
                    $response->header('X-Result-Window-End')
                );

                $state->update([
                    'status' => 'online',
                    'last_error' => null,
                    'last_success_at' => now('Asia/Jakarta'),
                    'records_last_sync' => $inserted,
                    'last_window_end' => $windowEnd,
                    'etag_week' => $response->header('ETag')
                        ?: $state->etag_week,
                ]);

                $this->refreshHealth();

                return [
                    'mode' => 'initial_week',
                    'received' => count($rows),
                    'inserted' => $inserted,
                    'pages' => 1,
                ];
            }

            /*
             * INCREMENTAL SYNC
             * Mengambil data sejak 48 jam sebelum
             * window terakhir yang berhasil diproses.
             */

            $cursor = $state->last_window_end
                ->copy()
                ->subHours(48);

            $received = 0;
            $inserted = 0;
            $pages = 0;

            $finalWindowEnd = $state->last_window_end;

            while (true) {

                $since = $cursor
                    ->copy()
                    ->utc()
                    ->format('Y-m-d\TH:i:s\Z');

                $url = $this->baseUrl
                    . '/since/'
                    . $since;

                $response = $this->http()->get($url);

                $response->throw();

                $rows = $this->decodeRows($response);

                $received += count($rows);

                $inserted += $this->storeRows($rows);

                $pages++;

                $windowEnd = $this->parseWindowEnd(
                    $response->header('X-Result-Window-End')
                );

                /*
                 * Jika hasil mencapai batas 10.000,
                 * lanjutkan dari window-end sebelumnya.
                 */

                $truncated = trim(
                    (string) $response->header(
                        'X-Result-Truncated'
                    )
                ) !== '';

                if ($truncated) {

                    if (!$windowEnd) {
                        throw new RuntimeException(
                            'Header X-Result-Window-End tidak ditemukan.'
                        );
                    }

                    if (!$windowEnd->greaterThan($cursor)) {
                        throw new RuntimeException(
                            'Pagination API tidak mengalami kemajuan.'
                        );
                    }

                    $cursor = $windowEnd;

                } else {

                    $finalWindowEnd = $windowEnd
                        ?? $finalWindowEnd;

                    break;
                }

                // Pengaman agar pagination tidak berulang tanpa batas.
                if ($pages >= 20) {
                    throw new RuntimeException(
                        'Maksimal pagination 20 halaman tercapai. '
                        . 'Sinkronisasi akan dilanjutkan pada jadwal berikutnya.'
                    );
                }
            }

            $state->update([
                'status' => 'online',
                'last_error' => null,
                'last_success_at' => now('Asia/Jakarta'),
                'records_last_sync' => $inserted,
                'last_window_end' => $finalWindowEnd,
            ]);

            $this->refreshHealth();

            return [
                'mode' => 'incremental',
                'received' => $received,
                'inserted' => $inserted,
                'pages' => $pages,
            ];

        } catch (Throwable $e) {

            $state->update([
                'status' => 'offline',
                'last_error' => mb_substr(
                    $e->getMessage(),
                    0,
                    60000
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WEEKLY BACKFILL
    |--------------------------------------------------------------------------
    */

    public function backfillWeek(): array
    {
        $state = $this->state();

        try {

            $response = $this->getWindow(
                'week',
                $state->etag_week
            );

            if ($response->status() === 304) {

                $state->update([
                    'status' => 'online',
                    'last_success_at' => now('Asia/Jakarta'),
                    'last_error' => null,
                    'records_last_sync' => 0,
                ]);

                $this->refreshHealth();

                return [
                    'received' => 0,
                    'inserted' => 0,
                    'not_modified' => true,
                ];
            }

            $response->throw();

            $rows = $this->decodeRows($response);

            $inserted = $this->storeRows($rows);

            $windowEnd = $this->parseWindowEnd(
                $response->header('X-Result-Window-End')
            );

            $latestWindowEnd = $state->last_window_end;

            if (
                $windowEnd &&
                (
                    !$latestWindowEnd ||
                    $windowEnd->greaterThan($latestWindowEnd)
                )
            ) {
                $latestWindowEnd = $windowEnd;
            }

            $state->update([
                'status' => 'online',
                'last_error' => null,
                'last_success_at' => now('Asia/Jakarta'),
                'records_last_sync' => $inserted,
                'last_window_end' => $latestWindowEnd,
                'etag_week' => $response->header('ETag')
                    ?: $state->etag_week,
            ]);

            $this->refreshHealth();

            return [
                'received' => count($rows),
                'inserted' => $inserted,
                'not_modified' => false,
            ];

        } catch (Throwable $e) {

            $state->update([
                'status' => 'offline',
                'last_error' => mb_substr(
                    $e->getMessage(),
                    0,
                    60000
                ),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DECODE API RESPONSE
    |--------------------------------------------------------------------------
    */

    private function decodeRows(Response $response): array
    {
        $rows = $response->json();

        if (!is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException(
                'Format JSON TweetFeed tidak sesuai.'
            );
        }

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE WINDOW END
    |--------------------------------------------------------------------------
    */

    private function parseWindowEnd(
        ?string $value
    ): ?Carbon {

        if (!$value) {
            return null;
        }

        return Carbon::parse($value, 'UTC')->setTimezone('Asia/Jakarta');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE IOC INTO MYSQL
    |--------------------------------------------------------------------------
    */

    private function storeRows(array $rows): int
    {
        $records = [];

        $allowedTypes = [
            'url',
            'domain',
            'ip',
            'sha256',
            'md5',
        ];

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            $type = strtolower(
                trim((string) ($row['type'] ?? ''))
            );

            $value = trim(
                (string) ($row['value'] ?? '')
            );

            $sourceUser = trim(
                (string) ($row['user'] ?? '')
            );

            $date = $row['date'] ?? null;

            // Validasi tipe IOC dan nilai indikator.
            if (
                !in_array($type, $allowedTypes, true) ||
                $value === '' ||
                !$date
            ) {
                continue;
            }

            // Normalisasi waktu menjadi UTC.
            try {

                $sourceDate = Carbon::parse(
                    $date,
                    'UTC'
                )->setTimezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s');
            } catch (Throwable) {

                continue;
            }

            // Mengambil tag.
            $tags = array_values(
                array_filter(
                    (array) ($row['tags'] ?? []),
                    fn ($tag) =>
                        is_string($tag) &&
                        trim($tag) !== ''
                )
            );

            // Menentukan severity dan kategori.
            $severity = $this->severityFor($tags);

            $category = isset($tags[0])
                ? ltrim($tags[0], '#')
                : 'Uncategorized';

            $category = mb_substr($category, 0, 100);

            // Memvalidasi link sumber.
            $tweet = $row['tweet'] ?? null;

            if (
                !is_string($tweet) ||
                !filter_var($tweet, FILTER_VALIDATE_URL)
            ) {
                $tweet = null;
            }

            /*
             * IDENTITAS UNIK IOC
             * Berdasarkan tuple:
             * date + user + type + value
             */

            $identity = json_encode([
                $sourceDate,
                $sourceUser,
                $type,
                $value,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $sourceKey = hash('sha256', $identity);

            $records[] = [
                'source_key' => $sourceKey,

                'type' => $type,

                'value' => $value,

                'source_user' => $sourceUser ?: null,

                'source_tweet' => $tweet,

                'tags' => json_encode(
                    $tags,
                    JSON_UNESCAPED_UNICODE
                ),

                'category' => $category,

                'severity' => $severity,

                'status' => 'active',

                'source_date' => $sourceDate,

                'source' => 'tweetfeed',

                'created_at' => now('Asia/Jakarta')
                    ->format('Y-m-d H:i:s'),

                'updated_at' => now('Asia/Jakarta')
                    ->format('Y-m-d H:i:s'),
            ];
        }

        /*
         * INSERT OR IGNORE:
         * IOC dengan source_key yang sudah ada
         * tidak akan dimasukkan kembali.
         */

        $inserted = 0;

        foreach (array_chunk($records, 500) as $chunk) {

            $inserted += DB::table('iocs')
                ->insertOrIgnore($chunk);
        }

        return $inserted;
    }

    /*
    |--------------------------------------------------------------------------
    | THREAT SEVERITY CLASSIFICATION
    |--------------------------------------------------------------------------
    */

    private function severityFor(array $tags): string
    {
        $normalizedTags = array_map(
            fn ($tag) => mb_strtolower(
                ltrim(trim($tag), '#')
            ),
            $tags
        );

        $criticalTags = [
            'apt',
            'c2',
            'ransomware',
            'lockbit',
            'ransomhub',
        ];

        $warningTags = [
            'malware',
            'phishing',
            'trojan',
            'stealer',
            'botnet',
            'rat',
            'cobaltstrike',
        ];

        foreach ($normalizedTags as $tag) {

            if (in_array($tag, $criticalTags, true)) {
                return 'critical';
            }
        }

        foreach ($normalizedTags as $tag) {

            if (in_array($tag, $warningTags, true)) {
                return 'warning';
            }
        }

        return 'info';
    }

    /*
    |--------------------------------------------------------------------------
    | API HEALTH STATUS
    |--------------------------------------------------------------------------
    */

    public function refreshHealth(): array
    {
        $state = $this->state();

        try {

            $response = $this->http()->get(
                $this->baseUrl . '/status'
            );

            $response->throw();

            $payload = $response->json();

            if (!is_array($payload)) {
                throw new RuntimeException(
                    'Format API status tidak valid.'
                );
            }

            $state->update([
                'status' => 'online',

                // Top-level stale menunjukkan kondisi pipeline.
                'pipeline_stale' => (bool) (
                    $payload['stale'] ?? false
                ),

                'status_payload' => $payload,

                'last_error' => null,
            ]);

            return $payload;

        } catch (Throwable $e) {

            $state->update([
                'status' => 'offline',
                'last_error' => mb_substr(
                    $e->getMessage(),
                    0,
                    60000
                ),
            ]);

            return [
                'stale' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATE
    |--------------------------------------------------------------------------
    */

    private function state(): ApiSyncState
    {
        return ApiSyncState::firstOrCreate(
            ['id' => 1],
            [
                'endpoint' => $this->baseUrl,
                'status' => 'offline',
            ]
        );
    }
}
