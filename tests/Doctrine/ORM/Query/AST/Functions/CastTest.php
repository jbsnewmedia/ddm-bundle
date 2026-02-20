<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use JBSNewMedia\DDMBundle\Doctrine\ORM\Query\AST\Functions\Cast;
use PHPUnit\Framework\TestCase;

final class CastTest extends TestCase
{
    public function testGetSql(): void
    {
        $fn = new Cast('CAST');

        $sqlWalker = $this->getMockBuilder(SqlWalker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fn->expression = new class extends Node {
            public function dispatch($walker): string { return 'e0_.id'; }
        };
        $fn->type = 'string';

        $sql = $fn->getSql($sqlWalker);
        $this->assertSame('CAST(e0_.id AS string)', $sql);
    }

    public function testParse(): void
    {
        $dql = 'CAST(p.id AS VARCHAR)';

        $config = new Configuration();
        $em = $this->createMock(EntityManager::class);
        $em->method('getConfiguration')->willReturn($config);

        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('getEntityManager')->willReturn($em);

        $parser = new Parser($query);
        $parser->getLexer()->setInput($dql);
        $parser->getLexer()->moveNext(); // Move to CAST

        $fn = new Cast('CAST');
        $fn->parse($parser);

        $this->assertInstanceOf(Node::class, $fn->expression);
        $this->assertSame('VARCHAR', $fn->type);
    }
}
