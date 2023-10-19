<?php
declare(strict_types=1);

namespace Navigator;

interface RulesProvider
{

    /**
     * @param string $name
     * @return string
     * @throws \Vinograd\UrlBuilder\Exception\RoutConfigurationException
     */
    public function getPattern(string $name): string;

}