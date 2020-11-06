<?php

declare(strict_types=1);

namespace ElasticApmTests\UnitTests;

use Elastic\Apm\ElasticApm;
use Elastic\Apm\Impl\GlobalTracerHolder;
use Elastic\Apm\TransactionInterface;

/**
 * @see \ElasticApmTests\PublicApiTest::testExamplePublicApiElasticApm - test that depends on this class
 */
final class ExamplePublicApiElasticApm
{
    /** @var string */
    public const TRANSACTION_NAME = 'Checkout transaction';

    /** @var string */
    public const TRANSACTION_TYPE = 'shopping';

    /** @var string */
    public const LOST_LABEL = 'lost-label';

    /** @var array<string, bool> */
    private $isDataInCache = [];

    public function processCheckoutRequest(int $shopNumber): void
    {
        $shopId = 'Shop #' . $shopNumber;
        if ($shopNumber == 1) {
            $tx = ElasticApm::beginCurrentTransaction(self::TRANSACTION_NAME, self::TRANSACTION_TYPE);
            $this->processCheckoutRequestImpl($shopId, $tx);
            $tx->end();
        } else {
            ElasticApm::captureCurrentTransaction(
                self::TRANSACTION_NAME,
                self::TRANSACTION_TYPE,
                function (TransactionInterface $tx) use ($shopId) {
                    $this->processCheckoutRequestImpl($shopId, $tx);
                }
            );
        }

        // Lost label because there is no current transaction
        ElasticApm::getCurrentTransaction()->context()->setLabel(self::LOST_LABEL, null);
    }

    private function processCheckoutRequestImpl(string $shopId, TransactionInterface $tx): void
    {
        $tx->context()->setLabel('shop-id', $shopId);

        $this->getShoppingCartItems($shopId);
        $this->chargePayment($shopId);

        // Lost label because there is no current span
        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel(self::LOST_LABEL, 123.456);
    }

    private function getShoppingCartItems(string $shopId): void
    {
        ElasticApm::getCurrentTransaction();

        $span = ElasticApm::getCurrentTransaction()->beginCurrentSpan('Get shopping cart items', 'business');

        $this->fetchData($shopId, 'shopping-cart-items');

        $span->end();
    }

    private function fetchData(string $shopId, string $dataId): void
    {
        $isDataInCache = $this->checkIfDataInCache($dataId);
        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('is-data-in-cache', $isDataInCache);

        if ($isDataInCache) {
            $this->redisFetch($shopId, $dataId);
        } else {
            $this->dbSelect($shopId, $dataId);
        }

        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('shop-id', $shopId);
    }

    private function redisFetch(string $shopId, string $dataId): void
    {
        $span = ElasticApm::getCurrentTransaction()->beginCurrentSpan('Fetch from Redis', 'db', 'redis', 'query');

        // ...

        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('redis-response-id', 'abc');
        $this->processData($shopId, $dataId);

        $span->end();
    }

    private function processData(string $shopId, string $dataId): void
    {
        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('data-id', $dataId);
        ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('shop-id', $shopId);
    }

    private function dbSelect(string $shopId, string $dataId): void
    {
        ElasticApm::getCurrentTransaction()->captureCurrentSpan(
            'DB query',
            'db',
            function () use ($shopId, $dataId): void {
                // ...

                ElasticApm::getCurrentTransaction()->getCurrentSpan()->context()->setLabel('db-row-count', 123);
                $this->processData($shopId, $dataId);

                $this->addDataToCache($dataId);
            },
            'mysql',
            'query'
        );
    }

    private function chargePayment(string $shopId): void
    {
        $span = ElasticApm::getCurrentTransaction()->beginCurrentSpan('Charge payment', 'business');

        $this->fetchData($shopId, 'payment-method-details');

        $span->end();
    }

    private function checkIfDataInCache(string $dataId): bool
    {
        return $this->isDataInCache[$dataId] ?? false;
    }

    private function addDataToCache(string $dataId): void
    {
        $this->isDataInCache[$dataId] = true;
    }
}
