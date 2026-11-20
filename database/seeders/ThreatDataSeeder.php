<?php

namespace Database\Seeders;

use App\Models\ApiSyncState;
use App\Models\ThreatIoc;
use App\Models\Watchlist;
use App\Services\ThreatIntelligenceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThreatDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Migrasikan data yang sudah ada di tabel iocs ke threat_iocs (jika ada)
        if (Schema::hasTable('iocs')) {
            $existingRows = DB::table('iocs')->get();

            $records = [];
            foreach ($existingRows as $row) {
                $type = $row->type;
                $value = $row->value;

                $defanged = ThreatIntelligenceService::defang($type, $value);

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

                $tags = [];
                if (!empty($row->tags)) {
                    $decoded = is_string($row->tags) ? json_decode($row->tags, true) : $row->tags;
                    if (is_array($decoded)) {
                        $tags = $decoded;
                    }
                }

                $category = 'General';
                if (!empty($row->category)) {
                    $category = $row->category;
                } elseif (!empty($tags[0])) {
                    $category = ltrim($tags[0], '#');
                }

                $severity = 'info';
                if (!empty($row->severity)) {
                    $severity = $row->severity;
                } else {
                    $tagsLower = array_map(fn($t) => strtolower(ltrim(trim($t), '#')), $tags);
                    if (array_intersect($tagsLower, ['apt', 'c2', 'ransomware', 'lockbit'])) {
                        $severity = 'critical';
                    } elseif (array_intersect($tagsLower, ['malware', 'phishing', 'trojan', 'stealer', 'botnet'])) {
                        $severity = 'warning';
                    }
                }

                $date = $row->reported_at ?? ($row->source_date ?? $row->created_at);
                $sourceKey = $row->dedupe_hash ?? ($row->source_key ?? hash('sha256', $date . '|' . ($row->reporter ?? ($row->source_user ?? '')) . '|' . $type . '|' . $value));

                $records[] = [
                    'source_key' => $sourceKey,
                    'type' => $type,
                    'value' => $value,
                    'defanged_value' => $defanged,
                    'host_domain' => $hostDomain,
                    'source_user' => $row->reporter ?? ($row->source_user ?? null),
                    'source_tweet' => $row->tweet_url ?? ($row->source_tweet ?? null),
                    'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                    'category' => mb_substr($category, 0, 100),
                    'severity' => $severity,
                    'status' => 'active',
                    'source_date' => $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : now('Asia/Jakarta'),
                    'source' => $row->source ?? 'tweetfeed',
                    'created_at' => now('Asia/Jakarta'),
                    'updated_at' => now('Asia/Jakarta'),
                ];
            }

            foreach (array_chunk($records, 250) as $chunk) {
                DB::table('threat_iocs')->insertOrIgnore($chunk);
            }
        }

        // 2. Inisialisasi entri Watchlist & Whitelist
        if (Watchlist::count() === 0) {
            $whitelists = [
                [
                    'type' => 'domain',
                    'value' => 'google.com',
                    'defanged_value' => 'google[.]com',
                    'host_domain' => 'google.com',
                    'list_type' => 'whitelist',
                    'category' => 'Official Search & Services',
                    'severity' => 'trusted',
                    'notes' => 'Domain resmi Google Search & Ekosistem Workspace.',
                    'created_by' => 'Lead SecOps',
                ],
                [
                    'type' => 'domain',
                    'value' => 'github.com',
                    'defanged_value' => 'github[.]com',
                    'host_domain' => 'github.com',
                    'list_type' => 'whitelist',
                    'category' => 'Code Repository',
                    'severity' => 'trusted',
                    'notes' => 'Platform kolaborasi source code resmi GitHub.',
                    'created_by' => 'Lead SecOps',
                ],
                [
                    'type' => 'ip',
                    'value' => '1.1.1.1',
                    'defanged_value' => '1[.]1[.]1[.]1',
                    'host_domain' => null,
                    'list_type' => 'whitelist',
                    'category' => 'Public DNS',
                    'severity' => 'trusted',
                    'notes' => 'Cloudflare Public Secure DNS Resolver.',
                    'created_by' => 'Lead SecOps',
                ],
                [
                    'type' => 'domain',
                    'value' => 'internal-suspicious-portal.xyz',
                    'defanged_value' => 'internal-suspicious-portal[.]xyz',
                    'host_domain' => 'internal-suspicious-portal.xyz',
                    'list_type' => 'watchlist',
                    'category' => 'Phishing Imitation',
                    'severity' => 'critical',
                    'notes' => 'Domain mencurigakan yang mencoba meniru portal internal kampus/kantor.',
                    'created_by' => 'SOC Analyst Alpha',
                ],
                [
                    'type' => 'ip',
                    'value' => '194.26.29.112',
                    'defanged_value' => '194[.]26[.]29[.]112',
                    'host_domain' => null,
                    'list_type' => 'watchlist',
                    'category' => 'Brute Force Host',
                    'severity' => 'warning',
                    'notes' => 'IP terdeteksi melakukan scanning SSH dan HTTP terus-menerus.',
                    'created_by' => 'SOC Analyst Beta',
                ]
            ];

            foreach ($whitelists as $item) {
                Watchlist::create($item);
            }
        }

        // 3. Pastikan record api_sync_states siap
        ApiSyncState::firstOrCreate(
            ['id' => 1],
            [
                'endpoint' => 'https://api.tweetfeed.live/v1',
                'status' => 'online',
                'last_success_at' => now('Asia/Jakarta'),
                'records_last_sync' => ThreatIoc::count(),
            ]
        );
    }
}
