<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Views\ViewValueResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ViewValueResolverTest extends TestCase
{
    private ViewValueResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ViewValueResolver();
    }

    public function testResolvesNullModelAsNull(): void
    {
        $this->assertNull($this->resolver->resolve(null, 'foo'));
    }

    public function testResolvesEmptyPathAsModel(): void
    {
        $model = (object) ['name' => 'Test'];
        $this->assertSame($model, $this->resolver->resolve($model, ''));
    }

    public function testResolvesObjectProperty(): void
    {
        $model = (object) ['name' => 'Peter', 'age' => 25];
        $this->assertSame('Peter', $this->resolver->resolve($model, 'name'));
        $this->assertSame(25, $this->resolver->resolve($model, 'age'));
    }

    public function testResolvesNestedObjectPath(): void
    {
        $address = (object) ['street' => 'Elm St', 'city' => 'Springwood'];
        $model = (object) ['name' => 'Fred', 'address' => $address];
        $this->assertSame('Elm St', $this->resolver->resolve($model, 'address->street'));
        $this->assertSame('Springwood', $this->resolver->resolve($model, 'address->city'));
    }

    public function testResolvesArrayAtRoot(): void
    {
        $model = ['name' => 'Alice', 'score' => 100];
        $this->assertSame('Alice', $this->resolver->resolve($model, 'name'));
        $this->assertSame(100, $this->resolver->resolve($model, 'score'));
    }

    public function testResolvesNestedPathWithArray(): void
    {
        $model = ['customer' => (object) ['address' => (object) ['city' => 'NYC']]];
        $this->assertSame('NYC', $this->resolver->resolve($model, 'customer->address->city'));
    }

    public function testResolvesNumericIndex(): void
    {
        $model = (object) ['items' => ['a', 'b', 'c']];
        $this->assertSame('a', $this->resolver->resolve($model, 'items[0]'));
        $this->assertSame('b', $this->resolver->resolve($model, 'items[1]'));
    }

    public function testResolvesAssociativeKeyWithDoubleQuotes(): void
    {
        $model = (object) ['data' => ['my-key' => 'value']];
        $this->assertSame('value', $this->resolver->resolve($model, 'data["my-key"]'));
    }

    public function testResolvesAssociativeKeyWithSingleQuotes(): void
    {
        $model = (object) ['data' => ['my-key' => 'value']];
        $this->assertSame('value', $this->resolver->resolve($model, "data['my-key']"));
    }

    public function testResolvesIndexThenProperty(): void
    {
        $model = (object) ['users' => [(object) ['name' => 'Alice'], (object) ['name' => 'Bob']]];
        $this->assertSame('Alice', $this->resolver->resolve($model, 'users[0]->name'));
        $this->assertSame('Bob', $this->resolver->resolve($model, 'users[1]->name'));
    }

    public function testResolvesMethodCall(): void
    {
        $model = new class {
            public function isActive(): bool
            {
                return true;
            }
        };
        $this->assertTrue($this->resolver->resolve($model, 'isActive()'));
    }

    public function testResolvesMissingPropertyAsNull(): void
    {
        $model = (object) ['name' => 'Test'];
        $this->assertNull($this->resolver->resolve($model, 'missing'));
        $this->assertNull($this->resolver->resolve($model, 'name->nested'));
    }

    public function testIsTruthyWithProperty(): void
    {
        $model = (object) ['flag' => true, 'empty' => false];
        $this->assertTrue($this->resolver->isTruthy($model, 'flag'));
        $this->assertFalse($this->resolver->isTruthy($model, 'empty'));
    }

    public function testIsTruthyWithNegation(): void
    {
        $model = (object) ['flag' => true];
        $this->assertFalse($this->resolver->isTruthy($model, '!flag'));
        $this->assertTrue($this->resolver->isTruthy($model, '!missing'));
    }

    public function testIsTruthyWithMethodCall(): void
    {
        $model = new class {
            public function isActive(): bool
            {
                return true;
            }
        };
        $this->assertTrue($this->resolver->isTruthy($model, 'isActive()'));
    }

    public function testPathExistsWhenPropertyPresent(): void
    {
        $model = (object) ['name' => 'Peter', 'email' => null];
        $this->assertTrue($this->resolver->pathExists($model, 'name'));
        $this->assertTrue($this->resolver->pathExists($model, 'email'));
    }

    public function testPathExistsWhenPropertyMissing(): void
    {
        $model = (object) ['name' => 'Peter'];
        $this->assertFalse($this->resolver->pathExists($model, 'email'));
        $this->assertFalse($this->resolver->pathExists($model, 'layout'));
    }

    public function testPathExistsWithNestedPath(): void
    {
        $model = (object) ['layout' => (object) ['app' => 'Resbooking']];
        $this->assertTrue($this->resolver->pathExists($model, 'layout->app'));
        $this->assertFalse($this->resolver->pathExists($model, 'layout->missing'));
        $this->assertFalse($this->resolver->pathExists($model, 'missing->key'));
    }
}
