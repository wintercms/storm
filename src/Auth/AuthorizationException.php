<?php namespace Winter\Storm\Auth;

use Config;
use Winter\Storm\Exception\ApplicationException;
use Exception;
use Lang;

/**
 * Used when user authorization fails. Implements a softer error message.
 *
 * @author Luke Towers
 */
class AuthorizationException extends ApplicationException
{

}
