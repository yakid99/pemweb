<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('threat_iocs')) {
            return;
        }

        Schema::create('threat_iocs', function (Blueprint $table) {
            $table->id();
            $table->char('source_key', 64)->unique();
            $table->enum('type', ['url', 'domain', 'ip', 'sha256', 'md5'])->index();
            $table->text('value');
            $table->text('defanged_value');
            $table->string('host_domain', 255)->nullable()->index();
            $table->string('source_user', 150)->nullable()->index();
            $table->text('source_tweet')->nullable();
            $table->json('tags')->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info')->index();
            $table->enum('status', ['active', 'inactive', 'false_positive'])->default('active')->index();
            $table->dateTime('source_date')->nullable()->index();
            $table->enum('source', ['tweetfeed', 'manual'])->default('tweetfeed');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['source_date', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threat_iocs');
    }
};
