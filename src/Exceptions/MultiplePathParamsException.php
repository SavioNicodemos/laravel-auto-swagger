<?php

namespace AutoSwagger\Docs\Exceptions;

/**
 * Class MultiplePathParamsException
 *
 * Thrown when a same route have the param 'pathParams' added in Request multiple times in different methods
 * @package AutoSwagger\Docs\Exceptions
 */
class MultiplePathParamsException extends AutoSwaggerException
{
}
