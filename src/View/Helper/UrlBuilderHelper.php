<?php

namespace Brick\App\View\Helper;

use Brick\UrlBuilder\UrlBuilder;

/**
 * This view helper allows to build URLs view parameters in views.
 */
trait UrlBuilderHelper
{
    /**
     * @var \Brick\UrlBuilder\UrlBuilder|null
     */
    private $builder;

    /**
     * @Brick\Di\Annotation\Inject
     *
     * @param \Brick\UrlBuilder\UrlBuilder $builder
     *
     * @return void
     */
    final public function setUrlBuilder(UrlBuilder $builder)
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
    final public function buildUrl(string $url, array $parameters = [])
    {
        if (! $this->builder) {
            throw new \RuntimeException('No URL builder has been registered');
        }

        return $this->builder->buildUrl($url, $parameters);
    }
}
