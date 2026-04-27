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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->json('input_features');                       // Semua 24 input dari form
            $table->unsignedTinyInteger('knn_prediction');        // 0, 1, atau 2
            $table->unsignedTinyInteger('svm_prediction');        // 0, 1, atau 2
            $table->unsignedTinyInteger('dt_prediction');         // 0, 1, atau 2
            $table->float('knn_confidence', 8, 4);                // 0.0000 - 1.0000
            $table->float('svm_confidence', 8, 4);
            $table->float('dt_confidence', 8, 4);
            $table->unsignedTinyInteger('final_prediction');      // Hasil majority voting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
