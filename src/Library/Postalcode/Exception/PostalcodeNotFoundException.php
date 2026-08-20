<?php
namespace ControleOnline\Library\Postalcode\Exception;

/**
 * CEP format is valid but no address was found by any provider.
 */
final class PostalcodeNotFoundException extends \Exception implements ExceptionInterface
{
}
