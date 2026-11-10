<?php

namespace Omnipulse\BingWebmaster;

use Omnipulse\Config;
use Omnipulse\Exception\InvalidConfigException;
use Omnipulse\ProviderFactory;
use Omnipulse\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Bing Webmaster Tools, from its options:
 *
 *   api_key  the API key (Bing Webmaster Tools, Settings, API access)
 *   site     the site as verified there ("https://example.org/")
 *   url      https://ssl.bing.com/webmaster/api.svc/json
 */
final class BingWebmasterFactory extends ProviderFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnipulse.factory_name' => 'bing-webmaster',
            'omnipulse.factory_title' => 'Bing Webmaster Tools',
            'omnipulse.required_options' => ['api_key', 'site'],
            'url' => 'https://ssl.bing.com/webmaster/api.svc/json',
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new BingWebmasterProvider(
            $this->http ?? throw new InvalidConfigException('The "bing-webmaster" provider needs an HTTP client: give one to BingWebmasterFactory.'),
            (string) $c->string('api_key'),
            (string) $c->string('site'),
            rtrim($c->string('url') ?? 'https://ssl.bing.com/webmaster/api.svc/json', '/'),
            $c->backoff(),
        );
    }
}
