<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Quote;

use Magebit\AgenticCore\Model\Quote\PersonName;
use PHPUnit\Framework\TestCase;

class PersonNameTest extends TestCase
{
    /**
     * @param string|null $name
     * @param string|null $expectedFirst
     * @param string|null $expectedLast
     * @return void
     * @dataProvider nameProvider
     */
    public function testSplitting(?string $name, ?string $expectedFirst, ?string $expectedLast): void
    {
        $this->assertSame([$expectedFirst, $expectedLast], PersonName::split($name));
    }

    /**
     * @return array<string, array{string|null, string|null, string|null}>
     */
    public static function nameProvider(): array
    {
        return [
            'two parts split on the space' => ['Ada Lovelace', 'Ada', 'Lovelace'],
            'everything after the first space is the last name' => ['Ada King Lovelace', 'Ada', 'King Lovelace'],
            // A caller that dropped the whole address here was losing a deliverable address over a
            // name it had no business validating.
            'a single word is a first name' => ['Ada', 'Ada', null],
            'surrounding whitespace is trimmed' => ['  Ada  Lovelace  ', 'Ada', 'Lovelace'],
            'an empty string is absent' => ['', null, null],
            'whitespace only is absent' => ['   ', null, null],
            'null is absent' => [null, null, null],
        ];
    }
}
