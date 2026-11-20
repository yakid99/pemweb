
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_sync_states')) {
            return;
        }

        Schema::create('api_sync_states', function (Blueprint $table) {

            $table->id();

            $table->string('endpoint')
                ->default('https://api.tweetfeed.live/v1');

            $table->enum('status', [
                'online',
                'offline'
            ])->default('offline');

            $table->boolean('pipeline_stale')
                ->default(false);

            $table->string('etag_today')->nullable();
            $table->string('etag_week')->nullable();

            $table->dateTime('last_window_end')->nullable();

            $table->dateTime('last_success_at')->nullable();

            $table->unsignedInteger('records_last_sync')
                ->default(0);

            $table->text('last_error')->nullable();

            $table->json('status_payload')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_states');
    }
};
