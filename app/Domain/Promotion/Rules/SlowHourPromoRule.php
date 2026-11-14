<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Promotion;
use Carbon\Carbon;

class SlowHourPromoRule implements PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        $conditions = $promotion->conditions ?? [];
        // Default: Monday (1) through Friday (5), 14:00 to 16:00 WIB
        $weekdays = $conditions['weekdays'] ?? [1, 2, 3, 4, 5];
        $timeFrom = (string) ($conditions['time_from'] ?? '14:00');
        $timeTo = (string) ($conditions['time_to'] ?? '16:00');

        $localNow = $now->copy()->setTimezone('Asia/Jakarta');
        $dayOfWeekIso = (int) $localNow->dayOfWeekIso; // 1 (Mon) .. 7 (Sun)

        $normalizedWeekdays = array_map('intval', (array) $weekdays);
        if (! in_array($dayOfWeekIso, $normalizedWeekdays, true)) {
            return false;
        }

        $currentTime = $localNow->format('H:i');

        return $currentTime >= $timeFrom && $currentTime <= $timeTo;
    }
}
