<?php

namespace Winter\Storm\Database\Relations;

/**
 * Winter relation interface.
 *
 * Relations in Winter CMS must be able to set and get a simple value, which is a single value that represents the
 * relation in a simple data format - for example, a string, integer or an array. It should (generally) not be
 * returned as an object.
 *
 * Retrieving this value will allow Winter to display or use the relation in forms, JavaScript, error messages and
 * other contexts.
 *
 * When setting the value, the relation should be able to use or parse this value and convert into the appropriate
 * relation data within Laravel's architecture.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
interface Relation
{
    /**
     * Gets the simple representation of the relation.
     *
     * Retrieving this value will allow Winter to display or use the relation in forms, JavaScript, error messages
     * and other contexts.
     */
    public function getSimpleValue();

    /**
     * Creates or modifies the relation using its simple value format.
     */
    public function setSimpleValue($value): void;
}
