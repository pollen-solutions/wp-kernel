<?php

declare(strict_types=1);

namespace Pollen\WpKernel\Components\Routing;

use Pollen\Kernel\Http\HttpKernelInterface;
use Pollen\Routing\UrlMatcher;
use Pollen\Support\Proxy\ContainerProxy;
use Pollen\Routing\RouterInterface;
use Pollen\WpKernel\Exception\WpRuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as Container;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

class Routing
{
    use ContainerProxy;

    /**
     * @param RouterInterface $router
     * @param Container $container
     */
    public function __construct(RouterInterface $router, Container $container)
    {
        if (defined('WP_INSTALLING') && WP_INSTALLING === true) {
            return;
        }

        if (!function_exists('is_admin')) {
            throw new WpRuntimeException('is_admin function is missing.');
        }

        if (!function_exists('add_action')) {
            throw new WpRuntimeException('add_action function is missing.');
        }

        if (!function_exists('get_option')) {
            throw new WpRuntimeException('add_action function is missing.');
        }

        $this->setContainer($container);

        $permalinks = get_option('permalink_structure');
        if (($permalinks === '') || substr($permalinks, -1) === '/') {
            $router->setBaseSuffix('/');
        }

        if (is_multisite()) {
            $router->setBasePrefix(get_blog_details()->path);
        }

        if (!$router->hasFallback()) {
            $router->setFallback(new WpFallbackController($container));
        }

        if (is_admin()) {
            add_action(
                'admin_init',
                function () use ($container, $router) {
                    try {
                        /** @var ServerRequestInterface $request */
                        $request = $container->get(ServerRequestInterface::class);

                        (new UrlMatcher($router))->handle($request);
                    } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                        unset($e);
                    }
                }
            );
        }

        add_action(
            'template_redirect',
            function () {
                if ($container = $this->getContainer()) {
                    try {
                        /** @var HttpKernelInterface $kernel */
                        $kernel = $container->get(HttpKernelInterface::class);
                        /** @var ServerRequestInterface $request */
                        $request = $container->get(ServerRequestInterface::class);

                        $response = $kernel->handle($request);

                        $kernel->send($response);

                        $kernel->terminate($request, $response);
                    } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                        unset($e);
                    }
                }
            },
            999999
        );
    }
}