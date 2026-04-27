<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelMetric
 *
 * Menyimpan metrik evaluasi tiap algoritma ML.
 *
 * @property int    $id
 * @property string $algorithm
 * @property float  $accuracy
 * @property float  $precision
 * @property float  $recall
 * @property float  $f1_score
 */
class ModelMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'algorithm',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
    ];

    protected $casts = [
        'accuracy'  => 'float',
        'precision' => 'float',
        'recall'    => 'float',
        'f1_score'  => 'float',
    ];
}
