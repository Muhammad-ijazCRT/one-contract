<?php

namespace App\Support;

/**
 * Prediction Duel "big road" layout.
 *
 * 1–4 of the same outcome stack in one vertical column (unchanged).
 * The 5th+ consecutive identical outcome continues horizontally
 * from the 4th cell. A different outcome starts a new column from the top,
 * after the previous streak (including any horizontal tail).
 */
final class PredictionTrendRoad
{
    public const MAX_HEIGHT = 4;

    /**
     * @param  iterable<int|string|null>  $history
     * @return list<list<int|null>>
     */
    public static function fromHistory(iterable $history, int $limit = 50, int $maxHeight = self::MAX_HEIGHT): array
    {
        $all = [];
        foreach ($history as $item) {
            $all[] = $item;
        }

        if ($limit > 0 && count($all) > $limit) {
            $all = array_slice($all, -$limit);
        }

        return self::columns($all, $maxHeight);
    }

    /**
     * @param  list<int|string|null>  $history
     * @return list<list<int|null>>
     */
    public static function columns(array $history, int $maxHeight = self::MAX_HEIGHT): array
    {
        $outcomes = [];
        foreach ($history as $item) {
            $n = (int) $item;
            if ($n === 1 || $n === 2) {
                $outcomes[] = $n;
            }
        }

        $columns = [];
        $i = 0;
        $n = count($outcomes);

        while ($i < $n) {
            $value = $outcomes[$i];
            $streak = 0;
            while ($i < $n && $outcomes[$i] === $value) {
                $streak++;
                $i++;
            }

            $startCol = count($columns);
            $columns[] = array_fill(0, $maxHeight, null);

            for ($s = 0; $s < $streak; $s++) {
                if ($s < $maxHeight) {
                    $columns[$startCol][$s] = $value;

                    continue;
                }

                $colIdx = $startCol + ($s - $maxHeight + 1);
                while (count($columns) <= $colIdx) {
                    $columns[] = array_fill(0, $maxHeight, null);
                }
                $columns[$colIdx][$maxHeight - 1] = $value;
            }
        }

        return $columns;
    }
}
