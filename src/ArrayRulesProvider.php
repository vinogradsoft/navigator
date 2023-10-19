<?php
declare(strict_types=1);

namespace Navigator;

class ArrayRulesProvider implements RulesProvider
{

    protected array $rules;

    /**
     * @param array<string,string> $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @inheritDoc
     */
    public function getPattern(string $name): string
    {
        $name = ltrim($name, "/ \t\n\r\0\x0B");
        if ($name === '') {
            throw new RoutConfigurationException('The name cannot be empty.');
        }
        return $this->rules[$name] ?? throw new RoutConfigurationException('No route with this name was found.');
    }

}