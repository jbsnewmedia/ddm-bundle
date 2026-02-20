<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "CAST" "(" Expression "AS" Type ")".
 */
class Cast extends FunctionNode
{
    public Node $expression;
    public string $type;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'CAST('.
            $this->expression->dispatch($sqlWalker).' AS '.
            $this->type.
        ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expression = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_AS);
        $parser->match(TokenType::T_IDENTIFIER);
        $this->type = $parser->getLexer()->token->value ?? '';
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
