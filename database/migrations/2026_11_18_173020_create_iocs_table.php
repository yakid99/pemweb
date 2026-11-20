
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iocs')) {
            return;
        }

        Schema::create('iocs', function (Blueprint $table) {

            $table->id();

            // Identitas unik untuk mencegah duplikasi IOC
            $table->char('source_key', 64)->unique();

            // Jenis IOC
            $table->enum('type', [
                'url',
                'domain',
                'ip',
                'sha256',
                'md5'
            ])->index();

            // Nilai indikator ancaman
            $table->text('value');

            // Informasi sumber
            $table->string('source_user', 150)->nullable();
            $table->text('source_tweet')->nullable();

            // Tag ancaman
            $table->json('tags')->nullable();

            // Kategori ancaman
            $table->string('category', 100)->nullable();

            // Tingkat keparahan
            $table->enum('severity', [
                'info',
                'warning',
                'critical'
            ])->default('info')->index();

            // Status IOC
            $table->enum('status', [
                'active',
                'inactive',
                'false_positive'
            ])->default('active')->index();

            // Waktu IOC dilaporkan
            $table->dateTime('source_date')->nullable()->index();

            // Asal data
            $table->enum('source', [
                'tweetfeed',
                'manual'
            ])->default('tweetfeed');

            $table->timestamps();

            // Index untuk kebutuhan pencarian
            $table->index(['type', 'status']);
            $table->index(['source_date', 'severity']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iocs');
    }
};
