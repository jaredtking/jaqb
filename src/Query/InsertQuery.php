<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace JAQB\Query;

use JAQB\Operations\Executable;
use JAQB\Statement\FromStatement;
use JAQB\Statement\ValuesStatement;

class InsertQuery extends AbstractQuery
{
    use Executable;

    /**
     * @var FromStatement
     */
    protected $table;

    /**
     * @var ValuesStatement
     */
    protected $insertValues;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var string
     */
    protected $onDuplicateKeyUpdate;

    public function __construct()
    {
        $this->table = new FromStatement(FromStatement::INSERT);
        $this->insertValues = new ValuesStatement();
    }

    /**
     * Sets the table for the query.
     *
     * @param string $table table name
     *
     * @return self
     */
    public function into($table)
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
        $this->insertValues->addValues($values);

        return $this;
    }

    /**
     * Gets the table for the query.
     *
     * @return string
     */
    public function getInto()
    {
        return $this->table;
    }

    /**
     * Gets the insert values for the query.
     *
     * @return ValuesStatement
     */
    public function getInsertValues()
    {
        return $this->insertValues;
    }

    /**
     * Adds an ON DUPLICATE KEY UPDATE clause.
     *
     * @param string $sql
     *
     * @return self
     */
    public function onDuplicateKeyUpdate($sql)
    {
        $this->onDuplicateKeyUpdate = $sql;

        return $this;
    }

    /**
     * Gets the ON DUPLICATE KEY UPDATE clause.
     *
     * @return string|null
     */
    public function getOnDuplicateKeyUpdate()
    {
        return $this->onDuplicateKeyUpdate;
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
            $this->insertValues->build(),
        ];

        if ($this->onDuplicateKeyUpdate) {
            $sql[] = 'ON DUPLICATE KEY UPDATE '.$this->onDuplicateKeyUpdate;
        }

        $this->values = array_values($this->insertValues->getValues());

        return implode(' ', array_filter($sql));
    }

    public function __clone()
    {
        $this->table = clone $this->table;
        $this->insertValues = clone $this->insertValues;
    }
}
