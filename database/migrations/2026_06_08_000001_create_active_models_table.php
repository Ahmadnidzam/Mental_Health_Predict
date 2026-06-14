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
        Schema::create('active_models', function (Blueprint $table) {
            $table->id();
            $table->string('algorithm')->unique();          // knn / knn_hpo / svm / svm_hpo / dt / dt_hpo
            $table->foreignId('model_version_id')->constrained('model_versions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_models');
    }
};
