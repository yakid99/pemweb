<?php

namespace App\Console\Commands;

use App\Services\TweetFeedSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncTweetFeed extends Command
{
    protected $signature = 'tweetfeed:sync {--mode=today : Sinkronisasi today atau week}';

    protected $description = 'Sinkronisasi data intelijen ancaman siber dari TweetFeed API ke database lokal';

    public function handle(TweetFeedSyncService $service): int
    {
        $mode = $this->option('mode') ?: 'today';
        $this->info("Memulai sinkronisasi TweetFeed API (Mode: {$mode})...");

        try {
            $result = $service->sync($mode);

            $this->newLine();
            $this->info('Sinkronisasi selesai dengan sukses!');

            $this->table(
                ['Parameter', 'Hasil'],
                [
                    ['Mode', $result['mode']],
                    ['Status API', $result['status']],
                    ['Total Diterima', $result['received']],
                    ['IOC Baru Disimpan', $result['inserted']],
                    ['Waktu Eksekusi', $result['timestamp']],
                ]
            );

            return self::SUCCESS;

        } catch (Throwable $e) {
            report($e);

            $this->error('Sinkronisasi gagal: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
