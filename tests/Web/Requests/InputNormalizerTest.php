<?php

declare(strict_types=1);

namespace Tests\Unit\PhpMvc\Requests;

use PhpMvc\Requests\InputNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InputNormalizerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // normalizeScalar
    // -------------------------------------------------------------------------

    public function testNormalizeScalarReturnsNullForNull(): void
    {
        $this->assertNull(InputNormalizer::normalizeScalar(null));
    }

    public function testNormalizeScalarReturnsNullForNonScalar(): void
    {
        $this->assertNull(InputNormalizer::normalizeScalar(['key' => 'value']));
    }

    public function testNormalizeScalarTrimsString(): void
    {
        $this->assertSame('hello', InputNormalizer::normalizeScalar('  hello  '));
    }

    public function testNormalizeScalarNormalizesCrLfToLf(): void
    {
        $this->assertSame("line1\nline2", InputNormalizer::normalizeScalar("line1\r\nline2"));
    }

    public function testNormalizeScalarNormalizesCrToLf(): void
    {
        $this->assertSame("line1\nline2", InputNormalizer::normalizeScalar("line1\rline2"));
    }

    public function testNormalizeScalarCastsIntToString(): void
    {
        $this->assertSame('42', InputNormalizer::normalizeScalar(42));
    }

    public function testNormalizeScalarCastsFloatToString(): void
    {
        $this->assertSame('3.14', InputNormalizer::normalizeScalar(3.14));
    }

    public function testNormalizeScalarCastsBoolTrueToString(): void
    {
        $this->assertSame('1', InputNormalizer::normalizeScalar(true));
    }

    public function testNormalizeScalarCastsBoolFalseToEmptyString(): void
    {
        $this->assertSame('', InputNormalizer::normalizeScalar(false));
    }

    // -------------------------------------------------------------------------
    // toInt
    // -------------------------------------------------------------------------

    public function testToIntReturnsNullForNull(): void
    {
        $this->assertNull(InputNormalizer::toInt(null));
    }

    public function testToIntReturnsNullForEmptyString(): void
    {
        $this->assertNull(InputNormalizer::toInt('  '));
    }

    public function testToIntReturnsNullForNonNumericString(): void
    {
        $this->assertNull(InputNormalizer::toInt('abc'));
    }

    public function testToIntConvertsValidIntString(): void
    {
        $this->assertSame(42, InputNormalizer::toInt('42'));
    }

    public function testToIntConvertsNegativeInt(): void
    {
        $this->assertSame(-7, InputNormalizer::toInt('-7'));
    }

    public function testToIntConvertsIntWithWhitespace(): void
    {
        $this->assertSame(10, InputNormalizer::toInt(' 10 '));
    }

    // -------------------------------------------------------------------------
    // toFloat
    // -------------------------------------------------------------------------

    public function testToFloatReturnsNullForNull(): void
    {
        $this->assertNull(InputNormalizer::toFloat(null));
    }

    public function testToFloatReturnsNullForEmptyString(): void
    {
        $this->assertNull(InputNormalizer::toFloat('  '));
    }

    public function testToFloatReturnsNullForNonNumericString(): void
    {
        $this->assertNull(InputNormalizer::toFloat('abc'));
    }

    public function testToFloatConvertsValidFloatString(): void
    {
        $this->assertSame(3.14, InputNormalizer::toFloat('3.14'));
    }

    public function testToFloatConvertsIntString(): void
    {
        $this->assertSame(42.0, InputNormalizer::toFloat('42'));
    }

    public function testToFloatConvertsNegativeFloat(): void
    {
        $this->assertSame(-1.5, InputNormalizer::toFloat('-1.5'));
    }

    // -------------------------------------------------------------------------
    // toString
    // -------------------------------------------------------------------------

    public function testToStringReturnsNullForNull(): void
    {
        $this->assertNull(InputNormalizer::toString(null));
    }

    public function testToStringTrimsAndReturnsString(): void
    {
        $this->assertSame('hello', InputNormalizer::toString('  hello  '));
    }

    public function testToStringConvertsIntToString(): void
    {
        $this->assertSame('99', InputNormalizer::toString(99));
    }

    // -------------------------------------------------------------------------
    // toBool
    // -------------------------------------------------------------------------

    public function testToBoolReturnsFalseForNull(): void
    {
        $this->assertFalse(InputNormalizer::toBool(null));
    }

    public function testToBoolReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(InputNormalizer::toBool(''));
    }

    public function testToBoolReturnsFalseForFalseString(): void
    {
        $this->assertFalse(InputNormalizer::toBool('false'));
        $this->assertFalse(InputNormalizer::toBool('FALSE'));
        $this->assertFalse(InputNormalizer::toBool('False'));
    }

    public function testToBoolReturnsFalseForZeroString(): void
    {
        $this->assertFalse(InputNormalizer::toBool('0'));
    }

    public function testToBoolReturnsTrueForNonEmptyString(): void
    {
        $this->assertTrue(InputNormalizer::toBool('1'));
        $this->assertTrue(InputNormalizer::toBool('true'));
        $this->assertTrue(InputNormalizer::toBool('yes'));
    }

    public function testToBoolReturnsTrueForNonScalarCastingToNonEmpty(): void
    {
        $this->assertTrue(InputNormalizer::toBool(42));
    }
}
