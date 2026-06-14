<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveModel extends Model
{
    /**
     * Assignment algoritma -> versi model yang sedang dipakai untuk inferensi.
     * Satu baris per algoritma (knn, knn_hpo, svm, svm_hpo, dt, dt_hpo).
     */
    protected $fillable = [
        'algorithm',
        'model_version_id',
    ];

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(ModelVersion::class);
    }
}
