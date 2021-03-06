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

abstract class Statement
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * Generates the raw SQL string for the statement.
     *
     * @return string
     */
    abstract public function build();

    /**
     * Gets the values associated with this statement.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Escapes potentially reserved keywords in identifiers by wrapping them
     * with the escape character as necessary.
     *
     * @param string $word
     * @param string $escapeChar
     *
     * @return string escaped identifier
     */
    protected function escapeIdentifier($word, $escapeChar = '`')
    {
        if (is_array($word) || is_object($word) || is_numeric($word)) {
            return '';
        }

        $spaces = explode(' ', $word);
        foreach ($spaces as &$space) {
            if (strtolower($space) == 'as') {
                $space = 'AS';
            } else {
                $periods = explode('.', $space);
                foreach ($periods as &$period) {
                    // escape identifiers that are: [0-9,a-z,A-Z$_]
                    if (preg_match('/^[A-Za-z0-9_$]*$/', $period)) {
                        $period = $escapeChar.$period.$escapeChar;
                    // do not use an identifier that contains something other than:
                    //      alpha-numeric, _, $, *, /, +, -, (, )
                    } elseif (!preg_match('/^[A-Za-z0-9_$\*\/\+\-\(\)]*$/', $period)) {
                        $period = '';
                    }
                }

                $space = implode('.', $periods);
            }
        }

        return implode(' ', $spaces);
    }

    /**
     * Parameterizes a function using indexed placeholders.
     *
     * @param string $value
     *
     * @return string
     */
    protected function parameterize($value)
    {
        // numbered parameters
        $this->values[] = $value;

        return '?';
    }

    /**
     * Parameterizes a list of values.
     *
     * @param array $values
     *
     * @return string
     */
    protected function parameterizeValues(array $values)
    {
        foreach ($values as &$value) {
            $value = $this->parameterize($value);
        }

        return '('.implode(',', $values).')';
    }
}
