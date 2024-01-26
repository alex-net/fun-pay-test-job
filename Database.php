<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    /**
     * Объект соединения с базой даннных
     */
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * метод рекурсивного прербразования найденных значений в нужный формат + возможное экранирование в случае строкового значения
     *
     * @param      mixed  $val       Значение параметра
     * @param      string  $argType  Тип параметра определённый из строки формата или из типа самого параметра, если в строке формата не указан нужный тип (обозначение ?)
     *
     * @return     string  Подготовленное значение для вставки в строку запроса ..
     */
    private function paramReplacer(mixed $val, string $argType): string
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
                if (array_keys($vals) !== range(0, count($vals) - 1)) {
                    foreach ($vals as $k => $v) {
                        $vals[$k] = "`$k` = $v";
                    }
                }

                return implode(', ', $vals);
        }

        return '<>';
    }

    /**
     * Построитель запроса
     *
     * @param      string  $query  Строка формата
     * @param      array   $args   аргументы запроса ..
     *
     * @return     string  Собранный запрос готовый к выполнению
     */
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

    /**
     * возвращаем конструкцию, позволяющую пропустить условный блок
     *
     * @return     string  Конструкция, наличие которой в условном блоке строки запроса говорит о том, что блок должен быть пропущен
     */
    public function skip(): string
    {
        return '>!<';
        // throw new Exception();
    }
}
