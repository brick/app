<?php

declare(strict_types=1);

namespace Brick\App\View\Helper;

use Brick\App\UrlBuilder;
use Brick\DI\Inject;
use RuntimeException;

/**
 * This view helper allows to build URLs view parameters in views.
 */
trait UrlBuilderHelper
{
    private UrlBuilder|null $builder = null;

    #[Inject]
    final public function setUrlBuilder(UrlBuilder $builder) : void
    {
        $this->builder = $builder;
    }

    /**
     * @throws RuntimeException
     */
    final public function buildUrl(string $url, array $parameters = []) : string
    {
        if (! $this->builder) {
            throw new RuntimeException('No URL builder has been registered');
        }

        return $this->builder->buildUrl($url, $parameters);
    }
}
