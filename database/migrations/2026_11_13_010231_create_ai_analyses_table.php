<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->char('content_hash', 64)->unique();
            $table->string('group_code', 50)->nullable()->index();
            $table->enum('automation_level', ['high', 'medium', 'low'])->nullable();
            $table->text('reason')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('status', 20)->default('pending')->index();   // pending|processing|success|failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->string('model', 100)->nullable();
            $table->string('prompt_version', 20)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
