<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Signature;

use Magebit\AgenticCore\Model\Signature\SignatureBase;
use PHPUnit\Framework\TestCase;

class SignatureBaseTest extends TestCase
{
    /**
     * @var SignatureBase
     */
    private SignatureBase $base;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->base = new SignatureBase();
    }

    /**
     * @return void
     */
    public function testWritesOneLinePerComponentInTheGivenOrder(): void
    {
        $result = $this->base->build(
            ['@method' => 'POST', '@authority' => 'platform.example', '@path' => '/hooks'],
            '("@method" "@authority" "@path");keyid="k1"'
        );

        $this->assertSame(
            '"@method": POST' . "\n"
            . '"@authority": platform.example' . "\n"
            . '"@path": /hooks' . "\n"
            . '"@signature-params": ("@method" "@authority" "@path");keyid="k1"',
            $result
        );
    }

    /**
     * RFC 9421 ends the base at the params line. A trailing newline changes the bytes signed.
     *
     * @return void
     */
    public function testDoesNotEndWithANewline(): void
    {
        $result = $this->base->build(['@method' => 'POST'], '("@method");keyid="k1"');

        $this->assertStringEndsWith('("@method");keyid="k1"', $result);
        $this->assertStringNotContainsString("\n\n", $result);
    }

    /**
     * @return void
     */
    public function testLowercasesHeaderNamesButNotValues(): void
    {
        $result = $this->base->build(['Content-Type' => 'application/json'], '("content-type")');

        $this->assertStringContainsString('"content-type": application/json', $result);
    }

    /**
     * @return void
     */
    public function testRejectsAnEmptyComponentList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->base->build([], '()');
    }
}
