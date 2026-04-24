<?php

namespace Tests\Unit;

use App\Models\Property;
use PHPUnit\Framework\TestCase;

class PropertyShareholderPoolFactorTest extends TestCase
{
    public function test_full_factor_without_mushaa_partner(): void
    {
        $p = new Property([
            'mushaa_floors' => [3, 4],
            'mushaa_partner_name' => null,
        ]);

        $this->assertSame(1.0, $p->shareholderOperatingPoolFactorForFloor(3));
    }

    public function test_half_factor_on_mushaa_floor_when_partner_set(): void
    {
        $p = new Property([
            'mushaa_floors' => [5, 6],
            'mushaa_partner_name' => 'شريك خارجي',
        ]);

        $this->assertSame(0.5, $p->shareholderOperatingPoolFactorForFloor(5));
        $this->assertSame(0.5, $p->shareholderOperatingPoolFactorForFloor(6));
        $this->assertSame(1.0, $p->shareholderOperatingPoolFactorForFloor(2));
    }
}
