<?php

namespace App\Support;

class DisplayCatalog
{
    private const DEFAULTS = [
        '11.6"',
        '13.0"',
        '13.3"',
        '13.6"',
        '14.0"',
        '14.2"',
        '15.6"',
        '16.0"',
        '17.3"',
        '18.5"',
        '21.45"',
        '21.5"',
    ];

    public static function normalize(string $display): string
    {
        $measurement = trim(rtrim(trim($display), '"'));

        return $measurement.'"';
    }

    public static function sort(iterable $displays): array
    {
        $options = [];
        foreach (array_merge(self::DEFAULTS, is_array($displays) ? $displays : iterator_to_array($displays)) as $display) {
            $display = self::normalize((string) $display);
            $options[$display] = $display;
        }

        $options = array_values($options);
        usort($options, static function (string $left, string $right): int {
            $numericOrder = (float) rtrim($left, '"') <=> (float) rtrim($right, '"');

            return $numericOrder ?: strnatcasecmp($left, $right);
        });

        return $options;
    }
}
