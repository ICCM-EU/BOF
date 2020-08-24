<?php

namespace ICCM\BOF;

use ErrorException;
use \PDO;
use RuntimeException;
use Twig\Error\RuntimeError;

class Cookies
{
    public function set(string $name , string $value = "" , int $expires = 0, string $path = "" , string $domain = "" , bool $secure = FALSE , bool $httponly = FALSE  ) : bool
    {
        return setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }
}
