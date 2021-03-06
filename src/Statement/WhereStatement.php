<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace JAQB\Statement;

use JAQB\Query\SelectQuery;

class WhereStatement extends Statement
{
    /**
     * @var bool
     */
    protected $having;

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @param bool $having when true, statement becomes a having statement
     */
    public function __construct($having = false)
    {
        $this->having = $having;
    }

    /**
     * Tells whether this statement is a HAVING statement.
     *
     * @return bool true: is HAVING, false: is WHERE
     */
    public function isHaving()
    {
        return $this->having;
    }

    /**
     * Adds a condition to the statement.
     *
     * Accepts the following forms:
     * 1. Equality comparison:
     *      addCondition('username', 'john')
     * 2. Comparison with custom operator:
     *      addCondition('balance', 100, '>')
     * 3. IN statement:
     *      addCondition('group', ['admin', 'owner'])
     * 4. SQL fragment:
     *      addCondition('name LIKE "%john%"')
     * 5. Subquery:
     *      addCondition(function(SelectQuery $query) {})
     * 6. List of conditions to add:
     *      addCondition([['balance', 100, '>'],
     *                    ['user_id', 5]])
     * 7. Map of equality comparisons:
     *      addCondition(['username' => 'john',
     *                    'user_id' => 5])
     * 8. List of SQL fragments:
     *      addCondition(['first_name LIKE "%john%"',
     *                    'last_name LIKE "%doe%"'])
     *
     * @param array|string $field
     * @param string|bool  $value    condition value (optional)
     * @param string       $operator operator (optional)
     *
     * @return self
     */
    public function addCondition($field, $value = false, $operator = '=')
    {
        if (is_array($field) && !$value) {
            foreach ($field as $key => $value) {
                // handles #6
                if (is_array($value)) {
                    call_user_func_array([$this, 'addCondition'], $value);
                // handles #7
                } elseif (!is_numeric($key)) {
                    $this->addCondition($key, $value);
                // handles #8
                } else {
                    $this->addCondition($value);
                }
            }

            return $this;
        }

        // handles #4 and #5
        $condition = [$field];

        // handles #1, #2, and #3
        if (func_num_args() >= 2) {
            $condition[] = $operator;
            $condition[] = $value;
        }

        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * Adds an OR condition. Uses same arguments as
     * adding AND conditions.
     *
     * @return self
     */
    public function addOrCondition()
    {
        $this->conditions[] = ['OR'];

        return call_user_func_array([$this, 'addCondition'], func_get_args());
    }

    /**
     * Adds a between condition to the query.
     *
     * @param string $field
     * @param mixed  $a     first between value
     * @param mixed  $b     second between value
     *
     * @return self
     */
    public function addBetweenCondition($field, $a, $b)
    {
        $this->conditions[] = ['BETWEEN', $field, $a, $b, true];

        return $this;
    }

    /**
     * Adds a not between condition to the query.
     *
     * @param string $field
     * @param mixed  $a     first between value
     * @param mixed  $b     second between value
     *
     * @return self
     */
    public function addNotBetweenCondition($field, $a, $b)
    {
        $this->conditions[] = ['BETWEEN', $field, $a, $b, false];

        return $this;
    }

    /**
     * Adds an exists condition to the query.
     *
     * @param callable $f
     *
     * @return self
     */
    public function addExistsCondition(callable $f)
    {
        $this->conditions[] = ['EXISTS', $f, true];

        return $this;
    }

    /**
     * Adds a not exists condition to the query.
     *
     * @param callable $f
     *
     * @return self
     */
    public function addNotExistsCondition(callable $f)
    {
        $this->conditions[] = ['EXISTS', $f, false];

        return $this;
    }

    /**
     * Gets the conditions for this statement.
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    public function build()
    {
        // reset the parameterized values
        $this->values = [];

        // build clause from conditions
        $clauses = [];
        foreach ($this->conditions as $condition) {
            $clauses[] = $this->buildClause($condition);
        }

        // remove empty values
        $clauses = array_filter($clauses);

        if (count($clauses) == 0) {
            return '';
        }

        return ((!$this->having) ? 'WHERE ' : 'HAVING ').$this->implodeClauses($clauses);
    }

    /**
     * Builds a parameterized and escaped SQL fragment
     * for a condition that uses our own internal
     * representation.
     *
     * A condition is represented by an array, and can be
     * have one of the following forms:
     * 1. ['SQL fragment']
     * 2. ['identifier', '=', 'value']
     * 3. ['BETWEEN', 'identifier', 'value', 'value', true]
     * 4. ['EXISTS', function(SelectQuery $query) {}, true]
     * 5. [function(SelectQuery $query) {}]
     * 6. [function(SelectQuery $query) {}, '=', 'value']
     *
     * @param array $cond
     *
     * @return string generated SQL fragment
     */
    protected function buildClause(array $cond)
    {
        // handle SQL fragments
        if (count($cond) == 1 && (is_string($cond[0]) || !is_callable($cond[0]))) {
            return $cond[0];
        }

        // handle EXISTS conditions
        if ($cond[0] === 'EXISTS') {
            return $this->buildExists($cond[1], $cond[2]);
        }

        // handle BETWEEN conditions
        if ($cond[0] === 'BETWEEN') {
            return $this->buildBetween($cond[1], $cond[2], $cond[3], $cond[4]);
        }

        // escape an identifier
        if (is_string($cond[0]) || !is_callable($cond[0])) {
            $cond[0] = $this->escapeIdentifier($cond[0]);

        // handle a subquery
        // NOTE string callables are not supported
        // as subquery functions
        } elseif (is_callable($cond[0])) {
            $cond[0] = $this->buildSubquery($cond[0]);
        }

        if (count($cond) === 1 || empty($cond[0])) {
            return $cond[0];
        }

        // handle NULL values
        if ($cond[2] === null && in_array($cond[1], ['=', '<>'])) {
            return $this->buildNull($cond[0], $cond[1] == '=');
        }

        // handle IN values
        if (is_array($cond[2]) && in_array($cond[1], ['=', '<>'])) {
            return $this->buildIn($cond[0], $cond[2], $cond[1] == '=');
        }

        // otherwise parameterize the value
        $cond[2] = $this->parameterize($cond[2]);

        return implode(' ', $cond);
    }

    /**
     * Builds a subquery.
     *
     * @param callable $f
     *
     * @return string
     */
    protected function buildSubquery(callable $f)
    {
        $query = new SelectQuery();
        $query->getSelect()->clearFields();
        $f($query);
        $sql = $query->build();
        $this->values = array_merge($this->values, $query->getValues());

        return '('.$sql.')';
    }

    /**
     * Builds an EXISTS clause.
     *
     * @param callable $f
     * @param bool     $isExists
     *
     * @return string
     */
    protected function buildExists(callable $f, $isExists)
    {
        $operator = $isExists ? 'EXISTS' : 'NOT EXISTS';

        return $operator.' '.$this->buildSubquery($f);
    }

    /**
     * Builds a BETWEEN clause.
     *
     * @param string $field
     * @param mixed  $value1
     * @param mixed  $value2
     * @param bool   $isBetween
     *
     * @return string
     */
    protected function buildBetween($field, $value1, $value2, $isBetween)
    {
        $operator = $isBetween ? 'BETWEEN' : 'NOT BETWEEN';

        return $this->escapeIdentifier($field).' '.$operator.' '.$this->parameterize($value1).' AND '.$this->parameterize($value2);
    }

    /**
     * Builds a NULL clause.
     *
     * @param string $field
     * @param bool   $isEqual
     *
     * @return string
     */
    protected function buildNull($field, $isEqual)
    {
        $operator = $isEqual ? ' IS NULL' : ' IS NOT NULL';

        return $field.$operator;
    }

    /**
     * Builds an IN clause.
     *
     * @param string $field
     * @param array  $values
     * @param bool   $isIn
     *
     * @return string
     */
    protected function buildIn($field, array $values, $isIn)
    {
        $operator = $isIn ? ' IN ' : ' NOT IN ';

        return $field.$operator.$this->parameterizeValues($values);
    }

    /**
     * Implodes a list of WHERE clauses.
     *
     * @param array $clauses
     *
     * @return string
     */
    protected function implodeClauses(array $clauses)
    {
        $str = '';
        $op = false;
        foreach ($clauses as $clause) {
            // an 'OR' token will change the operator used
            // when concatenating the next clause
            if ($clause == 'OR') {
                $op = ' OR ';
                continue;
            }

            if ($op && $str) {
                $str .= $op;
            }

            $str .= $clause;
            $op = ' AND ';
        }

        return $str;
    }
}
