<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$threatService = app(App\Services\ThreatIntelligenceService::class);

echo "=== TEST 1: WHITELIST SCAN (google.com) ===\n";
$res1 = $threatService->scan('https://google.com/search?q=threat');
echo "Verdict: " . $res1['verdict'] . "\n";
echo "Risk: " . $res1['risk_score'] . "\n";
echo "Explanation: " . $res1['risk_explanation'] . "\n\n";

echo "=== TEST 2: CHILD URL MATCHING RECORDED DOMAIN (boxfromjapan-jp.com) ===\n";
$res2 = $threatService->scan('https://boxfromjapan-jp.com/account/login?id=999');
echo "Verdict: " . $res2['verdict'] . "\n";
echo "Match Type: " . $res2['match_type'] . "\n";
echo "Defanged Input: " . $res2['defanged_query'] . "\n";
echo "Matched IOC Value: " . ($res2['matched_ioc']?->defanged_value ?? '-') . "\n";
echo "Risk Score: " . $res2['risk_score'] . "\n";
echo "Explanation: " . $res2['risk_explanation'] . "\n\n";

echo "=== TEST 3: CLEAN / UNKNOWN SCAN ===\n";
$res3 = $threatService->scan('https://contoh-aplikasi-aman-12345.edu');
echo "Verdict: " . $res3['verdict'] . "\n";
echo "Explanation: " . $res3['risk_explanation'] . "\n\n";

echo "=== TEST 4: WATCHLIST SCAN ===\n";
$res4 = $threatService->scan('internal-suspicious-portal.xyz');
echo "Verdict: " . $res4['verdict'] . "\n";
echo "Explanation: " . $res4['risk_explanation'] . "\n";
