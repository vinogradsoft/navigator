<?php
declare(strict_types=1);

namespace Navigator;

use Compass\Exception\InvalidUrlException;
use Compass\Url;
use Compass\UrlStrategy;

class UrlBuilder
{

    protected Url $url;
    protected ?Url $externalUrl;
    protected RulesProvider $rulesProvider;
    protected Adapter $adapter;
    private string $baseUrl;
    protected array $strategies;
    protected ?string $suffix = null;

    /**
     * @param string $baseUrl
     * @param RulesProvider $rulesProvider
     * @param array $strategies
     * @param Url|null $url
     * @param Url|null $externalUrl
     * @param Adapter|null $adapter
     */
    public function __construct(
        string        $baseUrl,
        RulesProvider $rulesProvider,
        array         $strategies = [],
        ?Url          $url = null,
        ?Url          $externalUrl = null,
        Adapter       $adapter = null
    )
    {
        $baseUrl = trim($baseUrl);
        $this->baseUrl = $baseUrl;
        $this->rulesProvider = $rulesProvider;
        $this->strategies = $strategies;
        $this->url = $url ?? Url::createBlank();
        $this->url->setSource($this->baseUrl);
        $this->adapter = $adapter ?? new FastRouteAdapter();
        $this->externalUrl = $externalUrl;
    }

    /**
     * @param string $name
     * @return UrlStrategy
     */
    protected function getStrategy(string $name): UrlStrategy
    {
        if (array_key_exists($name, $this->strategies)) {
            return $this->strategies[$name];
        }
        throw new BadParameterException(sprintf('The parameter %s is not registered.', $name));
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $name
     * @param array|null $placeholders
     * @param bool $absolute
     * @return string
     * @throws RoutConfigurationException
     * @throws BadParameterException
     */
    public function build(
        string     $name,
        array|null $placeholders = null,
        bool       $absolute = false
    ): string
    {
        $pathResult = null;
        $this->url->clearRelativeUrl();
        if (!$absolute && ($separator = substr($name, 0, 1)) && $separator === '/') {
            $pathResult[] = '';
        }

        if (!$placeholders) {
            $pattern = $this->rulesProvider->getPattern($name);
            $result = $this->adapter->buildStaticPath($pattern);
            $this->url->setPath(empty($pathResult) ? ltrim($result, '/') : '/' . ltrim($result, '/'));
        } else {
            $dynamic = $this->adapter->buildDynamicPath($this->rulesProvider->getPattern($name), $placeholders);
            $pathResult = $pathResult ? array_merge($pathResult, $dynamic) : $dynamic;
            $this->url->setArrayPath($pathResult);
        }

        if ($fragment = $placeholders['#'] ?? null) {
            $this->url->setFragment($fragment);
        }

        if (!empty($placeholders['?'])) {
            $query = $placeholders['?'];
            if (is_string($query)) {
                $this->url->setQuery($query);
            } elseif (is_array($query)) {
                $this->url->setArrayQuery($query);
            }
        }

        $originStrategy = null;

        if ($absolute && isset($placeholders[':idn'])) {
            $this->url->setConversionIdnToAscii($placeholders[':idn']);
        } elseif ($absolute && !isset($placeholders[':idn'])) {
            $this->url->setConversionIdnToAscii(false);
        }

        if ($strategyName = $placeholders[':strategy'] ?? null) {
            $strategy = $this->getStrategy($strategyName);
            $originStrategy = $this->url->getUpdateStrategy();
            $this->url->setUpdateStrategy($strategy);
        }

        $this->url->updateSource($absolute);

        if ($absolute) {
            $result = $this->url->getSource();
        } else {
            $result = $this->url->getRelativeUrl();
            if (empty($result)) {
                $result = '/';
            }
        }
        if (!empty($originStrategy)) {
            $this->url->setUpdateStrategy($originStrategy);
        }
        return $result;
    }

    /**
     * @param array<string,mixed> $placeholders
     * @return string
     */
    public function buildExternal(array $placeholders): string
    {
        if (empty($this->externalUrl)) {
            $this->externalUrl = Url::createBlank();
        }
        try {
            if (isset($placeholders[':src'])) {
                $this->externalUrl->setSource($placeholders[':src']);
                if ($scheme = $placeholders[':scheme'] ?? null) {
                    $this->externalUrl->setScheme($scheme);
                }
                if ($user = $placeholders[':user'] ?? null) {
                    $this->externalUrl->setUser($user);
                }
                if ($password = $placeholders[':password'] ?? null) {
                    if (!$this->externalUrl->getUser()) {
                        throw new BadParameterException('The :user placeholder was not found.');
                    }
                    $this->externalUrl->setPassword($password);
                }
            } else {
                $this->externalUrl->reset();
                $host = $placeholders[':host'] ?? throw new BadParameterException('The :host placeholder was not found.');
                $this->externalUrl->setHost($host);

                $scheme = $placeholders[':scheme'] ?? throw new BadParameterException('The :scheme placeholder was not found.');
                $this->externalUrl->setScheme($scheme);
                if ($user = $placeholders[':user'] ?? null) {
                    $this->externalUrl->setUser($user);
                }
                if ($password = $placeholders[':password'] ?? null) {
                    if (!isset($placeholders[':user'])) {
                        throw new BadParameterException('The :user placeholder was not found.');
                    }
                    $this->externalUrl->setPassword($password);
                }
            }

            if ($port = $placeholders[':port'] ?? null) {
                $this->externalUrl->setPort($port);
            }
            if ($path = $placeholders[':path'] ?? null) {
                if (is_string($path)) {
                    $this->externalUrl->setPath($path);
                } elseif (is_array($path)) {
                    $this->externalUrl->setArrayPath($path);
                }
            }
            if ($suffix = $placeholders[':suffix'] ?? null) {
                $this->externalUrl->setSuffix($suffix);
            }
            if ($query = $placeholders['?'] ?? null) {
                if (is_string($query)) {
                    $this->externalUrl->setQuery($query);
                } elseif (is_array($query)) {
                    $this->externalUrl->setArrayQuery($query);
                }
            }
            if ($fragment = $placeholders['#'] ?? null) {
                $this->externalUrl->setFragment($fragment);
            }

            $this->externalUrl->setConversionIdnToAscii($placeholders[':idn'] ?? false);
            $originStrategy = null;
            if ($strategyName = $placeholders[':strategy'] ?? null) {
                $strategy = $this->getStrategy($strategyName);
                $originStrategy = $this->externalUrl->getUpdateStrategy();
                $this->externalUrl->setUpdateStrategy($strategy);
            }
            $this->externalUrl->updateSource();
            $result = $this->externalUrl->getSource();
            if (!empty($originStrategy)) {
                $this->externalUrl->setUpdateStrategy($originStrategy);
            }
            return $result;
        } catch (InvalidUrlException $e) {
            throw  new BadParameterException($e->getMessage());
        }
    }

}