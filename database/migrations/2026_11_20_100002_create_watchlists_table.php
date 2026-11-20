<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('watchlists')) {
            return;
        }

        Schema::create('watchlists', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['url', 'domain', 'ip', 'sha256', 'md5'])->index();
            $table->string('value', 500)->index();
            $table->string('defanged_value', 500);
            $table->string('host_domain', 255)->nullable()->index();
            $table->enum('list_type', ['watchlist', 'whitelist'])->default('watchlist')->index();
            $table->string('category', 100)->nullable();
            $table->enum('severity', ['info', 'warning', 'critical', 'trusted'])->default('warning');
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->default('SecOps Analyst');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};
