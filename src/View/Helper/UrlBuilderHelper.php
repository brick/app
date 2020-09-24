<?php

declare(strict_types=1);

namespace Brick\App\View\Helper;

use Brick\App\UrlBuilder;
use Brick\DI\Inject;

/**
 * This view helper allows to build URLs view parameters in views.
 */
trait UrlBuilderHelper
{
    /**
     * @var \Brick\App\UrlBuilder|null
     */
    private $builder;

    /**
     * @param \Brick\App\UrlBuilder $builder
     *
     * @return void
     */
    #[Inject]
    final public function setUrlBuilder(UrlBuilder $builder) : void
    {
        $this->builder = $builder;
    }

    /**
     * @param string $url
     * @param array  $parameters
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    final public function buildUrl(string $url, array $parameters = []) : string
    {
        if (! $this->builder) {
            throw new \RuntimeException('No URL builder has been registered');
        }

        return $this->builder->buildUrl($url, $parameters);
    }
}
