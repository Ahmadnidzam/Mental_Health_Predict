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
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->index();              // pending / active / archived / rejected
            $table->string('label')->nullable();            // e.g. "Base", "Retrain #3"
            $table->json('metrics')->nullable();            // C3 shape
            $table->unsignedInteger('dataset_size')->default(0);
            $table->unsignedInteger('user_rows_used')->default(0);
            $table->string('artifact_path')->nullable();    // relative e.g. versions/1
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
