<?php

namespace Tests\Unit;

use App\Domain\Rfm\RfmService;
use App\Models\RfmSegment;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RfmService $rfmService;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->rfmService = app(RfmService::class);
        $this->config = $this->rfmService->getParameters();
    }

    public function test_rfm_recency_score_deterministic_boundary_conditions(): void
    {
        $r = $this->config['r_thresholds'];

        $this->assertEquals(5, $this->rfmService->scoreRecency(0, $r));
        $this->assertEquals(5, $this->rfmService->scoreRecency(7, $r));
        $this->assertEquals(4, $this->rfmService->scoreRecency(8, $r));
        $this->assertEquals(4, $this->rfmService->scoreRecency(14, $r));
        $this->assertEquals(3, $this->rfmService->scoreRecency(15, $r));
        $this->assertEquals(3, $this->rfmService->scoreRecency(30, $r));
        $this->assertEquals(2, $this->rfmService->scoreRecency(31, $r));
        $this->assertEquals(2, $this->rfmService->scoreRecency(60, $r));
        $this->assertEquals(1, $this->rfmService->scoreRecency(61, $r));
        $this->assertEquals(1, $this->rfmService->scoreRecency(120, $r));
        $this->assertEquals(1, $this->rfmService->scoreRecency(null, $r));
    }

    public function test_rfm_frequency_score_deterministic_boundary_conditions(): void
    {
        $f = $this->config['f_thresholds'];

        $this->assertEquals(5, $this->rfmService->scoreFrequency(15, $f));
        $this->assertEquals(5, $this->rfmService->scoreFrequency(8, $f));
        $this->assertEquals(4, $this->rfmService->scoreFrequency(7, $f));
        $this->assertEquals(4, $this->rfmService->scoreFrequency(5, $f));
        $this->assertEquals(3, $this->rfmService->scoreFrequency(4, $f));
        $this->assertEquals(3, $this->rfmService->scoreFrequency(3, $f));
        $this->assertEquals(2, $this->rfmService->scoreFrequency(2, $f));
        $this->assertEquals(1, $this->rfmService->scoreFrequency(1, $f));
        $this->assertEquals(1, $this->rfmService->scoreFrequency(0, $f));
    }

    public function test_rfm_monetary_score_deterministic_boundary_conditions(): void
    {
        $m = $this->config['m_thresholds'];

        $this->assertEquals(5, $this->rfmService->scoreMonetary(1500000, $m));
        $this->assertEquals(5, $this->rfmService->scoreMonetary(1000000, $m));
        $this->assertEquals(4, $this->rfmService->scoreMonetary(999999, $m));
        $this->assertEquals(4, $this->rfmService->scoreMonetary(600000, $m));
        $this->assertEquals(3, $this->rfmService->scoreMonetary(599999, $m));
        $this->assertEquals(3, $this->rfmService->scoreMonetary(300000, $m));
        $this->assertEquals(2, $this->rfmService->scoreMonetary(299999, $m));
        $this->assertEquals(2, $this->rfmService->scoreMonetary(100000, $m));
        $this->assertEquals(1, $this->rfmService->scoreMonetary(99999, $m));
        $this->assertEquals(1, $this->rfmService->scoreMonetary(0, $m));
    }

    public function test_rfm_segment_matching_against_hand_calculated_fixtures(): void
    {
        $segments = RfmSegment::query()->where('is_active', true)->orderBy('priority')->get();

        // 1. Champions / Pelanggan Setia (R >= 4, F >= 4, M >= 4)
        $result = $this->rfmService->matchSegment(5, 5, 5, 5, $segments);
        $this->assertEquals('Pelanggan Setia', $result['segment_name']);

        // 2. Loyal Customers / Pelanggan Loyal (R >= 3, F >= 3, M >= 1)
        $result = $this->rfmService->matchSegment(4, 3, 4, 3, $segments);
        $this->assertEquals('Pelanggan Loyal', $result['segment_name']);

        // 3. Potential / Berpotensi (R >= 3, F <= 2)
        $result = $this->rfmService->matchSegment(2, 4, 2, 2, $segments);
        $this->assertEquals('Berpotensi', $result['segment_name']);

        // 4. At Risk / Berisiko (R <= 2, F >= 3)
        $result = $this->rfmService->matchSegment(4, 2, 4, 4, $segments);
        $this->assertEquals('Berisiko', $result['segment_name']);

        // 5. Inactive / Tidak Aktif (R <= 2, F <= 2)
        $result = $this->rfmService->matchSegment(1, 2, 2, 2, $segments);
        $this->assertEquals('Tidak Aktif', $result['segment_name']);

        // 6. Belum Aktif (raw Frequency = 0)
        $result = $this->rfmService->matchSegment(0, 1, 1, 1, $segments);
        $this->assertEquals('Belum Aktif', $result['segment_name']);
    }
}
