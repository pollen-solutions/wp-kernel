<?php

declare(strict_types=1);

namespace Pollen\WpKernel;

use Pollen\Database\DatabaseManagerInterface;
use Pollen\Support\Proxy\ContainerProxy;
use Pollen\WpDatabase\WpDatabase as BaseWpDatabase;
use Pollen\WpDatabase\WpDatabaseInterface;
use Psr\Container\ContainerInterface as Container;

class WpDatabase
{
    use ContainerProxy;

    /**
     * Database Manager instance.
     * @var DatabaseManagerInterface $db
     */
    protected DatabaseManagerInterface $db;

    /**
     * @param DatabaseManagerInterface $db
     * @param Container $container
     */
    public function __construct(DatabaseManagerInterface $db, Container $container)
    {
        $this->db = $db;
        $this->setContainer($container);

        if (class_exists(BaseWpDatabase::class)) {
            $this->containerAdd(WpDatabaseInterface::class, new BaseWpDatabase([], $this->getContainer()), true);
        }
    }
}