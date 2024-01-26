<?php

namespace FpDbTest;

use Exception;
use mysqli;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected $queryBuilder;
    protected static $db;

    public static function setUpBeforeClass(): void
    {
        static::$db = @new mysqli(getenv('dbServer'), getenv('db_user'), getenv('db_pass'), getenv('db_name'), getenv('db_port'));
    }

    public function setUp(): void
    {
        $this->queryBuilder = new Database(static::$db);
    }

    public static function queryProvider()
    {
        return [
            ['SELECT name FROM users WHERE user_id = 1', [], 'SELECT name FROM users WHERE user_id = 1'],
            ['SELECT * FROM users WHERE name = ? AND block = 0', ['Jack'], 'SELECT * FROM users WHERE name = \'Jack\' AND block = 0'],
            ['SELECT ?# FROM users WHERE user_id = ?d AND block = ?d', [['name', 'email'], 2, true], 'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1'],
            ['UPDATE users SET ?a WHERE user_id = -1', [['name' => 'Jack', 'email' => null]], 'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',]
        ];
    }


    /**
     * @dataProvider queryProvider
     */
    public function testSimpleQuery($query, $params, $result)
    {
        $this->assertSame($this->queryBuilder->buildQuery($query, $params), $result);
    }

    public static function queryBlocksProvider()
    {
        return [
            ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3]], null, 'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)'],
            ['SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}', ['user_id', [1, 2, 3]], true, 'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1'],
        ];
    }

    /**
     * @dataProvider queryBlocksProvider
     */
    public function testQueryBlocks($query, $params, $block, $result)
    {
        $params[] = $block ?? $this->queryBuilder->skip();
        $this->assertSame($this->queryBuilder->buildQuery($query, $params), $result);
    }
}
