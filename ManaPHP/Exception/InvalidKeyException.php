<?php

namespace ManaPHP\Exception;

/**
 * Class InvalidKeyException
 *
 * @package ManaPHP\Exception
 *
 *This runtime exception is thrown to indicate that a method parameter which was expected to be
 * an item name of a composite data or a row index of a tabular data is not valid.
 */
class InvalidKeyException extends InvalidArgumentException
{

}