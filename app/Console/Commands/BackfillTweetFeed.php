<?php

namespace App\Console\Commands;

use App\Services\TweetFeedService;
use Illuminate\Console\Command;
use Throwable;

class BackfillTweetFeed extends Command
{
    protected $signature = 'tweetfeed:backfill';

    protected $description =
        'Replay the last 7 days of TweetFeed IOC data';

    public function handle(
        TweetFeedService $service
    ): int {

        $this->info('Starting weekly IOC backfill...');

        try {

            $result = $service->backfillWeek();

            $this->newLine();

            $this->info('Backfill completed!');

            $this->table(
                ['Information', 'Result'],
                [
                    ['Received IOC', $result['received']],
                    ['New IOC Saved', $result['inserted']],
                    [
                        'Not Modified',
                        $result['not_modified'] ? 'Yes' : 'No'
                    ],
                ]
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            report($e);

            $this->error(
                'Backfill failed: ' . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
