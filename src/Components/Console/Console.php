<?php

declare(strict_types=1);

namespace Pollen\WpKernel\Components\Console;

use Pollen\Console\ConsoleInterface;
use Pollen\Kernel\ApplicationInterface;
use Pollen\Support\Env;
use Pollen\Support\Proxy\ContainerProxy;

class Console
{
    use ContainerProxy;

    /**
     * @param ConsoleInterface $console
     * @param ApplicationInterface $app
     */
    public function __construct(ConsoleInterface $console, ApplicationInterface $app)
    {
        $this->setContainer($app);

        if ($app->runningInConsole()) {
            global $argv;

            // Default header
            $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
            $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SERVER['REQUEST_METHOD']  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $_SERVER['REMOTE_ADDR']     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            // @see php -i | grep php.ini
            $_SERVER['TZ'] = $_SERVER['TZ'] ?? (ini_get('date.timezone') ?: 'UTC');

            // Entêtes associées à l'url
//            if ($url = preg_grep('/^\-\-url\=(.*)/', $argv)) {
//                foreach (array_keys($url) as $k) {
//                    unset($argv[$k]);
//                }
//
//                $url = current($url);
//                $url = preg_replace('/^\-\-url\=/', '', $url);
//            }

            $url = /*$url ?:*/ Env::get('APP_URL', 'http://localhost');
            $parts = parse_url($url);

            if (!empty($parts['host'])) {
                if (!empty($parts['scheme']) && 'https' === strtolower($parts['scheme'])) {
                    $_SERVER['HTTPS'] = 'on';
                }

                $_SERVER['HTTP_HOST'] = $parts['host'];
                if (isset($parts['port'])) {
                    $_SERVER['HTTP_HOST'] .= ':' . $parts['port'];
                }

                $_SERVER['SERVER_NAME'] = $parts['host'];
            }

            $_SERVER['REQUEST_URI']  = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
            $_SERVER['SERVER_PORT']  = $parts['port'] ?? '80';
            $_SERVER['QUERY_STRING'] = $parts['query'] ?? '';
        }
    }
}