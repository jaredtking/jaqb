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

class SelectStatement extends Statement
{
    /**
     * @var array
     */
    protected $fields = ['*'];

    /**
     * Adds fields to this statement.
     * Supported input styles:
     * - addFields('field1,field2')
     * - addFields(['field','field2']).
     *
     * @param string|array $fields
     *
     * @return self
     */
    public function addFields($fields)
    {
        if (!is_array($fields)) {
            $fields = array_map(function ($f) {
                return trim($f);
            }, explode(',', $fields));
        }

        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function clearFields()
    {
        $this->fields = [];

        return $this;
    }

    /**
     * Gets the fields associated with this statement.
     * If no fields are present then defaults to '*'.
     *
     * @return array fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function build()
    {
        $fields = $this->getFields();
        foreach ($fields as &$field) {
            $field = $this->escapeIdentifier($field);
        }

        // remove empty values
        $fields = array_filter($fields);

        if (count($fields) === 0) {
            return '';
        }

        return 'SELECT '.implode(', ', $fields);
    }
}
