<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Views\ModelReplacer;
use AlfonsoSG\Mvc\Views\ViewValueResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ModelReplacerTest extends TestCase
{
    private ModelReplacer $replacer;

    protected function setUp(): void
    {
        $this->replacer = new ModelReplacer(new ViewValueResolver());
    }

    public function testReplacesPrimitiveProperties(): void
    {
        $model = (object) [
            'name' => 'Peter Parker',
            'age' => 25,
            'height' => 1.75,
            'isStudent' => true,
            'isEmployed' => false,
            'createdAt' => new \DateTimeImmutable('2025-01-02T12:01:02.000Z'),
        ];
        $template = 'Name: {{name}}, Age: {{age}}, Height: {{height}},'
             .' Student: {{isStudent}}, Employed: {{isEmployed}}, Created: {{createdAt}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('Peter Parker', $result);
        $this->assertStringContainsString('25', $result);
        $this->assertStringContainsString('1.75', $result);
        $this->assertStringContainsString('true', $result);
        $this->assertStringContainsString('false', $result);
        $this->assertStringContainsString('2025-01-02T12:01:02+00:00', $result);
    }

    public function testReplacesNestedObjectProperties(): void
    {
        $address = (object) [
            'street' => 'Elm Street',
            'number' => 123,
            'city' => 'Springwood',
        ];
        $model = (object) [
            'name' => 'Freddy Krueger',
            'address' => $address,
        ];
        $template = 'Name: {{name}}, Street: {{address->street}}, City: {{address->city}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('Freddy Krueger', $result);
        $this->assertStringContainsString('Elm Street', $result);
        $this->assertStringContainsString('Springwood', $result);
    }

    public function testReplacesArrayOfStrings(): void
    {
        $model = (object) [
            'items' => ['foo', 'bar', 'baz'],
        ];
        $template = '{{#for item in items:}}Item: {{item}}; {{#endfor items:}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('Item: foo;', $result);
        $this->assertStringContainsString('Item: bar;', $result);
        $this->assertStringContainsString('Item: baz;', $result);
    }

    public function testReplacesArrayOfObjects(): void
    {
        $user1 = (object) ['id' => '1', 'name' => 'Peter'];
        $user2 = (object) ['id' => '2', 'name' => 'Freddy'];
        $model = (object) [
            'users' => [$user1, $user2],
        ];
        $template = '{{#for user in users:}}User: {{user->name}}; {{#endfor users:}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('User: Peter;', $result);
        $this->assertStringContainsString('User: Freddy;', $result);
    }

    public function testHandlesEmptyModel(): void
    {
        $model = (object) [];
        $template = 'Nothing to replace here.';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertSame('Nothing to replace here.', $result);
    }

    public function testHandlesMissingProperty(): void
    {
        $model = (object) ['name' => 'Peter'];
        $template = 'Name: {{name}}, Age: {{age}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('Peter', $result);
        // Missing model paths are left unchanged so I18nReplacer can replace them (e.g. {{layout.app}})
        $this->assertStringContainsString('{{age}}', $result);
    }

    public function testReplacesWithArrayIndexInPath(): void
    {
        $model = (object) ['users' => [(object) ['name' => 'Alice'], (object) ['name' => 'Bob']]];
        $template = 'First: {{users[0]->name}}, Second: {{users[1]->name}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('First: Alice', $result);
        $this->assertStringContainsString('Second: Bob', $result);
    }

    public function testReplacesNestedLoops(): void
    {
        $section1 = (object) ['title' => 'A', 'items' => [(object) ['name' => 'A1'], (object) ['name' => 'A2']]];
        $section2 = (object) ['title' => 'B', 'items' => [(object) ['name' => 'B1']]];
        $model = (object) ['sections' => [$section1, $section2]];
        $template = '{{#for section in sections:}}'
            .'[{{section->title}}: {{#for item in section->items:}}{{item->name}}'
            .'{{#endfor section->items:}}] {{#endfor sections:}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertStringContainsString('[A: A1A2]', $result);
        $this->assertStringContainsString('[B: B1]', $result);
    }

    public function testEscapesHtmlByDefault(): void
    {
        $model = (object) [
            'unsafe' => '<script>alert("xss")</script>',
        ];
        $template = 'Value: {{unsafe}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertSame(
            'Value: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            $result
        );
    }

    public function testRendersRawHtmlWithTripleBraces(): void
    {
        $model = (object) [
            'content' => '<strong>Safe</strong>',
        ];
        $template = 'Value: {{{content}}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        $this->assertSame(
            'Value: <strong>Safe</strong>',
            $result
        );
    }

    public function testResolvesNestedPlaceholderInsideExpressionForDynamicI18nKey(): void
    {
        $model = (object) [
            'model' => (object) [
                'status' => 'success',
            ],
        ];
        $template = 'Message key: {{flash.{{model->status}}}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        // ModelReplacer should resolve the inner {{model->status}} but keep the outer placeholder
        // so that I18nReplacer can later replace {{flash.success}} using the dictionary.
        $this->assertSame('Message key: {{flash.success}}', $result);
    }

    public function testSimplePlaceholdersWithoutNestedExpressionsRemainUnchangedWhenPathMissing(): void
    {
        $model = (object) [
            'name' => 'Peter',
        ];
        $template = 'Name: {{name}}, Missing: {{nonExistingKey}}';

        $result = $this->replacer->replace($model, $template, new RequestContext());

        // Existing paths are replaced, missing ones are kept as-is for I18nReplacer.
        $this->assertStringContainsString('Name: Peter', $result);
        $this->assertStringContainsString('{{nonExistingKey}}', $result);
    }
}
