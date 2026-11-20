<?php

namespace App\Services;

use App\Models\ScanHistory;
use App\Models\ThreatIoc;
use App\Models\Watchlist;
use Illuminate\Support\Str;

class ThreatIntelligenceService
{
    /*
    |--------------------------------------------------------------------------
    | DEFANGING & REFANGING UTILITIES
    |--------------------------------------------------------------------------
    */

    /**
     * Mengubah URL, Domain, atau IP berbahaya menjadi format aman (defanged)
     * Contoh:
     * - http://evil.com/login -> hxxp://evil[.]com/login
     * - 192.168.1.1 -> 192[.]168[.]1[.]1
     */
    public static function defang(string $type, ?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $type = strtolower($type);

        if ($type === 'sha256' || $type === 'md5') {
            return $value;
        }

        // Defang protocol
        $defanged = preg_replace('/^http:\/\//i', 'hxxp://', $value);
        $defanged = preg_replace('/^https:\/\//i', 'hxxps://', $defanged);
        $defanged = preg_replace('/^ftp:\/\//i', 'fxp://', $defanged);

        // Defang domain & IP dots
        if ($type === 'ip') {
            $defanged = str_replace('.', '[.]', $defanged);
        } else {
            // Defang dot in host part
            $parts = parse_url($defanged);
            if (!empty($parts['host'])) {
                $defangedHost = str_replace('.', '[.]', $parts['host']);
                $defanged = str_replace($parts['host'], $defangedHost, $defanged);
            } else {
                $defanged = str_replace('.', '[.]', $defanged);
            }
        }

        return $defanged;
    }

    /**
     * Mengembalikan format defanged ke format standar untuk pengecekan database
     */
    public static function refang(string $value): string
    {
        $refanged = trim($value);
        $refanged = preg_replace('/^hxxps:\/\//i', 'https://', $refanged);
        $refanged = preg_replace('/^hxxp:\/\//i', 'http://', $refanged);
        $refanged = preg_replace('/^fxp:\/\//i', 'ftp://', $refanged);
        $refanged = str_replace(['[.]', '(.)', '{.}'], '.', $refanged);
        return $refanged;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALISASI INPUT & EKSTRAKSI HOST
    |--------------------------------------------------------------------------
    */

    /**
     * Normalisasi input link/indikator dari pengguna
     */
    public function normalizeInput(string $input): array
    {
        $refanged = self::refang($input);
        $refanged = trim($refanged);

        $type = $this->detectType($refanged);
        $host = null;
        $baseDomain = null;

        if ($type === 'url') {
            $parsed = parse_url($refanged);
            // Jika pengguna memasukkan url tanpa scheme (misal domain.com/login)
            if (empty($parsed['scheme']) && str_contains($refanged, '/')) {
                $parsed = parse_url('http://' . $refanged);
            }

            if (!empty($parsed['host'])) {
                $host = strtolower($parsed['host']);
                $host = preg_replace('/^www\./i', '', $host);
                $baseDomain = $this->extractBaseDomain($host);
            }
        } elseif ($type === 'domain') {
            $host = strtolower($refanged);
            $host = preg_replace('/^www\./i', '', $host);
            $baseDomain = $this->extractBaseDomain($host);
        }

        return [
            'raw' => $input,
            'clean' => $refanged,
            'type' => $type,
            'host' => $host,
            'base_domain' => $baseDomain,
        ];
    }

    /**
     * Deteksi tipe IOC: sha256, md5, ip, url, domain, atau unknown
     */
    public function detectType(string $value): string
    {
        // SHA-256 (64 hex characters)
        if (preg_match('/^[a-f0-9]{64}$/i', $value)) {
            return 'sha256';
        }

        // MD5 (32 hex characters)
        if (preg_match('/^[a-f0-9]{32}$/i', $value)) {
            return 'md5';
        }

        // IP Address (IPv4)
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'ip';
        }

        // IP Address (IPv6)
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'ip';
        }

        // URL (Memiliki skema http/https atau terdapat path '/')
        if (preg_match('/^https?:\/\//i', $value) || (str_contains($value, '/') && !str_contains($value, ' '))) {
            return 'url';
        }

