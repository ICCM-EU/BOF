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
        if (PHP_VERSION_ID < 70300) {
            return setcookie($name, $value, $expires,
                $path.'; SameSite=Strict', $domain, $secure, $httponly);
        }
        return setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Strict'
        ]);
    }
}
