<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'selected_model',
        'input_features',
        'final_prediction',
        'confidence',
        // Kolom lama (nullable, diisi null untuk record baru dengan single-model)
        'knn_prediction', 'svm_prediction', 'dt_prediction',
        'knn_confidence', 'svm_confidence', 'dt_confidence',
    ];

    protected $casts = [
        'input_features'   => 'array',
        'final_prediction' => 'integer',
        'knn_prediction'   => 'integer',
        'svm_prediction'   => 'integer',
        'dt_prediction'    => 'integer',
        'confidence'       => 'float',
        'knn_confidence'   => 'float',
        'svm_confidence'   => 'float',
        'dt_confidence'    => 'float',
    ];

    public static array $modelLabels = [
        'knn'     => 'KNN',
        'knn_hpo' => 'KNN + HPO',
        'svm'     => 'SVM',
        'svm_hpo' => 'SVM + HPO',
        'dt'      => 'Decision Tree',
        'dt_hpo'  => 'Decision Tree + HPO',
    ];

    public static function riskLabel(int $value): string
    {
        return match ($value) {
            0       => 'Rendah',
            1       => 'Sedang',
            2       => 'Tinggi',
            default => 'Tidak diketahui',
        };
    }

    public function getModelLabelAttribute(): string
    {
        return self::$modelLabels[$this->selected_model] ?? strtoupper($this->selected_model ?? '-');
    }

    public function getFinalLabelAttribute(): string
    {
        return self::riskLabel($this->final_prediction);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
