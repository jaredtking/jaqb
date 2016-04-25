<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace JAQB\Query;

use JAQB\Operations\Executable;
use JAQB\Statement\FromStatement;
use JAQB\Statement\LimitStatement;
use JAQB\Statement\OrderStatement;
use JAQB\Statement\SetStatement;
use JAQB\Statement\WhereStatement;
use JAQB\Query\Traits\WhereConditions;

class UpdateQuery extends AbstractQuery
{
    use Executable, WhereConditions;

    /**
     * @var FromStatement
     */
    protected $table;

    /**
     * @var SetStatement
     */
    protected $set;

    /**
     * @var OrderStatement
     */
    protected $orderBy;

    /**
     * @var LimitStatement
     */
    protected $limit;

    public function __construct()
    {
        $this->table = new FromStatement(FromStatement::UPDATE);
        $this->set = new SetStatement();
        $this->where = new WhereStatement();
        $this->orderBy = new OrderStatement();
        $this->limit = new LimitStatement();
    }

    /**
     * Sets the table for the query.
     *
     * @param string $table table name
     *
     * @return self
     */
    public function table($table)
    {
        $this->table->addTable($table);

        return $this;
    }

    /**
     * Sets the values for the query.
     *
     * @param array $values
     *
     * @return self
     */
    public function values(array $values)
    {
        $this->set->addValues($values);

        return $this;
    }

    /**
     * Sets the limit for the query.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return self
     */
    public function limit($limit, $offset = 0)
    {
        $this->limit->setLimit($limit, $offset);

        return $this;
    }

    /**
     * Sets the order for the query.
     *
     * @param string|array $fields
     * @param string       $direction
     *
     * @return self
     */
    public function orderBy($fields, $direction = false)
    {
        $this->orderBy->addFields($fields, $direction);

        return $this;
    }

    /**
     * Gets the table name for the query.
     *
     * @return FromStatement
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Gets the values for the query.
     *
     * @return array
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
     * Gets the order by statement for the query.
     *
     * @return OrderByStatement
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Gets the limit statement for the query.
     *
     * @return LimitStatement
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Generates the raw SQL string for the query.
     *
     * @return string
     */
    public function build()
    {
        $sql = [
            $this->table->build(),
            $this->set->build(),
            $this->where->build(),
            $this->orderBy->build(),
            $this->limit->build(),
        ];

        $this->values = array_merge(
            array_values($this->set->getValues()),
            $this->where->getValues());

        return implode(' ', array_filter($sql));
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->set = clone $this->set;
        $this->where = clone $this->where;
        $this->orderBy = clone $this->orderBy;
        $this->limit = clone $this->limit;
    }
}
