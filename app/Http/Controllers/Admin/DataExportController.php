<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * 24 kolom fitur + label. Urutan baku sesuai dataset & predict pipeline.
     */
    private const HEADER = [
        'age', 'gender', 'marital_status', 'education_level', 'employment_status',
        'sleep_hours', 'physical_activity_hours_per_week', 'screen_time_hours_per_day',
        'social_support_score', 'work_stress_level', 'academic_pressure_level',
        'job_satisfaction_score', 'financial_stress_level', 'working_hours_per_week',
        'anxiety_score', 'depression_score', 'stress_level', 'mood_swings_frequency',
        'concentration_difficulty_level', 'panic_attack_history',
        'family_history_mental_illness', 'previous_mental_health_diagnosis',
        'therapy_history', 'substance_use', 'mental_health_risk',
    ];

    public function export(): StreamedResponse
    {
        $base = storage_path('models/data/mental_health_risk_dataset.csv');
        $user = storage_path('models/data/user_contributions.csv');
        $filename = 'total_records_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($base, $user): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADER);

            $this->streamCsv($out, $base);
            $this->streamCsv($out, $user);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Stream baris data dari sebuah CSV, memetakan tiap baris ke urutan HEADER
     * berdasarkan nama kolom di baris header sumber. Header sumber dilewati.
     */
    private function streamCsv($out, string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        $sourceHeader = fgetcsv($handle);
        if ($sourceHeader === false) {
            fclose($handle);
            return;
        }
        $sourceHeader = array_map('trim', $sourceHeader);

        while (($row = fgetcsv($handle)) !== false) {
            $assoc = @array_combine($sourceHeader, $row);
            if ($assoc === false) {
                continue;
            }

            $mapped = [];
            foreach (self::HEADER as $col) {
                $mapped[] = $assoc[$col] ?? '';
            }

            fputcsv($out, $mapped);
        }

        fclose($handle);
    }
}
