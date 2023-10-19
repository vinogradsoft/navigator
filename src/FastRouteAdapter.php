<?php
declare(strict_types=1);

namespace Navigator;

use FastRoute\RouteParser\Std;

class FastRouteAdapter implements Adapter
{

    /**
     * @inheritDoc
     */
    public function buildDynamicPath(string $pattern, array $placeholders = []): array
    {
        $routeWithoutClosingOptionals = rtrim($pattern, ']');
        $numOptionals = strlen($pattern) - strlen($routeWithoutClosingOptionals);
        $segments = preg_split('~' . Std::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        if ($numOptionals !== count($segments) - 1) {
            if (preg_match('~' . Std::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new BadParameterException('Optional segments can only occur at the end of a route');
            }
            throw new BadParameterException("Number of opening '[' and closing ']' does not match");
        }

        $emptyCounter = 0;
        $url = '';
        $errorData = [];

        foreach ($segments as $index => $segment) {
            if ($segment === '' && $index !== 0) {
                throw new BadParameterException('Empty optional part');
            }
            if ($segment === '' && $index === 0) {
                continue;
            }
            if ($index === 0) {
                $data = $this->fillPlaceholders(is_int(strpos($segment, '{')), $segment, $placeholders);
            } else {
                $data = $this->fillPlaceholders(false, $segment, $placeholders);
            }

            if ($data && $emptyCounter === 0) {

                if (str_starts_with($segment, '/')) {
                    $url .= '/' . $data;
                } else {
                    $url .= $data;
                }

            } elseif ($data && $emptyCounter > 0) {
                $errorData[] = $data;
            } else {
                $emptyCounter++;
            }

        }
        if (!empty($errorData)) {
            throw new BadParameterException('Not enough parameters.');
        }
        return explode('/', ltrim($url, '/'));
    }

    /**
     * @param bool $require
     * @param string $segment
     * @param array $placeholders
     * @return string|null
     */
    private function fillPlaceholders(bool $require, string $segment, array $placeholders): ?string
    {
        if (!preg_match_all(
            '~' . Std::VARIABLE_REGEX . '~x', $segment, $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            if (!str_starts_with($segment, '/')) {
                if (!isset($placeholders[$segment])) {
                    return null;
                }
                if (!is_bool($placeholders[$segment])) {
                    throw new BadParameterException('Invalid placeholder type.');
                }
                return $placeholders[$segment] ? ltrim($segment, '/') : null;
            }
            return ltrim($segment, '/');
        }

        $matchesCount = count($matches);
        $counter = $matchesCount;

        foreach ($matches as $set) {
            $item = $set[1][0];
            $data = $placeholders[$item] ?? null;
            if (is_string($data) || is_int($data)) {

                if (
                    !preg_match(
                        '~^' . (isset($set[2]) ? trim($set[2][0]) : Std::DEFAULT_DISPATCH_REGEX) . '$~isu',
                        (string)$data,
                        $m,
                        PREG_OFFSET_CAPTURE
                    )
                ) {
                    throw new BadParameterException('Invalid placeholder type.');
                }

                $segment = str_replace($set[0][0], (string)$data, $segment);

            } elseif ($require && $data === null) {
                throw new BadParameterException('Not enough parameters.');
            } else {
                $counter--;
            }
        }
        if ($matchesCount !== $counter && $counter !== 0) {
            throw new BadParameterException('Not enough parameters.');
        }
        if (($matchesCount - $counter) === 0) {
            return ltrim($segment, '/');
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function buildStaticPath(string $pattern): string
    {
        $bracePos = strpos($pattern, '{');
        $squareBracketPos = strpos($pattern, '[');

        if ($bracePos === false && $squareBracketPos === false) {
            return ltrim($pattern, '/');
        } elseif ($squareBracketPos !== false && ($squareBracketPos < $bracePos || is_int($squareBracketPos) && is_bool($bracePos))) {
            $result = ltrim(substr($pattern, 0, $squareBracketPos), '/');
            if (empty($result)) {
                return '';
            }
            return $result;
        }

        throw new BadParameterException('Bad parameters.');
    }

}