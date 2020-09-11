<?php

function microTimeDiff(string $a, string $b): float
{
    $a = explode(' ', $a);
    $b = explode(' ', $b);
    return floatval(intval($b[1]) - intval($a[1])) + (floatval($b[0]) - floatval($a[0]));
}

function calcAverage(array $a)
{
    return array_sum($a) / count($a);
}

function calcMedian(array $a)
{
    $n = count($a);
    if (0 === $n) {
        return null;
    } elseif (1 === $n % 2) {
        return $a[intval($n/2)];
    } else {
        return ($a[$n/2] + $a[$n/2 - 1]) / 2;
    }
}
