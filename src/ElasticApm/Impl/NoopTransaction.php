<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace Elastic\Apm\Impl;

use Closure;
use Elastic\Apm\Impl\Util\NoopObjectTrait;
use Elastic\Apm\SpanInterface;
use Elastic\Apm\TransactionContextInterface;
use Elastic\Apm\TransactionInterface;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class NoopTransaction extends NoopExecutionSegment implements TransactionInterface
{
    use NoopObjectTrait;

    /** @inheritDoc */
    public function getParentId(): ?string
    {
        return null;
    }

    /** @inheritDoc */
    public function beginCurrentSpan(
        string $name,
        string $type,
        ?string $subtype = null,
        ?string $action = null,
        ?float $timestamp = null
    ): SpanInterface {
        return NoopSpan::singletonInstance();
    }

    /** @inheritDoc */
    public function captureCurrentSpan(
        string $name,
        string $type,
        Closure $callback,
        ?string $subtype = null,
        ?string $action = null,
        ?float $timestamp = null
    ) {
        return $callback(NoopSpan::singletonInstance());
    }

    /** @inheritDoc */
    public function getCurrentSpan(): SpanInterface
    {
        return NoopSpan::singletonInstance();
    }

    /** @inheritDoc */
    public function setResult(?string $result): void
    {
    }

    /** @inheritDoc */
    public function getResult(): ?string
    {
        return null;
    }

    /** @inheritDoc */
    public function isSampled(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function context(): TransactionContextInterface
    {
        return NoopTransactionContext::singletonInstance();
    }
}
