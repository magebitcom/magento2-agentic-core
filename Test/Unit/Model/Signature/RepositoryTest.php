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

use Magebit\AgenticCore\Model\Signature\Collection;
use Magebit\AgenticCore\Model\Signature\CollectionFactory;
use Magebit\AgenticCore\Model\Signature\Key;
use Magebit\AgenticCore\Model\Signature\KeyFactory;
use Magebit\AgenticCore\Model\Signature\KeyPairGenerator;
use Magebit\AgenticCore\Model\Signature\Repository;
use Magebit\AgenticCore\Model\Signature\ResourceModel;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private const SCOPE = 'caller';
    private const NOW = 1770000000;

    /**
     * @var ResourceModel&MockObject
     */
    private ResourceModel $resource;

    /**
     * @var Key[]
     */
    private array $stored = [];

    /**
     * @var int
     */
    private int $created = 0;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(ResourceModel::class);
    }

    /**
     * @return void
     */
    public function testReusesAnExistingActiveKey(): void
    {
        $this->stored = [$this->key('existing', true)];

        $this->assertSame('existing', $this->repository()->getSigningKey(self::SCOPE, 1)->getKid());
        $this->assertSame(0, $this->created);
    }

    /**
     * @return void
     */
    public function testGeneratesAKeyWhenThereIsNone(): void
    {
        $this->assertNotSame('', (string) $this->repository()->getSigningKey(self::SCOPE, 1)->getKid());
        $this->assertSame(1, $this->created);
    }

    /**
     * A retired key cannot sign, even though it is still published for verification.
     *
     * @return void
     */
    public function testIgnoresRetiredKeysWhenChoosingWhatToSignWith(): void
    {
        $this->stored = [$this->key('retired', false)];

        $this->repository()->getSigningKey(self::SCOPE, 1);

        $this->assertSame(1, $this->created);
    }

    /**
     * @return void
     */
    public function testRotationRetiresTheOldKeyAndAddsANewOne(): void
    {
        $old = $this->key('old', true);
        $this->stored = [$old];

        $new = $this->repository()->rotate(self::SCOPE, 1);

        $this->assertFalse($old->isActive());
        $this->assertNotSame('old', $new->getKid());
        $this->assertTrue($new->isActive());
    }

    /**
     * @return void
     */
    public function testPurgeNeverRemovesTheKeyStillInUse(): void
    {
        $this->stored = [$this->key('active', true, self::NOW - 400 * 86400)];
        $this->resource->expects($this->never())->method('delete');

        $this->assertSame(0, $this->repository()->purgeRetired(self::SCOPE, 1, 7));
    }

    /**
     * @return void
     */
    public function testPurgeKeepsARetiredKeyInsideTheGraceWindow(): void
    {
        $this->stored = [$this->key('recent', false, self::NOW - 3 * 86400)];
        $this->resource->expects($this->never())->method('delete');

        $this->assertSame(0, $this->repository()->purgeRetired(self::SCOPE, 1, 7));
    }

    /**
     * @return void
     */
    public function testPurgeRemovesARetiredKeyPastTheGraceWindow(): void
    {
        $this->stored = [$this->key('stale', false, self::NOW - 10 * 86400)];
        $this->resource->expects($this->once())->method('delete');

        $this->assertSame(1, $this->repository()->purgeRetired(self::SCOPE, 1, 7));
    }

    /**
     * @return Repository
     */
    private function repository(): Repository
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturnCallback(
            fn (): \ArrayIterator => new \ArrayIterator($this->stored)
        );

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $keyFactory = $this->createMock(KeyFactory::class);
        $keyFactory->method('create')->willReturnCallback(function (): Key {
            $this->created++;

            return $this->key('', true);
        });

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);
        $dateTime->method('gmtDate')->willReturnCallback(
            static fn (string $format, int $timestamp): string => gmdate($format, $timestamp)
        );

        return new Repository(
            $keyFactory,
            $collectionFactory,
            $this->resource,
            new KeyPairGenerator(),
            $dateTime
        );
    }

    /**
     * A real Key with the encryptor stubbed out, so the plaintext round trip is what the test controls.
     *
     * @param string $kid
     * @param bool $isActive
     * @param int|null $createdAt
     * @return Key
     */
    private function key(string $kid, bool $isActive, ?int $createdAt = null): Key
    {
        $key = $this->getMockBuilder(Key::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPrivateKeyPem', 'setPrivateKeyPem'])
            ->getMock();
        $key->method('getPrivateKeyPem')->willReturn('pem');
        $key->method('setPrivateKeyPem')->willReturnSelf();

        $key->setData(Key::KID, $kid);
        $key->setData(Key::IS_ACTIVE, $isActive ? 1 : 0);
        $key->setData(Key::CREATED_AT, gmdate('Y-m-d H:i:s', $createdAt ?? self::NOW));

        return $key;
    }
}
