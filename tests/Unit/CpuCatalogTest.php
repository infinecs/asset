<?php

namespace Tests\Unit;

use App\Support\CpuCatalog;
use PHPUnit\Framework\TestCase;

class CpuCatalogTest extends TestCase
{
    public function test_core_ultra_series_is_detected_without_case_sensitivity(): void
    {
        $groups = CpuCatalog::groups([
            'Intel COre Ultra 5 325',
            'Intel Core Ultra 7 155H',
        ]);

        $this->assertSame([
            'Intel Core Ultra 5 125H',
            'Intel Core Ultra 5 125U',
            'Intel Core Ultra 5 135U',
            'Intel COre Ultra 5 325',
            'Intel Core Ultra 7 155H',
            'Intel Core Ultra 7 155U',
            'Intel Core Ultra 7 165H',
            'Intel Core Ultra 9 185H',
        ], $groups['Intel Core Ultra (14th Gen)']);
        $this->assertArrayNotHasKey('Previously Used', $groups);
    }

    public function test_added_intel_and_amd_cpus_are_grouped_by_model_generation(): void
    {
        $groups = CpuCatalog::groups([
            'Intel Core i7-14650HX',
            'AMD Ryzen 7 8840U',
        ]);

        $this->assertContains('Intel Core i7-14650HX', $groups['Intel 14th Gen']);
        $this->assertContains('AMD Ryzen 7 8840U', $groups['AMD Ryzen 8000 Series']);
        $this->assertSame([
            'Intel Core Ultra (14th Gen)',
            'Intel 14th Gen',
            'Intel 13th Gen (Raptor Lake)',
            'Intel 12th Gen (Alder Lake)',
            'Intel 11th Gen (Tiger Lake)',
            'Intel 10th Gen (Comet/Ice Lake)',
            'AMD Ryzen 8000 Series',
            'AMD Ryzen 7000 Series',
            'AMD Ryzen 6000 Series',
            'AMD Ryzen 5000 Series',
            'AMD Ryzen 3000 Series',
            'Apple',
        ], array_keys($groups));
    }

    public function test_existing_and_additional_cpu_names_are_not_duplicated(): void
    {
        $groups = CpuCatalog::groups(['intel core ultra 5 125u', 'Apple M4']);

        $this->assertSame(1, count(array_filter(
            $groups['Intel Core Ultra (14th Gen)'],
            fn (string $cpu): bool => strcasecmp($cpu, 'Intel Core Ultra 5 125U') === 0,
        )));
        $this->assertContains('Apple M4', $groups['Apple']);
    }
}
