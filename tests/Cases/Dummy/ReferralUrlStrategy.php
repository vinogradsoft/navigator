<?php

namespace Test\Cases\Dummy;

use Compass\DefaultUrlStrategy;
use Compass\Url;

class ReferralUrlStrategy extends DefaultUrlStrategy
{

    /**
     * @inheritDoc
     */
    public function updateQuery(array $items): string
    {
        $items['refid'] = 222;
        return http_build_query($items, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @inheritDoc
     */
    public function forceUnlockMethod(
        bool    &$schemeState,
        int     &$authoritySate,
        int     &$relativeUrlState,
        array   $items,
        array   $pathItems,
        array   $queryItems,
        bool    $updateAbsoluteUrl,
        ?string $suffix = null
    ): void
    {
        $relativeUrlState &= ~Url::QUERY_STATE;
    }

}