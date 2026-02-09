<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Service;

use JBSNewMedia\DDMBundle\Service\DDM;
use JBSNewMedia\DDMBundle\Service\DDMFactory;
use JBSNewMedia\DDMBundle\Service\DDMField;
use PHPUnit\Framework\TestCase;

final class DDMFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $fields = [new class extends DDMField {}];
        $factory = new DDMFactory($fields);

        $ddm = $factory->create('MyEntity', 'context');

        $this->assertInstanceOf(DDM::class, $ddm);
        $this->assertSame('MyEntity', $ddm->getEntityClass());
    }
}
