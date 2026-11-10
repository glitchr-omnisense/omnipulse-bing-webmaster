<?php

namespace Omnipulse\BingWebmaster\Tests;

use Omnipulse\BingWebmaster\BingWebmasterFactory;
use Omnipulse\BingWebmaster\BingWebmasterProvider;
use Omnipulse\Exception\OmnipulseException;
use Omnipulse\Exception\UsageLimitReachedException;
use Omnipulse\Model\Dimension;
use Omnipulse\Model\ReachQuery;
use Omnipulse\Registry;
use Omnipulse\Testing\RecordedHttpClient;
use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\UsagePolicy;
use PHPUnit\Framework\TestCase;

/**
 * BingWebmasterProvider's usage policy (policy.json) through omnipulse's registry:
 * as declared, a site's limit counted with the call over it never made,
 * a 429 and its Retry-After holding every next call, a tightening accepted
 * and a looser one refused. No network: recorded answers only.
 */
final class PolicyTest extends TestCase
{
    private const OPTIONS = ['api_key' => 'not-a-real-key', 'site' => 'https://atelier.example.org/'];

    public function testThePolicyIsTheOneDeclared(): void
    {
        $policy = (new Registry([new BingWebmasterFactory(RecordedHttpClient::routes([]))], ['p' => ['factory' => 'bing-webmaster', 'options' => self::OPTIONS]]))->policy('p');

        self::assertFileExists(BingWebmasterProvider::POLICY);
        self::assertEquals(UsagePolicy::fromFile(BingWebmasterProvider::POLICY), $policy);
        self::assertSame('bing-webmaster', $policy->provider);
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
        self::assertNull($policy->rateLimits, 'no figure published: unknown, never invented');
        self::assertSame(true, $policy->requirements->key);
    }

    public function testASitesLimitIsCountedAndTheCallOverItIsNeverMade(): void
    {
        $http = RecordedHttpClient::routes([]);
        $registry = new Registry([new BingWebmasterFactory($http)], ['p' => ['factory' => 'bing-webmaster', 'options' => self::OPTIONS, 'policy' => ['rateLimits' => [['max' => 1, 'window' => 'day', 'operations' => ['searchPerformance']]]]]]);

        try {
            $registry->search('p')->searchPerformance(ReachQuery::lastDays(28, [Dimension::Query]));
        } catch (UsageLimitReachedException $e) {
            throw $e;
        } catch (OmnipulseException) {
            // Nothing recorded: the provider's own answer to that, counted all the same.
        }
        $made = \count($http->requests);
        self::assertGreaterThan(0, $made);
        try {
            $registry->search('p')->searchPerformance(ReachQuery::lastDays(28, [Dimension::Query]));
            self::fail('The call over the site\'s limit was made.');
        } catch (UsageLimitReachedException $e) {
            self::assertInstanceOf(OmnipulseException::class, $e, 'the family\'s catch holds it');
            self::assertSame('1/day/application/searchPerformance', $e->limit?->id());
        }
        self::assertCount($made, $http->requests, 'the refused call reached nothing');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $http = new RecordedHttpClient(static fn (): array => [429, '{"message":"too many requests"}', ['retry-after' => ['120']]]);
        $registry = new Registry([new BingWebmasterFactory($http)], ['p' => ['factory' => 'bing-webmaster', 'options' => self::OPTIONS]]);

        try {
            $registry->search('p')->searchPerformance(ReachQuery::lastDays(28, [Dimension::Query]));
            self::fail('A 429 was read as an answer.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(120, $e->retryAfter);
            self::assertSame('bing-webmaster answered 429 Too Many Requests: try again in 120 s.', $e->getMessage());
        }
        $made = \count($http->requests);
        try {
            $registry->search('p')->searchPerformance(ReachQuery::lastDays(28, [Dimension::Query]));
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(119, $e->retryAfter);
        }
        self::assertCount($made, $http->requests, 'the wait held the next request back');
    }

    public function testATighteningIsAccepted(): void
    {
        $registry = new Registry([new BingWebmasterFactory(RecordedHttpClient::routes([]))], ['p' => ['factory' => 'bing-webmaster', 'options' => self::OPTIONS, 'policy' => ['rateLimits' => [['max' => 10, 'window' => 'minute']], 'rights' => ['caching' => ['maxTtl' => 0]]]]]);

        $policy = $registry->policy('p');
        self::assertContains('10/minute/application/*', array_map(static fn ($l) => $l->id(), $policy->rateLimits ?? []));
        self::assertSame(0, $policy->rights->caching->maxTtl);
    }

    public function testASiteCannotLoosenWhatTheTermsLeaveUnknown(): void
    {
        $registry = new Registry([new BingWebmasterFactory(RecordedHttpClient::routes([]))], ['p' => ['factory' => 'bing-webmaster', 'options' => self::OPTIONS, 'policy' => ['rights' => ['commercial' => 'allowed']]]]);

        $this->expectException(InvalidPolicyException::class);
        $registry->search('p');
    }
}
