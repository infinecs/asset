<?php

namespace App\Support;

class CpuCatalog
{
    public static function groups(iterable $additionalCpus = []): array
    {
        $groups = [
            'Intel Core Ultra (14th Gen)' => [
                'Intel Core Ultra 5 125U', 'Intel Core Ultra 5 125H', 'Intel Core Ultra 5 135U',
                'Intel Core Ultra 7 155U', 'Intel Core Ultra 7 155H', 'Intel Core Ultra 7 165H',
                'Intel Core Ultra 9 185H',
            ],
            'Intel 13th Gen (Raptor Lake)' => [
                'Intel Core i3-1305U', 'Intel Core i3-1315U',
                'Intel Core i5-1335U', 'Intel Core i5-1345U', 'Intel Core i5-13500H', 'Intel Core i5-13600H',
                'Intel Core i7-1355U', 'Intel Core i7-1365U', 'Intel Core i7-13700H', 'Intel Core i7-13800H',
                'Intel Core i9-13900H', 'Intel Core i9-13950HX',
            ],
            'Intel 12th Gen (Alder Lake)' => [
                'Intel Core i3-1215U', 'Intel Core i3-1220P',
                'Intel Core i5-1235U', 'Intel Core i5-1240P', 'Intel Core i5-1250P',
                'Intel Core i5-12450H', 'Intel Core i5-12450HX', 'Intel Core i5-12500H', 'Intel Core i5-12600H',
                'Intel Core i7-1260P', 'Intel Core i7-1270P',
                'Intel Core i7-12700H', 'Intel Core i7-12800H', 'Intel Core i7-12800HX',
                'Intel Core i9-12900H', 'Intel Core i9-12900HK',
            ],
            'Intel 11th Gen (Tiger Lake)' => [
                'Intel Core i3-1115G4', 'Intel Core i3-1125G4',
                'Intel Core i5-1135G7', 'Intel Core i5-1155G7',
                'Intel Core i5-11300H', 'Intel Core i5-11400H',
                'Intel Core i7-1165G7', 'Intel Core i7-1185G7',
                'Intel Core i7-11370H', 'Intel Core i7-11800H',
                'Intel Core i9-11900H',
            ],
            'Intel 10th Gen (Comet/Ice Lake)' => [
                'Intel Core i3-10110U', 'Intel Core i3-1005G1',
                'Intel Core i5-10210U', 'Intel Core i5-10310U', 'Intel Core i5-10500H',
                'Intel Core i7-10510U', 'Intel Core i7-10750H', 'Intel Core i7-10850H',
            ],
            'AMD Ryzen 7000 Series' => [
                'AMD Ryzen 3 7330U',
                'AMD Ryzen 5 7530U', 'AMD Ryzen 5 7535U', 'AMD Ryzen 5 7600H',
                'AMD Ryzen 7 7730U', 'AMD Ryzen 7 7735U', 'AMD Ryzen 7 7745HX',
                'AMD Ryzen 9 7940HS', 'AMD Ryzen 9 7945HX',
            ],
            'AMD Ryzen 6000 Series' => [
                'AMD Ryzen 5 6600U', 'AMD Ryzen 5 6600H',
                'AMD Ryzen 7 6800U', 'AMD Ryzen 7 6800H',
                'AMD Ryzen 9 6900HX',
            ],
            'AMD Ryzen 5000 Series' => [
                'AMD Ryzen 3 5300U',
                'AMD Ryzen 5 5500U', 'AMD Ryzen 5 5600U', 'AMD Ryzen 5 5600H',
                'AMD Ryzen 7 5700U', 'AMD Ryzen 7 5800H',
                'AMD Ryzen 9 5900HS', 'AMD Ryzen 9 5900HX',
            ],
            'AMD Ryzen 3000 Series' => [
                'AMD Ryzen 5 3500U', 'AMD Ryzen 5 3550H',
                'AMD Ryzen 7 3700U', 'AMD Ryzen 7 3750H',
            ],
            'Apple' => [
                'Apple M1', 'Apple M1 Pro', 'Apple M1 Max',
                'Apple M2', 'Apple M2 Pro', 'Apple M2 Max',
                'Apple M3', 'Apple M3 Pro', 'Apple M3 Max',
            ],
        ];

        $knownNames = [];
        foreach ($groups as $cpus) {
            foreach ($cpus as $cpu) {
                $knownNames[mb_strtolower($cpu)] = true;
            }
        }

        $newGroups = [];
        foreach ($additionalCpus as $cpu) {
            $cpu = trim($cpu);
            $key = mb_strtolower($cpu);
            if ($cpu === '' || isset($knownNames[$key])) {
                continue;
            }

            $group = self::groupFor($cpu);
            $newGroups[$group][] = $cpu;
            $knownNames[$key] = true;
        }

        ksort($newGroups, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($newGroups as $group => $cpus) {
            sort($cpus, SORT_NATURAL | SORT_FLAG_CASE);
            $groups[$group] = array_merge($groups[$group] ?? [], $cpus);
        }

        foreach ($groups as &$cpus) {
            usort($cpus, 'strnatcasecmp');
        }
        unset($cpus);

        uksort($groups, function (string $left, string $right): int {
            $leftRank = self::groupRank($left);
            $rightRank = self::groupRank($right);

            return $leftRank <=> $rightRank ?: strnatcasecmp($left, $right);
        });

        return $groups;
    }

    private static function groupRank(string $group): int
    {
        if ($group === 'Intel Core Ultra (14th Gen)') {
            return 0;
        }

        if (preg_match('/^Intel (\d+)th Gen$/', $group, $matches)) {
            return 100 - (int) $matches[1];
        }

        if (preg_match('/^Intel \d+th Gen /', $group)) {
            preg_match('/^Intel (\d+)th Gen /', $group, $matches);

            return 100 - (int) $matches[1];
        }

        if ($group === 'Intel Other') {
            return 150;
        }

        if (preg_match('/^AMD Ryzen (\d+) Series$/', $group, $matches)) {
            return 200 - (int) ($matches[1] / 1000);
        }

        if ($group === 'AMD Other') {
            return 300;
        }

        if ($group === 'Apple') {
            return 400;
        }

        return 500;
    }

    public static function groupFor(string $cpu): string
    {
        if (preg_match('/\bIntel\s+Core\s+Ultra\s+[3579]\s+(\d{3})/i', $cpu, $matches)) {
            return 'Intel Core Ultra (14th Gen)';
        }

        if (preg_match('/\bIntel\s+Core\s+i[3579]-?(\d{4,5})/i', $cpu, $matches)) {
            $model = $matches[1];
            $generation = (int) (str_starts_with($model, '1') ? substr($model, 0, 2) : $model[0]);
            $knownGenerations = [
                10 => 'Intel 10th Gen (Comet/Ice Lake)',
                11 => 'Intel 11th Gen (Tiger Lake)',
                12 => 'Intel 12th Gen (Alder Lake)',
                13 => 'Intel 13th Gen (Raptor Lake)',
            ];

            return $knownGenerations[$generation] ?? "Intel {$generation}th Gen";
        }

        if (preg_match('/\bAMD\s+Ryzen\s+[3579]\s+(\d{4})/i', $cpu, $matches)) {
            $series = (int) $matches[1][0] * 1000;

            return "AMD Ryzen {$series} Series";
        }

        if (preg_match('/\bApple\s+M\d+/i', $cpu)) {
            return 'Apple';
        }

        if (preg_match('/\bIntel\b/i', $cpu)) {
            return 'Intel Other';
        }

        if (preg_match('/\bAMD\b/i', $cpu)) {
            return 'AMD Other';
        }

        return 'Other CPUs';
    }
}
