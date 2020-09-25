<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\ObjectPacker\NullPacker;
use Brick\App\ObjectPacker\ObjectPacker;
use RuntimeException;

class UrlBuilder
{
    private ObjectPacker $objectPacker;

    /**
     * Class constructor.
     */
    public function __construct(ObjectPacker|null $objectPacker = null)
    {
        $this->objectPacker = $objectPacker ?? new NullPacker();
    }

    /**
     * Builds a URL with the given parameters.
     *
     * If the URL already contains query parameters, they will be merged, the parameters passed to the method
     * having precedence over the original query parameters.
     *
     * If any of the method parameters is an object, it will be replaced by its packed representation,
     * as provided by the ObjectPacker implementation.
     *
     * @throws RuntimeException If an unsupported object is given as a parameter.
     */
    public function buildUrl(string $url, array $parameters = []) : string
    {
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                if ($value === null) {
                    unset($parameters[$key]);
                }

                if (is_object($value)) {
                    $packedObject = $this->objectPacker->pack($value);

                    if ($packedObject === null) {
                        throw new RuntimeException('Cannot pack object ' . get_class($value));
                    }

                    $parameters[$key] = $packedObject->getData();
                }
            }
        }

        $pos = strpos($url, '?');
        if ($pos !== false) {
            parse_str(substr($url, $pos + 1), $query);
            $parameters += $query;

            $url = substr($url, 0, $pos);
        }

        if ($parameters) {
            return $url . '?' . http_build_query($parameters);
        }

        return $url;
    }
}
