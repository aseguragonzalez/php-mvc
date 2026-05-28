<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Requests;

use PhpMvc\Requests\RequestContext;
use PhpMvc\Security\Identity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RequestContextTest extends TestCase
{
    public function testGetReturnsStringValue(): void
    {
        $context = new RequestContext(['foo' => 'bar']);

        $this->assertSame('bar', $context->get('foo'));
    }

    public function testGetThrowsExceptionIfKeyNotFound(): void
    {
        $context = new RequestContext(['foo' => 'bar']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Key 'baz' not found");

        $context->get('baz');
    }

    public function testGetThrowsExceptionIfValueNotString(): void
    {
        $context = new RequestContext(['foo' => 123]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Value for key 'foo' is not a string");

        $context->get('foo');
    }

    public function testGetAsReturnsTypedValue(): void
    {
        $object = new \stdClass();
        $context = new RequestContext(['obj' => $object]);

        $result = $context->getAs('obj', \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($object, $result);
    }

    public function testGetAsThrowsExceptionIfTypeMismatch(): void
    {
        $context = new RequestContext(['obj' => new \stdClass()]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Value for key 'obj' is not of type 'DateTime'");

        $context->getAs('obj', \DateTime::class);
    }

    public function testGetAsThrowsExceptionIfKeyNotFound(): void
    {
        $context = new RequestContext(['foo' => 'bar']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Key 'baz' not found");

        $context->getAs('baz', \stdClass::class);
    }

    public function testSetAddsOrUpdatesValue(): void
    {
        $context = new RequestContext(['foo' => 'bar']);
        $context->set('foo', 'baz');
        $this->assertSame('baz', $context->get('foo'));

        $context->set('new', 'value');
        $this->assertSame('value', $context->get('new'));
    }

    public function testSetIdentityAndGetIdentity(): void
    {
        $identity = $this->createStub(Identity::class);
        $context = new RequestContext();

        $context->setIdentity($identity);

        $this->assertSame($identity, $context->getIdentity());
    }

    public function testGetIdentityThrowsExceptionIfNotSet(): void
    {
        $context = new RequestContext();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Key 'identity' not found");

        $context->getIdentity();
    }

    public function testSetIdentityTokenAndGetIdentityToken(): void
    {
        $context = new RequestContext();

        $context->setIdentityToken('token123');

        $this->assertSame('token123', $context->getIdentityToken());
    }

    public function testGetIdentityTokenThrowsExceptionIfNotSet(): void
    {
        $context = new RequestContext();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Key 'identity_token' not found");

        $context->getIdentityToken();
    }

    public function testGetIdentityTokenThrowsExceptionIfNotString(): void
    {
        $context = new RequestContext();
        $context->setIdentityToken('token123');
        $context->set('identity_token', 123);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Value for key 'identity_token' is not of type 'string'");

        $context->getIdentityToken();
    }
}
