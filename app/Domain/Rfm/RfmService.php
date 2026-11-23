<?php

namespace App\Domain\Rfm;

use App\Models\Member;
use App\Models\Order;
use App\Models\RfmAnalysis;
use App\Models\RfmParameter;
use App\Models\RfmResult;
use App\Models\RfmSegment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfmService
{
    public const DEFAULT_CONFIG = [
        'period_days' => 90,
        'r_thresholds' => [5 => 7, 4 => 14, 3 => 30, 2 => 60, 1 => 999999],
        'f_thresholds' => [5 => 8, 4 => 5, 3 => 3, 2 => 2, 1 => 1],
        'm_thresholds' => [5 => 1000000, 4 => 600000, 3 => 300000, 2 => 100000, 1 => 0],
    ];

    public function getParameters(): array
    {
        $row = RfmParameter::query()->where('key', 'scoring_config')->first();

        return array_replace_recursive(self::DEFAULT_CONFIG, $row?->value ?? []);
    }

    public function updateParameters(array $config, ?int $updatedBy = null): RfmParameter
    {
        $periodDays = (int) ($config['period_days'] ?? 90);
        if ($periodDays < 7 || $periodDays > 730) {
            throw ValidationException::withMessages([
                'period_days' => 'Periode analisis RFM harus antara 7 sampai 730 hari.',
            ]);
        }

        // Validate monotonic thresholds (Section 6 Module I)
        $r = $config['r_thresholds'] ?? [];
        if (! isset($r[5], $r[4], $r[3], $r[2]) || (int) $r[5] <= 0 || ((int) $r[5] >= (int) $r[4]) || ((int) $r[4] >= (int) $r[3]) || ((int) $r[3] >= (int) $r[2])) {
            throw ValidationException::withMessages([
                'r_thresholds' => 'Ambang Recency harus berurutan naik (Skor 5 < Skor 4 < Skor 3 < Skor 2).',
            ]);
        }

        $f = $config['f_thresholds'] ?? [];
        if (! isset($f[5], $f[4], $f[3], $f[2]) || ((int) $f[5] <= (int) $f[4]) || ((int) $f[4] <= (int) $f[3]) || ((int) $f[3] <= (int) $f[2]) || ((int) $f[2] < 1)) {
            throw ValidationException::withMessages([
                'f_thresholds' => 'Ambang Frequency harus berurutan turun (Skor 5 > Skor 4 > Skor 3 > Skor 2 >= 1).',
            ]);
        }

        $m = $config['m_thresholds'] ?? [];
        if (! isset($m[5], $m[4], $m[3], $m[2]) || ((int) $m[5] <= (int) $m[4]) || ((int) $m[4] <= (int) $m[3]) || ((int) $m[3] <= (int) $m[2]) || ((int) $m[2] <= 0)) {
            throw ValidationException::withMessages([
                'm_thresholds' => 'Ambang Monetary harus berurutan turun (Skor 5 > Skor 4 > Skor 3 > Skor 2 > 0).',
            ]);
        }

        return RfmParameter::query()->updateOrCreate(
            ['key' => 'scoring_config'],
            [
                'value' => [
                    'period_days' => $periodDays,
                    'r_thresholds' => [
                        5 => (int) $r[5],
                        4 => (int) $r[4],
                        3 => (int) $r[3],
                        2 => (int) $r[2],
                        1 => 999999,
                    ],
                    'f_thresholds' => [
                        5 => (int) $f[5],
                        4 => (int) $f[4],
                        3 => (int) $f[3],
                        2 => (int) $f[2],
                        1 => 1,
                    ],
                    'm_thresholds' => [
                        5 => (int) $m[5],
                        4 => (int) $m[4],
                        3 => (int) $m[3],
                        2 => (int) $m[2],
                        1 => 0,
                    ],
                ],
                'updated_by' => $updatedBy,
            ]
        );
    }

    /**
     * Pure deterministic scorer for Recency (days since last successful order; lower is better).
     */
    public function scoreRecency(?int $days, array $rThresholds): int
    {
        if ($days === null) {
            return 1;
        }
        if ($days <= (int) ($rThresholds[5] ?? 7)) {
            return 5;
        }
        if ($days <= (int) ($rThresholds[4] ?? 14)) {
            return 4;
        }
        if ($days <= (int) ($rThresholds[3] ?? 30)) {
            return 3;
        }
        if ($days <= (int) ($rThresholds[2] ?? 60)) {
            return 2;
        }

        return 1;
    }

    /**
     * Pure deterministic scorer for Frequency (successful orders count; higher is better).
     */
    public function scoreFrequency(int $count, array $fThresholds): int
    {
        if ($count <= 0) {
            return 1;
        }
        if ($count >= (int) ($fThresholds[5] ?? 8)) {
            return 5;
        }
        if ($count >= (int) ($fThresholds[4] ?? 5)) {
            return 4;
        }
        if ($count >= (int) ($fThresholds[3] ?? 3)) {
            return 3;
        }
        if ($count >= (int) ($fThresholds[2] ?? 2)) {
            return 2;
        }

        return 1;
    }

    /**
     * Pure deterministic scorer for Monetary (sum of total_amount in Rupiah; higher is better).
     */
    public function scoreMonetary(int $monetary, array $mThresholds): int
    {
        if ($monetary >= (int) ($mThresholds[5] ?? 1000000)) {
            return 5;
        }
        if ($monetary >= (int) ($mThresholds[4] ?? 600000)) {
            return 4;
        }
        if ($monetary >= (int) ($mThresholds[3] ?? 300000)) {
            return 3;
        }
        if ($monetary >= (int) ($mThresholds[2] ?? 100000)) {
            return 2;
        }

        return 1;
    }

    /**
     * Evaluates ordered segment rules (first match wins).
     * Members with 0 successful orders in period receive "Belum Aktif".
     *
     * @param  Collection<int, RfmSegment>  $segments
     * @return array{segment_id: ?int, segment_name: string}
     */
    public function matchSegment(int $frequency, int $rScore, int $fScore, int $mScore, Collection $segments): array
    {
        if ($frequency <= 0) {
            return ['segment_id' => null, 'segment_name' => 'Belum Aktif'];
        }

        foreach ($segments as $segment) {
            $rule = $segment->rule ?? [];
            $rMin = (int) ($rule['r_min'] ?? 1);
            $rMax = (int) ($rule['r_max'] ?? 5);
            $fMin = (int) ($rule['f_min'] ?? 1);
            $fMax = (int) ($rule['f_max'] ?? 5);
            $mMin = (int) ($rule['m_min'] ?? 1);
            $mMax = (int) ($rule['m_max'] ?? 5);

            if (
                $rScore >= $rMin && $rScore <= $rMax &&
                $fScore >= $fMin && $fScore <= $fMax &&
                $mScore >= $mMin && $mScore <= $mMax
            ) {
                return ['segment_id' => $segment->id, 'segment_name' => $segment->name];
            }
        }

        $fallback = $segments->last();

        return [
            'segment_id' => $fallback?->id,
            'segment_name' => $fallback?->name ?? 'Tidak Aktif',
        ];
    }

    /**
     * Runs a full RFM analysis across all Members and persists RfmAnalysis + RfmResult rows.
     * Monetary uses sum of orders.total_amount for Order::successful() in [analysis_date - period_days, analysis_date].
     */
    public function runAnalysis(?Carbon $analysisDate = null, ?int $periodDaysOverride = null, string $triggeredBy = 'system'): RfmAnalysis
    {
        $config = $this->getParameters();
        $periodDays = $periodDaysOverride ?? (int) ($config['period_days'] ?? 90);
        $endDate = ($analysisDate ? $analysisDate->copy() : now())->endOfDay();
        $startDate = $endDate->copy()->subDays($periodDays)->startOfDay();

        $segments = RfmSegment::query()->where('is_active', true)->orderBy('priority')->get();
        $members = Member::query()->get();

        return DB::transaction(function () use ($config, $periodDays, $endDate, $startDate, $segments, $members, $triggeredBy) {
            $analysis = RfmAnalysis::query()->create([
                'analysis_date' => $endDate->toDateString(),
                'period_days' => $periodDays,
                'parameters_snapshot' => $config,
                'triggered_by' => $triggeredBy,
                'is_demo' => (bool) config('app.demo_mode', false),
            ]);

            foreach ($members as $member) {
                $this->computeAndSaveMemberResult($analysis, $member, $startDate, $endDate, $config, $segments);
            }

            return $analysis->load('results.segment');
        });
    }

    /**
     * Incremental recompute for a single member on the latest RfmAnalysis (or creates one if none exists).
     */
    public function recomputeForMember(Member $member): RfmResult
    {
        $latestAnalysis = RfmAnalysis::query()->latest('id')->first();
        if (! $latestAnalysis) {
            $latestAnalysis = $this->runAnalysis(now(), null, 'auto_incremental');

            return RfmResult::query()
                ->where('rfm_analysis_id', $latestAnalysis->id)
                ->where('member_id', $member->id)
                ->firstOrFail();
        }

        $config = $this->getParameters();
        $periodDays = (int) ($config['period_days'] ?? 90);
        $endDate = now()->endOfDay();
        $startDate = $endDate->copy()->subDays($periodDays)->startOfDay();
        $segments = RfmSegment::query()->where('is_active', true)->orderBy('priority')->get();

        return $this->computeAndSaveMemberResult($latestAnalysis, $member, $startDate, $endDate, $config, $segments);
    }

    protected function computeAndSaveMemberResult(
        RfmAnalysis $analysis,
        Member $member,
        Carbon $startDate,
        Carbon $endDate,
        array $config,
        Collection $segments
    ): RfmResult {
        $orders = Order::query()
            ->successful()
            ->where('member_id', $member->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get(['id', 'total_amount', 'created_at']);

        $frequency = $orders->count();
        $monetary = (int) $orders->sum('total_amount');
        $recencyDays = null;

        if ($frequency > 0) {
            $lastOrderAt = Carbon::parse($orders->first()->created_at)->startOfDay();
            $recencyDays = max(0, (int) $lastOrderAt->diffInDays($endDate->copy()->startOfDay()));
        }

        $rScore = $this->scoreRecency($recencyDays, $config['r_thresholds'] ?? []);
        $fScore = $this->scoreFrequency($frequency, $config['f_thresholds'] ?? []);
        $mScore = $this->scoreMonetary($monetary, $config['m_thresholds'] ?? []);

        $matched = $this->matchSegment($frequency, $rScore, $fScore, $mScore, $segments);

        return RfmResult::query()->updateOrCreate(
            [
                'rfm_analysis_id' => $analysis->id,
                'member_id' => $member->id,
            ],
            [
                'recency_days' => $recencyDays,
                'frequency' => $frequency,
                'monetary' => $monetary,
                'r_score' => $rScore,
                'f_score' => $fScore,
                'm_score' => $mScore,
                'segment_id' => $matched['segment_id'],
                'segment_name' => $matched['segment_name'],
            ]
        );
    }
}
