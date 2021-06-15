<?php
namespace Tests;

use Generator;

class Common {
    /**
     * 与えられた配列をかけざんする
     * @param $head array
     * @param ...$tail array
     * @return \Generator
     */
    public static function multipleArray(array $head, array ...$tail): Generator {
        if (count($tail) == 0) {
            foreach ($head as $item) yield $item;
            return;
        }
        foreach ($head as $item1) {
            foreach (self::multipleArray(...$tail) as $item2) {
                if (is_array($item2)) yield [$item1, ...$item2];
                else yield[$item1, $item2];
            }
        }
    }
}
