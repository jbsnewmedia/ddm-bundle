<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMFactory;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;

final class DDMFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $fields = [new class extends DDMField {}];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $factory = new DDMFactory($fields, $entityManager);

        if (!class_exists('MyEntity')) { eval('class MyEntity {}'); }
        $ddm = $factory->create('MyEntity', 'context');

        $this->assertInstanceOf(DDM::class, $ddm);
        $this->assertSame('MyEntity', $ddm->getEntityClass());
    }
}
