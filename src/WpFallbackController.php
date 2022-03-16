<?php

declare(strict_types=1);

namespace Pollen\WpKernel;

use League\Route\Http\Exception\HttpExceptionInterface as BaseHttpExceptionInterface;
use League\Route\Http\Exception\NotFoundException as BaseNotFoundException;
use Pollen\Http\Response;
use Pollen\Http\ResponseInterface;
use Pollen\Kernel\ApplicationInterface;
use Pollen\Support\ProxyResolver;
use Pollen\Support\Str;
use Pollen\Routing\BaseViewController;
use Pollen\Routing\Exception\HttpExceptionInterface;
use Pollen\Routing\Exception\NotFoundException;
use Pollen\View\ViewManager;
use Pollen\View\ViewManagerInterface;
use Pollen\WpKernel\Exception\WpRuntimeException;
use RuntimeException;

class WpFallbackController extends BaseViewController
{
    /**
     * Map list of conditional tags for Worpress template.
     * @see ./wp-includes/template-loader.php
     * @var array<string, string>
     */
    protected array $wpTemplateTags = [
        'is_embed'             => 'get_embed_template',
        'is_404'               => 'get_404_template',
        'is_search'            => 'get_search_template',
        'is_front_page'        => 'get_front_page_template',
        'is_home'              => 'get_home_template',
        'is_privacy_policy'    => 'get_privacy_policy_template',
        'is_post_type_archive' => 'get_post_type_archive_template',
        'is_tax'               => 'get_taxonomy_template',
        'is_attachment'        => 'get_attachment_template',
        'is_single'            => 'get_single_template',
        'is_page'              => 'get_page_template',
        'is_singular'          => 'get_singular_template',
        'is_category'          => 'get_category_template',
        'is_tag'               => 'get_tag_template',
        'is_author'            => 'get_author_template',
        'is_date'              => 'get_date_template',
        'is_archive'           => 'get_archive_template',
    ];

    public function __invoke(): ResponseInterface
    {
        $args = func_get_args();

        if (isset($args[0]) && $args[0] instanceof BaseHttpExceptionInterface) {
            return $this->exceptionRender(...$args);
        }

        return $this->dispatch(...$args);
    }

    /**
     * After HTTP request dispatch.
     *
     * @param ...$args
     *
     * @return ResponseInterface|null
     */
    public function afterDispatch(...$args): ?ResponseInterface
    {
        return null;
    }

    /**
     * Before HTTP request dispatch.
     *
     * @param ...$args
     *
     * @return ResponseInterface|null
     */
    public function beforeDispatch(...$args): ?ResponseInterface
    {
        return null;
    }

    /**
     * Dispatch HTTP request.
     *
     * @return ResponseInterface
     */
    public function dispatch(): ResponseInterface
    {
        $args = func_get_args();

        $response = $this->beforeDispatch(...$args);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        $hasTag = false;
        foreach (array_keys($this->wpTemplateTags) as $tag) {
            $tagged = $tag();

            if (!$hasTag && $tagged) {
                $hasTag = true;
            }

            if (
                $tagged
                && ($template = $this->handleTagTemplate($tag, ...$args))
                && ($response = $this->handleTemplateResponse($template))
            ) {
                return $response;
            }
        }

        if ($hasTag && ($response = $this->handleTemplateResponse())) {
            return $reponse;
        }

        $response = $this->afterDispatch(...$args);
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (
            !($template = $this->handleTagTemplate('is_404', ...$args))
            || !($response = $this->handleTemplateResponse($template))
        ) {
            $response = $this->response('Template unavailable', 404);
        }

        return $response;
    }

    /**
     * Exceptions render.
     *
     * @param BaseHttpExceptionInterface|HttpExceptionInterface $e
     * @param ...$args
     *
     * @return ResponseInterface
     */
    public function exceptionRender(BaseHttpExceptionInterface $e, ...$args): ResponseInterface
    {
        if ($e instanceof NotFoundException || $e instanceof BaseNotFoundException) {
            return $this->dispatch(...$args);
        }

        ob_start();
        _default_wp_die_handler(
            $e->getMessage(),
            $e instanceof HttpExceptionInterface ? $e->getTitle() : get_class($e),
            [
                'exit' => false,
                'code' => $e->getStatusCode(),
            ]
        );
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * Handle HTTP request for conditionnal tag of Wordpress Template.
     *
     * @param string $tag
     * @param ...$args
     *
     * @return string|null
     */
    public function handleTagTemplate(string $tag, ...$args): ?string
    {
        if (!function_exists('apply_filters')) {
            throw new WpRuntimeException('apply_filters function is missing.');
        }

        $method = Str::camel($tag);

        if (method_exists($this, $method)) {
            return $this->{$method}(...$args);
        }

        if ($template = call_user_func($this->wpTemplateTags[$tag])) {
            if ('attachment' === $tag) {
                remove_filter('the_content', 'prepend_attachment');
            }
        }

       return $template ? $template : null;
    }

    /**
     * Handle HTTP request for Wordpress template response.
     *
     * @param string|null $template
     *
     * @return ResponseInterface|null
     */
    public function handleTemplateResponse(?string $template = null): ?ResponseInterface
    {
        if ($template === null) {
            $template = get_index_template();
        }

        if ($template = apply_filters('template_include', $template)) {
            /** @var ApplicationInterface $app */
            $app = $this->getContainer()->get(ApplicationInterface::class);

            $template = preg_replace(
                '#' . preg_quote($app->getPublicPath(), DIRECTORY_SEPARATOR) . '#',
                '',
                $template
            );

            try {
                $viewManager = ViewManager::getInstance();
            } catch (RuntimeException $e) {
                $viewManager = ProxyResolver::getInstance(
                    ViewManagerInterface::class,
                    ViewManager::class,
                    method_exists($this, 'getContainer') ? $this->getContainer() : null
                );
            }
            $view = $viewManager->createView('plates')
                ->setDirectory($app->getPublicPath())
                ->setFileExtension('php');

            return $this->response($view->render(
                pathinfo($template, PATHINFO_DIRNAME) . '/' . pathinfo($template, PATHINFO_FILENAME))
            );
        }
        return null;
    }
}