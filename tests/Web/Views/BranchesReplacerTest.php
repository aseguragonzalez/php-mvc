<?php

declare(strict_types=1);

namespace Tests\Unit\AlfonsoSG\Mvc\Views;

use AlfonsoSG\Mvc\Requests\RequestContext;
use AlfonsoSG\Mvc\Views\BranchesReplacer;
use AlfonsoSG\Mvc\Views\ViewValueResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class BranchesReplacerTest extends TestCase
{
    private BranchesReplacer $branchesReplacer;

    protected function setUp(): void
    {
        $this->branchesReplacer = new BranchesReplacer(new ViewValueResolver());
    }

    public function testReplacesIfBranchWithTrueProperty(): void
    {
        $model = (object) ['isVisible' => true];
        $template = 'Hello {{#if isVisible:}}World{{#endif isVisible:}}!';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('Hello World!', $result);
    }

    public function testReplacesIfBranchWithFalseProperty(): void
    {
        $model = (object) ['isVisible' => false];
        $template = 'Hello {{#if isVisible:}}World{{#endif isVisible:}}!';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('Hello !', $result);
    }

    public function testReplacesIfBranchWithNegation(): void
    {
        $model = (object) ['isVisible' => false];
        $template = 'Hello {{#if !isVisible:}}Hidden{{#endif !isVisible:}}!';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('Hello Hidden!', $result);
    }

    public function testReplacesIfBranchWithNestedProperty(): void
    {
        $model = (object) ['user' => (object) ['active' => true]];
        $template = 'User is {{#if user->active:}}active{{#endif user->active:}}.';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('User is active.', $result);
    }

    public function testReplacesIfBranchWithArrayProperty(): void
    {
        $model = (object) ['data' => ['foo' => true]];
        $template = 'Item: {{#if data["foo"]:}}exists{{#endif data["foo"]:}}.';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('Item: exists.', $result);
    }

    public function testDoesNotReplaceIfBranchWhenModelIsNull(): void
    {
        $template = 'Hello {{#if isVisible:}}World{{#endif isVisible:}}!';

        $result = $this->branchesReplacer->replace((object) [], $template, new RequestContext());

        $this->assertSame('Hello !', $result);
    }

    public function testReplacesMultipleBranches(): void
    {
        $model = (object) ['a' => true, 'b' => false];
        $template = '{{#if a:}}A{{#endif a:}},{{#if b:}}B{{#endif b:}}';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('A,', $result);
    }

    public function testReplacesNestedIfBlocks(): void
    {
        $model = (object) ['outer' => true, 'inner' => true];
        $template = '{{#if outer:}}[{{#if inner:}}INNER{{#endif inner:}}]{{#endif outer:}}';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('[INNER]', $result);
    }

    public function testReplacesNestedIfBlocksInnerFalse(): void
    {
        $model = (object) ['outer' => true, 'inner' => false];
        $template = '{{#if outer:}}[{{#if inner:}}INNER{{#endif inner:}}]{{#endif outer:}}';

        $result = $this->branchesReplacer->replace($model, $template, new RequestContext());

        $this->assertSame('[]', $result);
    }
}
