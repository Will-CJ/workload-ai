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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_code', 20)->unique();   // ACT-0001 dari Excel
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();

            // Kolom dari dataset
            $table->string('activity_name');
            $table->text('description');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->unsignedInteger('volume');
            $table->unsignedInteger('duration_minutes');

            // Dihitung oleh program (deterministik)
            $table->unsignedBigInteger('annual_minutes');
            $table->decimal('fte', 8, 4);
            $table->string('normalized_key')->index();
            $table->char('content_hash', 64)->index();   // -> ai_analyses.content_hash
            $table->foreignId('activity_group_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('estimated_fte_saving', 8, 4)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
