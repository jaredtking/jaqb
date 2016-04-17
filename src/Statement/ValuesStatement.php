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

class ValuesStatement extends Statement
{
    /**
     * Adds values to the statement.
     *
     * @return self
     */
    public function addValues(array $values)
    {
        $this->values = array_replace($this->values, $values);

        return $this;
    }

    public function build()
    {
        $keys = array_keys($this->values);
        foreach ($keys as &$key) {
            $key = $this->escapeIdentifier($key);
        }

        // remove empty values
        $keys = array_filter($keys);

        if (count($keys) == 0) {
            return '';
        }

        // generates (`col1`,`col2`,`col3`) VALUES (?,?,?)
        return '('.implode(',', $keys).') VALUES ('.
            implode(',', array_fill(0, count($keys), '?')).')';
    }
}
