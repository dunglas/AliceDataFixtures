<?php

/*
 * This file is part of the Fidry\AliceDataFixtures package.
 *
 * (c) Théo FIDRY <theo.fidry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Fidry\AliceDataFixtures\Bridge\Doctrine\Persister;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Entity\Dummy;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Entity\DummyEmbeddable;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Entity\DummySubClass;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Entity\DummyWithEmbeddable;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Entity\MappedSuperclassDummy;
use Fidry\AliceDataFixtures\Persistence\PersisterInterface;

/**
 * @covers \Fidry\AliceDataFixtures\Bridge\Doctrine\Persister\ObjectManagerPersister
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class ObjectManagerPersisterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManagerPersister
     */
    private $persister;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ORMPurger
     */
    private $purger;

    public function setUp()
    {
        $this->entityManager = $GLOBALS['entity_manager'];
        $this->persister = new ObjectManagerPersister($this->entityManager);
        $this->purger = new ORMPurger($this->entityManager);
    }

    public function tearDown()
    {
        $this->purger->purge();
    }

    public function testIsAPersister()
    {
        $this->assertTrue(is_a(ObjectManagerPersister::class, PersisterInterface::class, true));
    }

    /**
     * @dataProvider provideEntities
     */
    public function testCanPersistAnEntity($entity)
    {
        $this->persister->persist($entity);
        $this->persister->flush();

        $result = $this->entityManager->getRepository(get_class($entity))->findAll();

        $this->assertEquals(1, count($result));
    }

    /**
     * @dataProvider provideNonPersistableEntities
     */
    public function testDoesNotPersistEmbeddables($dummy)
    {
        try {
            $this->entityManager->persist($dummy);
            $this->entityManager->flush();
            $this->fail('Expected exception to be thrown.');
        } catch (ORMInvalidArgumentException $exception) {
            // Expected result
            $this->entityManager->clear();
        }

        $this->persister->persist($dummy);
        $this->persister->flush();
    }

    public function provideEntities()
    {
        yield 'simple entity' => [new Dummy()];

        yield 'entity with embeddable' => [
            (function () {
                $embeddable = new DummyEmbeddable();
                $dummy = new DummyWithEmbeddable();

                $dummy->embeddable = $embeddable;

                return $dummy;
            })()
        ];

        yield 'sub class entity' => [
            (function () {
                $dummy = new DummySubClass();
                $dummy->status = 200;

                return $dummy;
            })()
        ];
    }

    public function provideNonPersistableEntities()
    {
        yield 'embeddable' => [new DummyEmbeddable()];

        yield 'mapped super class' => [new MappedSuperclassDummy()];
    }
}
