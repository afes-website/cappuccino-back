<?php
namespace Tests;

use Generator;

class Common {
    /**
     * 与えられた配列の直積をとる
     * @param $head array
     * @param ...$tail array
     * @return \Generator
     */
    public static function multipleArray(array $head, array ...$tail): Generator {
        if (count($tail) == 0) {
            foreach ($head as $item) yield [$item];
            return;
        }
        foreach ($head as $item1) {
            foreach (self::multipleArray(...$tail) as $item2) {
                yield [$item1, ...$item2];
            }
        }
    }
}
