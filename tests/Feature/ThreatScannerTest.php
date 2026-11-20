<?php

namespace Tests\Feature;

use App\Models\ThreatIoc;
use App\Models\Watchlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreatScannerTest extends TestCase
{
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ThreatMetrics');
        $response->assertSee('Cyber Threat Intelligence & Link Scanner');
    }

    public function test_scanner_detects_malicious_child_url_and_defangs(): void
    {
        // Pastikan ada sampel domain jahat
        ThreatIoc::firstOrCreate(
            ['value' => 'phish-target-bank.com'],
            [
                'source_key' => hash('sha256', 'phish-target-bank.com'),
                'type' => 'domain',
                'defanged_value' => 'phish-target-bank[.]com',
                'host_domain' => 'phish-target-bank.com',
                'tags' => ['#phishing', '#malware'],
                'severity' => 'critical',
                'status' => 'active',
                'source_date' => now(),
            ]
        );

        // Scan dengan child URL lengkap
        $response = $this->postJson('/scan', [
            'query' => 'https://phish-target-bank.com/secure/login?id=12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'verdict' => 'malicious',
                'is_malicious' => true,
                'defanged_query' => 'hxxps://phish-target-bank[.]com/secure/login?id=12345',
            ]
        ]);
    }

    public function test_scanner_detects_clean_url(): void
    {
        $response = $this->postJson('/scan', [
            'query' => 'https://non-existent-clean-domain-xyz-1234.org/page',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'verdict' => 'clean',
                'is_clean' => true,
            ]
        ]);
    }

    public function test_scanner_detects_whitelist(): void
    {
        Watchlist::firstOrCreate(
            ['value' => 'trusted-cloud.internal'],
            [
                'type' => 'domain',
                'defanged_value' => 'trusted-cloud[.]internal',
                'host_domain' => 'trusted-cloud.internal',
                'list_type' => 'whitelist',
                'severity' => 'trusted',
                'is_active' => true,
            ]
        );

        $response = $this->postJson('/scan', [
            'query' => 'https://trusted-cloud.internal/app',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'verdict' => 'whitelist',
                'is_whitelist' => true,
            ]
        ]);
    }

    public function test_api_samples_returns_data(): void
    {
        $response = $this->getJson('/api/samples');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'malicious' => ['raw_value', 'defanged_value', 'type'],
            'safe' => ['raw_value', 'defanged_value', 'type'],
        ]);
    }

    public function test_ioc_table_can_be_filtered(): void
    {
        $response = $this->getJson('/threat-iocs?type=url');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'pagination',
            'total',
        ]);
    }
}
