<?php

declare(strict_types=1);

namespace Pollen\WpKernel\Components\Routing;

use Pollen\Kernel\Http\HttpKernelInterface;
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
        if (!function_exists('is_admin')) {
            throw new WpRuntimeException('is_admin function is missing.');
        }

        if (!function_exists('add_action')) {
            throw new WpRuntimeException('add_action function is missing.');
        }

        if (defined('WP_INSTALLING') && WP_INSTALLING === true) {
            return;
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

//        if (is_admin()) {
//            add_action(
//                'admin_init',
//                function () {
//                    $request = $this->httpRequest();
//                    $urlMatcher = new UrlMatcher($this->router, $request);
//                    $urlMatcher->match();
//                }
//            );
//        }

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

/* @todo * /
if (wp_using_themes() && $request->isMethod('GET')) {
    if (config('routing.remove_trailing_slash', true)) {
        $permalinks = get_option('permalink_structure');
        if (substr($permalinks, -1) == '/') {
            update_option('permalink_structure', rtrim($permalinks, '/'));
        }

        $path = Request::getBaseUrl() . Request::getPathInfo();

        if (($path != '/') && (substr($path, -1) == '/')) {
            $dispatcher = new Dispatcher($this->manager->getData());
            $match = $dispatcher->dispatch($method, rtrim($path, '/'));

            if ($match[0] === FastRoute::FOUND) {
                $redirect_url = rtrim($path, '/');
                $redirect_url .= ($qs = Request::getQueryString()) ? "?{$qs}" : '';

                $response = HttpRedirect::createPsr($redirect_url);
                $this->manager->emit($response);
                exit;
            }
        }
    }
}
/**/