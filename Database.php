<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    private function paramReplacer($val, $argType)
    {
        if (in_array($argType, ['?d', '?f', 'integer', 'double'])) {
            return $val;
        }

        $type = gettype($val);
        switch ($type) {
            case 'string':
                $wrap = '\'';
                if ($argType == '?#') {
                    $wrap = '`';
                }
                return sprintf("$wrap%s$wrap", mysqli_escape_string($this->mysqli, $val));
            case 'NULL':
                return 'NULL';
            case 'double':
            case 'integer':
                return $val;
            case 'array':
                $vals = [];
                foreach ($val as $ind => $aVal) {
                    $vals[$ind] = $this->paramReplacer($aVal, $argType);
                }
                if (array_keys($vals) !== range(0, count($vals)-1)) {
                    foreach ($vals as $k => $v) {
                        $vals[$k] = "`$k` = $v";
                    }
                }

                return implode(', ', $vals);
        }

        return '<>';
    }

    public function buildQuery(string $query, array $args = []): string
    {
        // Если подставлять нечего ... вернём исходный запрос
        if (!$args) {
            return $query;
        }
        // замена аргументов
        preg_match_all('#\?[dfa\#]?#', $query, $ma);
        $ma = reset($ma);
        foreach ($ma as $ind => $param) {
            $query = preg_replace('#' . preg_quote($param) . '#', $this->paramReplacer($args[$ind], $param), $query, 1);
        }

        // замена блоков
        preg_match_all('#{([^}]+?)}#', $query, $blocks);
        foreach ($blocks[1] as $ind => $blockVal) {
            $query = preg_replace('#' . preg_quote($blocks[0][$ind]) . '#', strpos($blockVal, $this->skip()) === false ? $blocks[1][$ind] : '', $query);
        }

        return $query;
        // throw new Exception();
    }

    public function skip()
    {
        return '>!<';
        // throw new Exception();
    }
}
