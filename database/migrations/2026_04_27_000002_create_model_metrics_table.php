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
        Schema::create('model_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('algorithm');                  // 'KNN', 'SVM', 'Decision Tree'
            $table->float('accuracy', 8, 4);              // 0.0000 - 1.0000
            $table->float('precision', 8, 4);
            $table->float('recall', 8, 4);
            $table->float('f1_score', 8, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_metrics');
    }
};
