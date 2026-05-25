<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('selected_model', 20)->nullable()->after('user_id');
            $table->float('confidence', 8, 4)->nullable()->after('final_prediction');

            // Jadikan nullable untuk kompatibilitas dengan model seleksi tunggal
            $table->unsignedTinyInteger('knn_prediction')->nullable()->change();
            $table->unsignedTinyInteger('svm_prediction')->nullable()->change();
            $table->unsignedTinyInteger('dt_prediction')->nullable()->change();
            $table->float('knn_confidence', 8, 4)->nullable()->change();
            $table->float('svm_confidence', 8, 4)->nullable()->change();
            $table->float('dt_confidence', 8, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['selected_model', 'confidence']);
        });
    }
};
