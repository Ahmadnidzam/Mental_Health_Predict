<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelVersion extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'status',
        'label',
        'metrics',
        'dataset_size',
        'user_rows_used',
        'artifact_path',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'metrics'     => 'array',
        'approved_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Highest accuracy across all algorithms in the metrics array.
     */
    public function bestAccuracy(): ?float
    {
        $metrics = $this->metrics;
        if (! is_array($metrics) || empty($metrics)) {
            return null;
        }

        $best = null;
        foreach ($metrics as $algo) {
            if (is_array($algo) && isset($algo['accuracy'])) {
                $accuracy = (float) $algo['accuracy'];
                if ($best === null || $accuracy > $best) {
                    $best = $accuracy;
                }
            }
        }

        return $best;
    }

    /**
     * Accuracy for a single algorithm key (e.g. "KNN", "SVM+HPO").
     */
    public function accuracyFor(string $algo): ?float
    {
        $metrics = $this->metrics;
        if (! is_array($metrics) || ! isset($metrics[$algo]['accuracy'])) {
            return null;
        }

        return (float) $metrics[$algo]['accuracy'];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
