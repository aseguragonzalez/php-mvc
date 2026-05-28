<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Actions;

use PhpMvc\Actions\ActionParameterBuilder;
use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;
use PHPUnit\Framework\TestCase;
use Tests\Unit\PhpMvc\Fixtures\Actions\RequestObject;
use Tests\Unit\PhpMvc\Fixtures\Actions\WithoutDocsObject;

/**
 * @internal
 *
 * @coversNothing
 */
final class ActionParameterBuilderTest extends TestCase
{
    private Faker $faker;

    public function setUp(): void
    {
        $this->faker = FakerFactory::create();
    }

    public function tearDown(): void {}

    public function testBuild(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'id' => $this->faker->randomNumber(),
            'amount' => $this->faker->randomFloat(),
            'name' => $this->faker->name,
            'uuid' => $this->faker->uuid,
            'date' => '2021-01-01 00:00:00',
            'dateImmutable' => '2021-01-01 00:00:00',
            'active' => $this->faker->boolean(),
        ];
        $requestBuilder->withArgs(array_map(fn ($item) => (string) $item, $args));

        $requestObject = $requestBuilder->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertSame($args['id'], $requestObject->id);
        $this->assertEqualsWithDelta($args['amount'], $requestObject->amount, 0.1);
        $this->assertSame($args['name'], $requestObject->name);
        $this->assertSame($args['uuid'], $requestObject->uuid);
        $this->assertSame($args['date'], $requestObject->date ? $requestObject->date->format('Y-m-d H:i:s') : null);
        $this->assertSame(
            $args['dateImmutable'],
            $requestObject->dateImmutable ? $requestObject->dateImmutable->format('Y-m-d H:i:s') : null
        );
        $this->assertSame($args['active'], $requestObject->active);
    }

    public function testBuildIntArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'intItems[0]' => $this->faker->randomNumber(),
            'intItems[1]' => $this->faker->randomNumber(),
            'intItems[2]' => $this->faker->randomNumber(),
            'intItems[3]' => $this->faker->randomNumber(),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['intItems[0]'], $requestObject->intItems[0]);
        $this->assertEquals($args['intItems[1]'], $requestObject->intItems[1]);
        $this->assertEquals($args['intItems[2]'], $requestObject->intItems[2]);
        $this->assertEquals($args['intItems[3]'], $requestObject->intItems[3]);
    }

    public function testBuildFloatArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'floatItems[0]' => $this->faker->randomFloat(),
            'floatItems[1]' => $this->faker->randomFloat(),
            'floatItems[2]' => $this->faker->randomFloat(),
            'floatItems[3]' => $this->faker->randomFloat(),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['floatItems[0]'], $requestObject->floatItems[0]);
        $this->assertEquals($args['floatItems[1]'], $requestObject->floatItems[1]);
        $this->assertEquals($args['floatItems[2]'], $requestObject->floatItems[2]);
        $this->assertEquals($args['floatItems[3]'], $requestObject->floatItems[3]);
    }

    public function testBuildStringArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'stringItems[0]' => $this->faker->name,
            'stringItems[1]' => $this->faker->name,
            'stringItems[2]' => $this->faker->name,
            'stringItems[3]' => $this->faker->name,
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['stringItems[0]'], $requestObject->stringItems[0]);
        $this->assertEquals($args['stringItems[1]'], $requestObject->stringItems[1]);
        $this->assertEquals($args['stringItems[2]'], $requestObject->stringItems[2]);
        $this->assertEquals($args['stringItems[3]'], $requestObject->stringItems[3]);
    }

    public function testBuildBoolArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'boolItems[0]' => $this->faker->boolean() ? 'true' : 'false',
            'boolItems[1]' => $this->faker->boolean() ? 'true' : 'false',
            'boolItems[2]' => $this->faker->boolean() ? 'true' : 'false',
            'boolItems[3]' => $this->faker->boolean() ? 'true' : 'false',
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['boolItems[0]'], $requestObject->boolItems[0]);
        $this->assertEquals($args['boolItems[1]'], $requestObject->boolItems[1]);
        $this->assertEquals($args['boolItems[2]'], $requestObject->boolItems[2]);
        $this->assertEquals($args['boolItems[3]'], $requestObject->boolItems[3]);
    }

    public function testBuildDateTimeArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'dateTimeItems[0]' => new \DateTime()->format('Y-m-d H:i:s'),
            'dateTimeItems[1]' => new \DateTime()->format('Y-m-d H:i:s'),
            'dateTimeItems[2]' => new \DateTime()->format('Y-m-d H:i:s'),
            'dateTimeItems[3]' => new \DateTime()->format('Y-m-d H:i:s'),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['dateTimeItems[0]'], $requestObject->dateTimeItems[0]->format('Y-m-d H:i:s'));
        $this->assertEquals($args['dateTimeItems[1]'], $requestObject->dateTimeItems[1]->format('Y-m-d H:i:s'));
        $this->assertEquals($args['dateTimeItems[2]'], $requestObject->dateTimeItems[2]->format('Y-m-d H:i:s'));
        $this->assertEquals($args['dateTimeItems[3]'], $requestObject->dateTimeItems[3]->format('Y-m-d H:i:s'));
    }

    public function testBuildDateTimeImmutableArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'dateTimeImmutableItems[0]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'dateTimeImmutableItems[1]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'dateTimeImmutableItems[2]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'dateTimeImmutableItems[3]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals(
            $args['dateTimeImmutableItems[0]'],
            $requestObject->dateTimeImmutableItems[0]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['dateTimeImmutableItems[1]'],
            $requestObject->dateTimeImmutableItems[1]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['dateTimeImmutableItems[2]'],
            $requestObject->dateTimeImmutableItems[2]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['dateTimeImmutableItems[3]'],
            $requestObject->dateTimeImmutableItems[3]->format('Y-m-d H:i:s')
        );
    }

    public function testBuildClassTypeArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'customClassType[0].id' => $this->faker->randomNumber(),
            'customClassType[0].name' => $this->faker->name,
            'customClassType[0].createdAt' => '2021-01-01 00:00:00',
            'customClassType[0].active' => $this->faker->boolean(),
            'customClassType[1].id' => $this->faker->randomNumber(),
            'customClassType[1].name' => $this->faker->name,
            'customClassType[1].createdAt' => '2021-01-02 01:01:00',
            'customClassType[1].active' => $this->faker->boolean(),
            'customClassType[2].id' => $this->faker->randomNumber(),
            'customClassType[2].name' => $this->faker->name,
            'customClassType[2].createdAt' => '2021-01-03 02:02:00',
            'customClassType[2].active' => $this->faker->boolean(),
        ];
        $requestBuilder->withArgs(array_map(fn ($item) => (string) $item, $args));

        $requestObject = $requestBuilder->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertEquals($args['customClassType[0].id'], $requestObject->customClassType[0]->id);
        $this->assertEquals($args['customClassType[0].name'], $requestObject->customClassType[0]->name);
        $this->assertEquals(
            $args['customClassType[0].createdAt'],
            $requestObject->customClassType[0]->createdAt->format('Y-m-d H:i:s')
        );
        $this->assertEquals($args['customClassType[0].active'], $requestObject->customClassType[0]->active);
        $this->assertEquals($args['customClassType[1].id'], $requestObject->customClassType[1]->id);
        $this->assertEquals($args['customClassType[1].name'], $requestObject->customClassType[1]->name);
        $this->assertEquals(
            $args['customClassType[1].createdAt'],
            $requestObject->customClassType[1]->createdAt->format('Y-m-d H:i:s')
        );
        $this->assertEquals($args['customClassType[1].active'], $requestObject->customClassType[1]->active);
        $this->assertEquals($args['customClassType[2].id'], $requestObject->customClassType[2]->id);
        $this->assertEquals($args['customClassType[2].name'], $requestObject->customClassType[2]->name);
        $this->assertEquals(
            $args['customClassType[2].createdAt'],
            $requestObject->customClassType[2]->createdAt->format('Y-m-d H:i:s')
        );
        $this->assertEquals($args['customClassType[2].active'], $requestObject->customClassType[2]->active);
    }

    public function testBuildEmbeddedObject(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'id' => $this->faker->randomNumber(),
            'innerTypeObject.id' => $this->faker->randomNumber(),
            'innerTypeObject.name' => $this->faker->name,
            'innerTypeObject.createdAt' => '2021-01-01 00:00:00',
            'innerTypeObject.active' => $this->faker->boolean(),
        ];
        $requestBuilder->withArgs(array_map(fn ($item) => (string) $item, $args));

        $requestObject = $requestBuilder->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertSame($args['id'], $requestObject->id);
        $this->assertNotNull($requestObject->innerTypeObject);
        $this->assertSame($args['innerTypeObject.id'], $requestObject->innerTypeObject->id);
        $this->assertSame($args['innerTypeObject.name'], $requestObject->innerTypeObject->name);
        $this->assertSame(
            $args['innerTypeObject.createdAt'],
            $requestObject->innerTypeObject->createdAt->format('Y-m-d H:i:s')
        );
        $this->assertSame($args['innerTypeObject.active'], $requestObject->innerTypeObject->active);
    }

    public function testBuildEmbeddedObjectWithIntArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.intItems[0]' => $this->faker->randomNumber(),
            'embeddedObject.intItems[1]' => $this->faker->randomNumber(),
            'embeddedObject.intItems[2]' => $this->faker->randomNumber(),
            'embeddedObject.intItems[3]' => $this->faker->randomNumber(),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertNotNull($requestObject->embeddedObject);
        $this->assertEquals($args['embeddedObject.intItems[0]'], $requestObject->embeddedObject->intItems[0]);
        $this->assertEquals($args['embeddedObject.intItems[1]'], $requestObject->embeddedObject->intItems[1]);
        $this->assertEquals($args['embeddedObject.intItems[2]'], $requestObject->embeddedObject->intItems[2]);
        $this->assertEquals($args['embeddedObject.intItems[3]'], $requestObject->embeddedObject->intItems[3]);
    }

    public function testBuildEmbeddedObjectWithFloatArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.floatItems[0]' => $this->faker->randomFloat(),
            'embeddedObject.floatItems[1]' => $this->faker->randomFloat(),
            'embeddedObject.floatItems[2]' => $this->faker->randomFloat(),
            'embeddedObject.floatItems[3]' => $this->faker->randomFloat(),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertNotNull($requestObject->embeddedObject);
        $this->assertEquals($args['embeddedObject.floatItems[0]'], $requestObject->embeddedObject->floatItems[0]);
        $this->assertEquals($args['embeddedObject.floatItems[1]'], $requestObject->embeddedObject->floatItems[1]);
        $this->assertEquals($args['embeddedObject.floatItems[2]'], $requestObject->embeddedObject->floatItems[2]);
        $this->assertEquals($args['embeddedObject.floatItems[3]'], $requestObject->embeddedObject->floatItems[3]);
    }

    public function testBuildEmbeddedObjectWithStringArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.stringItems[0]' => $this->faker->name,
            'embeddedObject.stringItems[1]' => $this->faker->name,
            'embeddedObject.stringItems[2]' => $this->faker->name,
            'embeddedObject.stringItems[3]' => $this->faker->name,
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertNotNull($requestObject->embeddedObject);
        $this->assertEquals($args['embeddedObject.stringItems[0]'], $requestObject->embeddedObject->stringItems[0]);
        $this->assertEquals($args['embeddedObject.stringItems[1]'], $requestObject->embeddedObject->stringItems[1]);
        $this->assertEquals($args['embeddedObject.stringItems[2]'], $requestObject->embeddedObject->stringItems[2]);
        $this->assertEquals($args['embeddedObject.stringItems[3]'], $requestObject->embeddedObject->stringItems[3]);
    }

    public function testBuildEmbeddedObjectWithBoolArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.boolItems[0]' => $this->faker->boolean() ? 'true' : 'false',
            'embeddedObject.boolItems[1]' => $this->faker->boolean() ? 'true' : 'false',
            'embeddedObject.boolItems[2]' => $this->faker->boolean() ? 'true' : 'false',
            'embeddedObject.boolItems[3]' => $this->faker->boolean() ? 'true' : 'false',
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertNotNull($requestObject->embeddedObject);
        $this->assertEquals($args['embeddedObject.boolItems[0]'], $requestObject->embeddedObject->boolItems[0]);
        $this->assertEquals($args['embeddedObject.boolItems[1]'], $requestObject->embeddedObject->boolItems[1]);
        $this->assertEquals($args['embeddedObject.boolItems[2]'], $requestObject->embeddedObject->boolItems[2]);
        $this->assertEquals($args['embeddedObject.boolItems[3]'], $requestObject->embeddedObject->boolItems[3]);
    }

    public function testBuildEmbeddedObjectWithDateTimeArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.dateTimeItems[0]' => new \DateTime()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeItems[1]' => new \DateTime()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeItems[2]' => new \DateTime()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeItems[3]' => new \DateTime()->format('Y-m-d H:i:s'),
        ];

        $requestObject = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertNotNull($requestObject->embeddedObject);
        $this->assertEquals(
            $args['embeddedObject.dateTimeItems[0]'],
            $requestObject->embeddedObject->dateTimeItems[0]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeItems[1]'],
            $requestObject->embeddedObject->dateTimeItems[1]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeItems[2]'],
            $requestObject->embeddedObject->dateTimeItems[2]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeItems[3]'],
            $requestObject->embeddedObject->dateTimeItems[3]->format('Y-m-d H:i:s')
        );
    }

    public function testBuildEmbeddedObjectWithDateTimeImmutableArray(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'embeddedObject.dateTimeImmutableItems[0]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeImmutableItems[1]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeImmutableItems[2]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'embeddedObject.dateTimeImmutableItems[3]' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        $request = $requestBuilder->withArgs($args)->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $request);
        $this->assertNotNull($request->embeddedObject);
        $this->assertEquals(
            $args['embeddedObject.dateTimeImmutableItems[0]'],
            $request->embeddedObject->dateTimeImmutableItems[0]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeImmutableItems[1]'],
            $request->embeddedObject->dateTimeImmutableItems[1]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeImmutableItems[2]'],
            $request->embeddedObject->dateTimeImmutableItems[2]->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $args['embeddedObject.dateTimeImmutableItems[3]'],
            $request->embeddedObject->dateTimeImmutableItems[3]->format('Y-m-d H:i:s')
        );
    }

    public function testBuildDefaultValues(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $requestBuilder->withArgs([]);

        $requestObject = $requestBuilder->build(RequestObject::class);

        $this->assertInstanceOf(RequestObject::class, $requestObject);
        $this->assertSame(0, $requestObject->id);
        $this->assertSame(0.0, $requestObject->amount);
        $this->assertSame('', $requestObject->name);
        $this->assertSame('', $requestObject->uuid);
        $this->assertNull($requestObject->date);
        $this->assertNull($requestObject->dateImmutable);
        $this->assertFalse($requestObject->active);
    }

    public function testBuildFailWhenNoArrayOfAttribute(): void
    {
        $requestBuilder = new ActionParameterBuilder();
        $args = [
            'items[0]' => new \DateTime()->format('Y-m-d'),
            'items[1]' => new \DateTime()->format('Y-m-d'),
            'items[2]' => new \DateTime()->format('Y-m-d'),
            'items[3]' => new \DateTime()->format('Y-m-d'),
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ArrayOf attribute not found for parameter items');

        $requestBuilder->withArgs($args)->build(WithoutDocsObject::class);
    }
}
