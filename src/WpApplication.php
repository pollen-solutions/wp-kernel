<?php

declare(strict_types=1);

namespace Pollen\WpKernel;

use Pollen\Console\ConsoleInterface;
use Pollen\Event\EventDispatcherInterface;
use Pollen\Kernel\Application;
use Pollen\Routing\RouterInterface;
use Pollen\WpEnv\WpEnv;
use Pollen\WpKernel\Components\Console\Console;
use Pollen\WpKernel\Components\Routing\Routing;
use Pollen\WpKernel\Exception\WpRuntimeException;

class WpApplication extends Application implements WpApplicationInterface
{
    /**
     * @return void
     */
    protected function preBuildEvents(): void
    {
        if (!defined('ABSPATH')) {
            throw new WpRuntimeException('ABSPATH Constant is missing.');
        }

        if ($console = $this->resolve(ConsoleInterface::class)) {
            new Console($console, $this);
        }

        /** @var EventDispatcherInterface $eventDispatcher */
        if ($eventDispatcher = $this->resolve(EventDispatcherInterface::class)) {
            $eventDispatcher->subscribeTo('app.boot', function (/*BootEvent $event*/) use ($eventDispatcher) {

                if ($router = $this->resolve(RouterInterface::class)) {
                    new Routing($router, $this);
                }
            });
        }
    }

    /**
     * Load environment variables.
     *
     * @return void
     */
    protected function envLoad(): void
    {
        $loader = (new WpEnv($this->getBasePath()))->load();

        $loader->required(['DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_HOST']);
    }
}