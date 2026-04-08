<?php

use App\Models\Platform;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\QAReply\App\Models\QaReply;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qa_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('module');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('qa_reply_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(QaReply::class, 'qa_reply_id')->constrained()->cascadeOnDelete();
            $table->text('key');
            $table->enum('type', ['text', 'template']);
            $table->foreignIdFor(Template::class, 'template_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            // if connection is mysql, add fulltext index
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['key']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_reply_items');
        Schema::dropIfExists('qa_replies');
    }
};
