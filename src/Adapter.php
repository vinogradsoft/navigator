<?php
declare(strict_types=1);

namespace Navigator;

interface Adapter
{

    /**
     * @param string $pattern
     * @return string
     * @throws BadParameterException
     */
    public function buildStaticPath(string $pattern): string;

    /**
     * @param string $pattern
     * @param array $placeholders
     * @return array
     * @throws BadParameterException
     */
    public function buildDynamicPath(string $pattern, array $placeholders = []): array;

}