<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Prediction
 *
 * Menyimpan setiap hasil prediksi pengguna.
 *
 * @property int    $id
 * @property array  $input_features
 * @property int    $knn_prediction
 * @property int    $svm_prediction
 * @property int    $dt_prediction
 * @property float  $knn_confidence
 * @property float  $svm_confidence
 * @property float  $dt_confidence
 * @property int    $final_prediction
 */
class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'input_features',
        'knn_prediction',
        'svm_prediction',
        'dt_prediction',
        'knn_confidence',
        'svm_confidence',
        'dt_confidence',
        'final_prediction',
    ];

    protected $casts = [
        'input_features'   => 'array',
        'knn_prediction'   => 'integer',
        'svm_prediction'   => 'integer',
        'dt_prediction'    => 'integer',
        'final_prediction' => 'integer',
        'knn_confidence'   => 'float',
        'svm_confidence'   => 'float',
        'dt_confidence'    => 'float',
    ];

    /**
     * Mapping integer risk -> label manusiawi.
     */
    public static function riskLabel(int $value): string
    {
        return match ($value) {
            0       => 'Rendah',
            1       => 'Sedang',
            2       => 'Tinggi',
            default => 'Tidak diketahui',
        };
    }

    public function getKnnLabelAttribute(): string
    {
        return self::riskLabel($this->knn_prediction);
    }

    public function getSvmLabelAttribute(): string
    {
        return self::riskLabel($this->svm_prediction);
    }

    public function getDtLabelAttribute(): string
    {
        return self::riskLabel($this->dt_prediction);
    }

    public function getFinalLabelAttribute(): string
    {
        return self::riskLabel($this->final_prediction);
    }
}
