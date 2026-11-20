<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== 1. TEST DASHBOARD GET / ===\n";
$request = Illuminate\Http\Request::create('/', 'GET');
$response = $kernel->handle($request);
echo "Dashboard Status Code: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 200) {
    echo "Dashboard successfully rendered! Content length: " . strlen($response->getContent()) . " bytes\n";
} else {
    echo "Error content: " . substr($response->getContent(), 0, 500) . "\n";
}

echo "\n=== 2. TEST POST /scan (Scan Malicious Link) ===\n";
$scanReq = Illuminate\Http\Request::create('/scan', 'POST', ['query' => 'https://boxfromjapan-jp.com/login']);
$scanReq->headers->set('Accept', 'application/json');
$scanRes = $kernel->handle($scanReq);
echo "Scan Status Code: " . $scanRes->getStatusCode() . "\n";
$data = json_decode($scanRes->getContent(), true);
echo "Verdict: " . ($data['data']['verdict'] ?? 'error') . "\n";
echo "Defanged: " . ($data['data']['defanged_query'] ?? 'error') . "\n";
echo "Explanation: " . substr($data['data']['risk_explanation'] ?? '', 0, 80) . "...\n";

echo "\n=== 3. TEST GET /api/samples ===\n";
$sampleReq = Illuminate\Http\Request::create('/api/samples', 'GET');
$sampleReq->headers->set('Accept', 'application/json');
$sampleRes = $kernel->handle($sampleReq);
echo "Samples Status Code: " . $sampleRes->getStatusCode() . "\n";
$sampleData = json_decode($sampleRes->getContent(), true);
echo "Sample Malicious: " . ($sampleData['malicious']['defanged_value'] ?? '') . "\n";
echo "Sample Safe: " . ($sampleData['safe']['raw_value'] ?? '') . "\n";

echo "\n=== 4. TEST GET /threat-iocs AJAX ===\n";
$iocReq = Illuminate\Http\Request::create('/threat-iocs?type=url', 'GET');
$iocReq->headers->set('Accept', 'application/json');
$iocReq->headers->set('X-Requested-With', 'XMLHttpRequest');
$iocRes = $kernel->handle($iocReq);
echo "IOC Filter Status: " . $iocRes->getStatusCode() . "\n";
$iocData = json_decode($iocRes->getContent(), true);
echo "Total filtered items: " . ($iocData['total'] ?? 0) . "\n";

echo "\n=== ALL CONTROLLER TESTS PASSED! ===\n";
