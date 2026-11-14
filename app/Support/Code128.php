<?php

namespace App\Support;

use InvalidArgumentException;

final class Code128
{
    private const START_B = 104;

    private const STOP = 106;

    /**
     * @var array<int, string>
     */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /**
     * @return array<int, bool>
     */
    public static function modules(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $symbols = [self::START_B];
        $checksum = self::START_B;

        foreach (str_split($content) as $position => $character) {
            $value = ord($character) - 32;

            if ($value < 0 || $value > 94) {
                throw new InvalidArgumentException("El carácter «{$character}» no es válido para Code 128.");
            }

            $symbols[] = $value;
            $checksum += $value * ($position + 1);
        }

        $symbols[] = $checksum % 103;
        $symbols[] = self::STOP;

        $modules = [];

        foreach ($symbols as $symbol) {
            $bar = true;

            foreach (str_split(self::PATTERNS[$symbol]) as $width) {
                $count = (int) $width;

                for ($index = 0; $index < $count; $index++) {
                    $modules[] = $bar;
                }

                $bar = ! $bar;
            }
        }

        return $modules;
    }

    public static function svg(string $content, int $width = 330, int $height = 74, int $quietZone = 10): string
    {
        $modules = self::modules($content);

        if ($modules === []) {
            return '';
        }

        $total = count($modules) + ($quietZone * 2);
        $bars = '';
        $index = 0;

        while ($index < count($modules)) {
            if (! $modules[$index]) {
                $index++;

                continue;
            }

            $start = $index;

            while ($index < count($modules) && $modules[$index]) {
                $index++;
            }

            $x = $quietZone + $start;
            $barWidth = $index - $start;
            $bars .= '<rect x="'.$x.'" y="0" width="'.$barWidth.'" height="'.$height.'" />';
        }

        $label = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$height.'" width="'.$width.'" height="'.$height.'" preserveAspectRatio="none" fill="#000" role="img" aria-label="Código de barras '.$label.'" shape-rendering="crispEdges">'.$bars.'</svg>';
    }
}
