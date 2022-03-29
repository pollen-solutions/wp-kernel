<?php

declare(strict_types=1);

namespace Pollen\WpKernel;

use Pollen\Faker\FakerInterface;
use Pollen\Support\Proxy\ContainerProxy;
use Psr\Container\ContainerInterface as Container;

class WpFaker
{
    use ContainerProxy;

    /**
     * Faker instance.
     * @var FakerInterface
     */
    protected FakerInterface $faker;

    /**
     * @param FakerInterface $faker
     * @param Container $container
     */
    public function __construct(FakerInterface $faker, Container $container)
    {
        $this->faker = $faker;
        $this->setContainer($container);

        global $locale;

        if ($locale) {
            $this->faker->setLocale($locale);
        }
    }
}