        // Domain
        if (preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $value)) {
            return 'domain';
        }

        return 'unknown';
    }

    /**
     * Ekstrak domain utama (base domain) dari hostname/subdomain
     * Contoh: login.secure.evil-phish.com -> evil-phish.com
     */
    public function extractBaseDomain(string $host): string
    {
        $parts = explode('.', $host);
        $count = count($parts);

        if ($count <= 2) {
            return $host;
        }

        // Penanganan domain tingkat dua umum seperti .co.id, .com.br
        $secondLevelTlds = ['co.id', 'ac.id', 'go.id', 'or.id', 'sch.id', 'web.id', 'com.br', 'co.uk', 'gov.uk'];
        $lastTwo = $parts[$count - 2] . '.' . $parts[$count - 1];

        if (in_array(strtolower($lastTwo), $secondLevelTlds, true) && $count >= 3) {
            return $parts[$count - 3] . '.' . $lastTwo;
        }

        return $parts[$count - 2] . '.' . $parts[$count - 1];
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIKA PEMINDAIAN ANCAMAN (THREAT SCANNER)
    |--------------------------------------------------------------------------
    */

    /**
     * Memindai query link, domain, IP, atau hash terhadap database ThreatMetrics
     */
    public function scan(string $query, ?string $clientIp = null, ?string $userAgent = null): array
    {
        $normalized = $this->normalizeInput($query);
        $clean = $normalized['clean'];
        $type = $normalized['type'];
        $host = $normalized['host'];
        $baseDomain = $normalized['base_domain'];

        $verdict = 'clean';
        $matchedIoc = null;
        $matchedWatchlist = null;
        $matchType = null;
        $riskScore = 0;
        $riskExplanation = null;

        // 1. Cek Tabel Watchlist & Whitelist Manual
        $watchlistMatch = $this->checkWatchlists($clean, $type, $host, $baseDomain);

        if ($watchlistMatch) {
            $matchedWatchlist = $watchlistMatch;
            if ($watchlistMatch->list_type === 'whitelist') {
                $verdict = 'whitelist';
                $riskScore = 0;
                $riskExplanation = 'Indikator ini terdaftar dalam Whitelist Terpercaya internal organisasi. Akses diizinkan secara resmi.';
            } else {
                $verdict = 'watchlist';
                $riskScore = 80;
                $riskExplanation = 'Indikator ini terdaftar dalam Watchlist Pemantauan Internal. Tim keamanan sedang mengawasi aktivitas terkait: ' . ($watchlistMatch->notes ?: 'Tergolong mencurigakan.');
            }
        }

        // 2. Jika bukan whitelist, lakukan pencocokan pada tabel ancaman (threat_iocs)
        if ($verdict !== 'whitelist') {
            $threatMatch = $this->findThreatMatch($clean, $type, $host, $baseDomain);

            if ($threatMatch) {
                $matchedIoc = $threatMatch['ioc'];
                $matchType = $threatMatch['match_type'];
                $verdict = 'malicious';

                // Hitung skor risiko
                $riskScore = $this->calculateRiskScore($matchedIoc);

                // Dapatkan penjelasan risiko bahasa awam
                $tags = is_array($matchedIoc->tags) ? $matchedIoc->tags : [];
                $riskExplanation = $this->generateRiskExplanation($tags, $matchedIoc->type, $matchType);
            }
        }

        // 3. Jika tetap clean
        if ($verdict === 'clean') {
            $riskScore = 0;
            $riskExplanation = 'Indikator ini tidak ditemukan di dalam feed intelijen ancaman TweetFeed maupun database lokal ThreatMetrics. Tetap berhati-hati terhadap link baru yang belum terindeks (zero-day).';
        }

        // 4. Simpan riwayat pemindaian ke tabel scan_histories
        $scanRecord = ScanHistory::create([
            'query' => $query,
            'normalized_query' => $clean,
            'query_type' => $type,
            'verdict' => $verdict,
            'matched_ioc_id' => $matchedIoc?->id,
            'matched_ioc_value' => $matchedIoc ? self::defang($matchedIoc->type, $matchedIoc->value) : ($matchedWatchlist?->defanged_value),
            'matched_type' => $matchType ?: ($matchedWatchlist ? 'watchlist' : null),
            'matched_tags' => $matchedIoc?->tags,
            'risk_score' => $riskScore,
            'risk_explanation' => $riskExplanation,
            'client_ip' => $clientIp,
            'user_agent' => $userAgent ? Str::limit($userAgent, 255) : null,
        ]);

        return [
            'scan_id' => $scanRecord->id,
            'query' => $query,
            'normalized_query' => $clean,
            'defanged_query' => self::defang($type, $clean),
            'query_type' => $type,
            'host' => $host,
            'verdict' => $verdict,
            'is_malicious' => $verdict === 'malicious',
            'is_watchlist' => $verdict === 'watchlist',
            'is_whitelist' => $verdict === 'whitelist',
            'is_clean' => $verdict === 'clean',
            'risk_score' => $riskScore,
            'risk_explanation' => $riskExplanation,
            'match_type' => $matchType,
            'matched_ioc' => $matchedIoc,
            'matched_watchlist' => $matchedWatchlist,
            'scanned_at' => now('Asia/Jakarta'),
        ];
    }

    /**
     * Mencari pencocokan di tabel threat_iocs (Exact Match, Host Match, Domain Match)
     */
    private function findThreatMatch(string $clean, string $type, ?string $host, ?string $baseDomain): ?array
    {
        // A. Pencocokan Persis (Exact Match)
        $exact = ThreatIoc::where('value', $clean)->active()->first();
        if ($exact) {
            return ['ioc' => $exact, 'match_type' => 'exact_match'];
        }

        // B. Pencocokan berdasarkan URL / Host / Domain
        if ($host) {
            // Cek apakah host cocok dengan domain atau URL lain di database
            $hostMatch = ThreatIoc::where('host_domain', $host)
                ->orWhere('value', $host)
                ->orWhere('value', 'http://' . $host)
                ->orWhere('value', 'https://' . $host)
                ->active()
                ->first();

            if ($hostMatch) {
                return ['ioc' => $hostMatch, 'match_type' => 'host_match'];
            }

            // Cek apakah base domain cocok
            if ($baseDomain && $baseDomain !== $host) {
                $baseMatch = ThreatIoc::where('host_domain', $baseDomain)
                    ->orWhere('value', $baseDomain)
                    ->orWhere('value', 'http://' . $baseDomain)
                    ->orWhere('value', 'https://' . $baseDomain)
                    ->active()
                    ->first();

                if ($baseMatch) {
                    return ['ioc' => $baseMatch, 'match_type' => 'parent_domain_match'];
                }
            }
        }

        // C. Jika input adalah domain, cek apakah terdapat URL di DB yang berasal dari domain tersebut
        if ($type === 'domain') {
            $urlFromDomain = ThreatIoc::where('host_domain', $clean)
                ->active()
                ->first();

            if ($urlFromDomain) {
                return ['ioc' => $urlFromDomain, 'match_type' => 'domain_contains_malicious_urls'];
            }
        }

        return null;
    }

    /**
     * Memeriksa tabel watchlists untuk whitelist / watchlist kustom
     */
    private function checkWatchlists(string $clean, string $type, ?string $host, ?string $baseDomain): ?Watchlist
    {
        $exact = Watchlist::where('value', $clean)->active()->first();
        if ($exact) {
            return $exact;
        }

        if ($host) {
            $hostMatch = Watchlist::where('value', $host)
                ->orWhere('host_domain', $host)
                ->active()
                ->first();

            if ($hostMatch) {
                return $hostMatch;
            }

            if ($baseDomain && $baseDomain !== $host) {
                $baseMatch = Watchlist::where('value', $baseDomain)
                    ->orWhere('host_domain', $baseDomain)
                    ->active()
                    ->first();

                if ($baseMatch) {
                    return $baseMatch;
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | EDUKASI & PENJELASAN RISIKO (BAHASA AWAM)
    |--------------------------------------------------------------------------
    */

    /**
     * Menghasilkan penjelasan risiko ramah pengguna dalam Bahasa Indonesia
     */
    public function generateRiskExplanation(array $tags, string $type, ?string $matchType = null): string
    {
        $normalizedTags = array_map(fn($t) => strtolower(ltrim(trim($t), '#')), $tags);

        $explanations = [];

        if (in_array('phishing', $normalizedTags, true) || in_array('credential-harvesting', $normalizedTags, true)) {
            $explanations[] = 'Link ini terindikasi sebagai situs Phishing yang berpotensi mencuri kredensial login (username, kata sandi), data pribadi, atau informasi perbankan Anda melalui halaman palsu yang meniru layanan resmi.';
        }

        if (in_array('c2', $normalizedTags, true) || in_array('command-and-control', $normalizedTags, true) || in_array('cobaltstrike', $normalizedTags, true)) {
            $explanations[] = 'Alamat ini diidentifikasi sebagai server Command & Control (C2) yang digunakan oleh peretas untuk mengirim instruksi jarak jauh dan mengendalikan perangkat yang terinfeksi malware.';
        }

        if (in_array('malware', $normalizedTags, true) || in_array('trojan', $normalizedTags, true) || in_array('worm', $normalizedTags, true)) {
            $explanations[] = 'Indikator ini dikaitkan dengan penyebaran perangkat lunak berbahaya (malware) yang dapat menginfeksi, merusak, atau mengambil alih kendali sistem tanpa izin pemilik.';
        }

        if (in_array('ransomware', $normalizedTags, true) || in_array('lockbit', $normalizedTags, true) || in_array('ransomhub', $normalizedTags, true)) {
            $explanations[] = 'Terkait erat dengan serangan Ransomware berbahaya yang dapat mengenkripsi seluruh file penting di perangkat Anda dan menuntut tebusan uang.';
        }

        if (in_array('stealer', $normalizedTags, true) || in_array('rat', $normalizedTags, true) || in_array('redline', $normalizedTags, true)) {
            $explanations[] = 'Terindikasi menyebarkan Infostealer / Remote Access Trojan (RAT) yang bertugas menyusup untuk menyedot kata sandi tersimpan, sesi browser, dan file rahasia.';
        }

        if (in_array('apt', $normalizedTags, true) || in_array('kimsuky', $normalizedTags, true) || in_array('lazarus', $normalizedTags, true)) {
            $explanations[] = 'Merupakan bagian dari kampanye Advanced Persistent Threat (APT) — operasi spionase siber canggih yang biasanya didanai oleh kelompok tingkat negara.';
        }

        if (in_array('botnet', $normalizedTags, true) || in_array('mirai', $normalizedTags, true)) {
            $explanations[] = 'Terlibat dalam aktivitas Botnet, di mana perangkat yang terinfeksi dipaksa bergabung dalam jaringan penyerang untuk melancarkan serangan siber massal (seperti DDoS).';
        }

        if (empty($explanations)) {
            $explanations[] = 'Indikator ini telah dilaporkan secara publik oleh analis keamanan siber global melalui TweetFeed karena terdeteksi dalam aktivitas ancaman digital yang mencurigakan.';
        }

        $base = implode(' ', $explanations);

        if ($matchType === 'parent_domain_match') {
            $base = '[Domain Induk Berbahaya] ' . $base . ' Meskipun URL spesifik yang Anda masukkan belum pernah dilaporkan secara persis, domain induknya telah tercatat sebagai sumber ancaman aktif.';
        } elseif ($matchType === 'host_match') {
            $base = '[Host Terindikasi] ' . $base . ' Host/Domain dari link ini cocok dengan entri ancaman berbahaya yang tercatat.';
        }

        return $base;
    }

    /**
     * Menghitung skor risiko (0 - 100)
     */
    public function calculateRiskScore(ThreatIoc $ioc): int
    {
        $score = match ($ioc->severity) {
            'critical' => 95,
            'warning' => 80,
            default => 60,
        };

        $tags = array_map(fn($t) => strtolower(ltrim(trim($t), '#')), is_array($ioc->tags) ? $ioc->tags : []);

        if (array_intersect($tags, ['apt', 'c2', 'ransomware', 'lockbit', 'kimsuky'])) {
            $score = max($score, 98);
        } elseif (array_intersect($tags, ['phishing', 'malware', 'stealer', 'trojan'])) {
            $score = max($score, 88);
        }

        return min(100, $score);
    }

    /*
    |--------------------------------------------------------------------------
    | SAMPEL CONTOH LINK (UNTUK HERO SECTION)
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil contoh nyata link berbahaya dari database lokal
     */
    public function getSampleMalicious(): array
    {
        $ioc = ThreatIoc::where('type', 'url')
            ->whereNotNull('tags')
            ->orderByDesc('id')
            ->first();

        if (!$ioc) {
            $ioc = ThreatIoc::where('type', 'domain')
                ->orderByDesc('id')
                ->first();
        }

        if ($ioc) {
            return [
                'raw_value' => $ioc->value,
                'defanged_value' => $ioc->defanged_value,
                'type' => $ioc->type,
                'category' => $ioc->category,
                'tags' => $ioc->tags,
            ];
        }

        return [
            'raw_value' => 'http://makes4.dynu.org/login.php',
            'defanged_value' => 'hxxp://makes4[.]dynu[.]org/login.php',
            'type' => 'url',
            'category' => 'C2 / Malware',
            'tags' => ['#malware', '#c2'],
        ];
    }

    /**
     * Contoh indikator yang aman (Clean)
     */
    public function getSampleSafe(): array
    {
        return [
            'raw_value' => 'https://www.google.com/search?q=cybersecurity',
            'defanged_value' => 'hxxps://www[.]google[.]com/search?q=cybersecurity',
            'type' => 'url',
            'category' => 'Trusted Search Engine',
            'tags' => ['#clean', '#verified'],
        ];
    }
}
