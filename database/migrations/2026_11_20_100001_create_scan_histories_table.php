<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scan_histories')) {
            return;
        }

        Schema::create('scan_histories', function (Blueprint $table) {
            $table->id();
            $table->text('query');
            $table->string('normalized_query', 500)->index();
            $table->string('query_type', 50)->index();
            $table->enum('verdict', ['malicious', 'clean', 'watchlist', 'whitelist'])->default('clean')->index();
            $table->unsignedBigInteger('matched_ioc_id')->nullable()->index();
            $table->text('matched_ioc_value')->nullable();
            $table->string('matched_type', 50)->nullable();
            $table->json('matched_tags')->nullable();
            $table->unsignedInteger('risk_score')->default(0);
            $table->text('risk_explanation')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_histories');
    }
};
