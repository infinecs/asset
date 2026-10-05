<?php

namespace Tests\Unit;

use App\Support\DisplayCatalog;
use PHPUnit\Framework\TestCase;

class DisplayCatalogTest extends TestCase
{
    public function test_normalization_ensures_exactly_one_trailing_inch_symbol(): void
    {
        $this->assertSame('14.0"', DisplayCatalog::normalize(' 14.0 '));
        $this->assertSame('14.0"', DisplayCatalog::normalize('14.0"'));
        $this->assertSame('14.0"', DisplayCatalog::normalize('14.0""'));
    }

    public function test_options_are_unique_and_numerically_sorted(): void
    {
        $options = DisplayCatalog::sort(['21.5', '14.0"', '13.0', '11.6"', '21.45"', '14.0']);

        $this->assertSame([
            '11.6"', '13.0"', '13.3"', '13.6"', '14.0"', '14.2"',
            '15.6"', '16.0"', '17.3"', '18.5"', '21.45"', '21.5"',
        ], array_slice($options, 0, 12));
        $this->assertSame(1, count(array_filter($options, fn (string $value): bool => $value === '14.0"')));
    }
}
