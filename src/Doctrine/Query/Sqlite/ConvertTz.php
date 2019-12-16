<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine\Query\Sqlite;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * This is just a fake, as SQLITE does not support this by now.
 */
class ConvertTz extends FunctionNode
{
    protected $dateExpression;

    protected $fromTz;

    protected $toTz;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $fieldName = $sqlWalker->walkArithmeticExpression($this->dateExpression);
        $fromTz = $sqlWalker->walkStringPrimary($this->fromTz);
        $toTz = $sqlWalker->walkStringPrimary($this->toTz);

        return sprintf('%s', $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->dateExpression = $parser->ArithmeticExpression();
        $parser->match(Lexer::T_COMMA);

        $this->fromTz = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);

        $this->toTz = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
