<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Anti-ban: delay per nomor (detik)
            $table->unsignedTinyInteger('delay_min')->default(8)->after('delay_between');
            $table->unsignedTinyInteger('delay_max')->default(15)->after('delay_min');

            // Anti-ban: batch settings
            $table->unsignedTinyInteger('batch_size_min')->default(20)->after('delay_max');
            $table->unsignedTinyInteger('batch_size_max')->default(30)->after('batch_size_min');
            $table->unsignedSmallInteger('batch_pause_min')->default(5)->after('batch_size_max');  // menit
            $table->unsignedSmallInteger('batch_pause_max')->default(10)->after('batch_pause_min'); // menit

            // Anti-ban: limit harian
            $table->unsignedSmallInteger('daily_limit')->default(150)->after('batch_pause_max');

            // Anti-ban: filter spam
            $table->boolean('spam_filter')->default(true)->after('daily_limit');

            // Resume: simpan index customer terakhir yang berhasil dikirim
            $table->unsignedInteger('sending_progress')->default(0)->after('spam_filter');
        });

        // Ubah enum status campaigns — tambah 'paused'
        // MySQL tidak bisa alter enum langsung, gunakan DB::statement
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','pending','scheduled','send','failed','paused') DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'delay_min',
                'delay_max',
                'batch_size_min',
                'batch_size_max',
                'batch_pause_min',
                'batch_pause_max',
                'daily_limit',
                'spam_filter',
                'sending_progress',
            ]);
        });

        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','pending','scheduled','send','failed') DEFAULT 'draft'");
    }
};
