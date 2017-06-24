<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use JAQB\Query\DeleteQuery;
use JAQB\Query\SelectQuery;

class DeleteQueryTest extends PHPUnit_Framework_TestCase
{
    public function testFrom()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->from('Users'));
        $this->assertInstanceOf('JAQB\Statement\FromStatement', $query->getFrom());
        $this->assertEquals(['Users'], $query->getFrom()->getTables());
    }

    public function testWhere()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->where('balance', 10, '>'));
        $this->assertEquals($query, $query->where('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('JAQB\Statement\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10], ['notes IS NULL']], $where->getConditions());
    }

    public function testOrWhere()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->orWhere('balance', 10, '>'));
        $this->assertEquals($query, $query->orWhere('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('JAQB\Statement\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['OR'], ['balance', '>', 10], ['OR'], ['notes IS NULL']], $where->getConditions());
    }

    public function testWhereInfix()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->whereInfix('balance', '>', 10));
        $this->assertEquals($query, $query->whereInfix('name', 'Bob'));
        $this->assertEquals($query, $query->whereInfix('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('JAQB\Statement\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['balance', '>', 10], ['name', '=', 'Bob'], ['notes IS NULL']], $where->getConditions());
    }

    public function testOrWhereInfix()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->orWhereInfix('balance', '>', 10));
        $this->assertEquals($query, $query->orWhereInfix('name', 'Bob'));
        $this->assertEquals($query, $query->orWhereInfix('notes IS NULL'));
        $where = $query->getWhere();
        $this->assertInstanceOf('JAQB\Statement\WhereStatement', $where);
        $this->assertFalse($where->isHaving());
        $this->assertEquals([['OR'], ['balance', '>', 10], ['OR'], ['name', '=', 'Bob'], ['OR'], ['notes IS NULL']], $where->getConditions());
    }

    public function testNot()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->not('disabled'));
        $this->assertEquals($query, $query->not('group', 'admin'));
        $this->assertEquals($query, $query->not('group', null));
        $this->assertEquals($query, $query->not('name', ['Larry', 'Curly', 'Moe']));
        $this->assertEquals([['disabled', '<>', true], ['group', '<>', 'admin'], ['group', '<>', null], ['name', '<>', ['Larry', 'Curly', 'Moe']]], $query->getWhere()->getConditions());
    }

    public function testBetween()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->between('date', 2015, 2016));
        $this->assertEquals([['BETWEEN', 'date', 2015, 2016, true]], $query->getWhere()->getConditions());
    }

    public function testNotBetween()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->notBetween('date', 2015, 2016));
        $this->assertEquals([['BETWEEN', 'date', 2015, 2016, false]], $query->getWhere()->getConditions());
    }

    public function testExists()
    {
        $query = new DeleteQuery();

        $f = function (SelectQuery $query) {};

        $this->assertEquals($query, $query->exists($f));
        $this->assertEquals([['EXISTS', $f, true]], $query->getWhere()->getConditions());
    }

    public function testNotExists()
    {
        $query = new DeleteQuery();

        $f = function (SelectQuery $query) {};

        $this->assertEquals($query, $query->notExists($f));
        $this->assertEquals([['EXISTS', $f, false]], $query->getWhere()->getConditions());
    }

    public function testOrderBy()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->orderBy('uid', 'ASC'));
        $orderBy = $query->getOrderBy();
        $this->assertInstanceOf('JAQB\Statement\OrderStatement', $orderBy);
        $this->assertFalse($orderBy->isGroupBy());
        $this->assertEquals([['uid', 'ASC']], $orderBy->getFields());
    }

    public function testLimit()
    {
        $query = new DeleteQuery();

        $this->assertEquals($query, $query->limit(10));
        $limit = $query->getLimit();
        $this->assertInstanceOf('JAQB\Statement\LimitStatement', $limit);
        $this->assertEquals(10, $limit->getLimit());

        $this->assertEquals($query, $query->limit('hello'));
        $this->assertEquals(10, $query->getLimit()->getLimit());
    }

    public function testBuild()
    {
        $query = new DeleteQuery();

        $query->from('Users')
              ->where('uid', 10)
              ->between('created_at', '2016-04-01', '2016-04-30')
              ->notBetween('balance', 100, 150)
              ->not('disabled')
              ->orWhere('admin', true)
              ->limit(100)
              ->orderBy('uid', 'ASC');

        // test for idempotence
        for ($i = 0; $i < 3; ++$i) {
            $this->assertEquals('DELETE FROM `Users` WHERE `uid` = ? AND `created_at` BETWEEN ? AND ? AND `balance` NOT BETWEEN ? AND ? AND `disabled` <> ? OR `admin` = ? ORDER BY `uid` ASC LIMIT 100', $query->build());

            // test values
            $this->assertEquals([10, '2016-04-01', '2016-04-30', 100, 150, true, true], $query->getValues());
        }
    }

    public function testClone()
    {
        $query = new DeleteQuery();
        $query2 = clone $query;
        $this->assertNotSame($query->getFrom(), $query2->getFrom());
        $this->assertNotSame($query->getWhere(), $query2->getWhere());
        $this->assertNotSame($query->getOrderBy(), $query2->getOrderBy());
        $this->assertNotSame($query->getLimit(), $query2->getLimit());
    }

    ////////////////////////
    // Operations
    ////////////////////////

    public function testExecute()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('rowCount')->andReturn(10);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->withArgs(['DELETE FROM `Test` WHERE `id` = ?'])
            ->andReturn($stmt);

        $query = new DeleteQuery();
        $query->setPDO($pdo);
        $this->assertEquals($pdo, $query->getPDO());
        $query->from('Test')->where('id', 'test');

        $this->assertEquals($stmt, $query->execute());
        $this->assertEquals(10, $query->rowCount());
    }

    public function testExecuteFail()
    {
        $stmt = Mockery::mock();
        $stmt->shouldReceive('execute')->andReturn(false);

        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmt);

        $query = new DeleteQuery();
        $query->setPDO($pdo);

        $this->assertFalse($query->execute());
    }
}
