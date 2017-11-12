<?php declare(strict_types = 1);

namespace PhpStan\TypeParser\Parser;

use PhpStan\TypeParser\Ast;
use PhpStan\TypeParser\Lexer\Lexer;


class ConstExprParser
{
	public function parse(TokenIterator $tokens): Ast\ConstExpr\ConstExprNode
	{
		if ($tokens->isCurrentTokenType(Lexer::TOKEN_FLOAT)) {
			$value = $tokens->currentTokenValue();
			$tokens->next();
			return new Ast\ConstExpr\ConstExprFloatNode($value);

		} elseif ($tokens->isCurrentTokenType(Lexer::TOKEN_INTEGER)) {
			$value = $tokens->currentTokenValue();
			$tokens->next();
			return new Ast\ConstExpr\ConstExprIntegerNode($value);

		} elseif ($tokens->isCurrentTokenType(Lexer::TOKEN_SINGLE_QUOTED_STRING)) {
			$value = $tokens->currentTokenValue();
			$tokens->next();
			return new Ast\ConstExpr\ConstExprStringNode($value);

		} elseif ($tokens->isCurrentTokenType(Lexer::TOKEN_DOUBLE_QUOTED_STRING)) {
			$value = $tokens->currentTokenValue();
			$tokens->next();
			return new Ast\ConstExpr\ConstExprStringNode($value);

		} elseif ($tokens->isCurrentTokenType(Lexer::TOKEN_IDENTIFIER)) {
			$identifier = $tokens->currentTokenValue();
			$tokens->next();

			switch (strtolower($identifier)) {
				case 'true':
					return new Ast\ConstExpr\ConstExprTrueNode();
				case 'false':
					return new Ast\ConstExpr\ConstExprFalseNode();
				case 'null':
					return new Ast\ConstExpr\ConstExprNullNode();
				case 'array':
					$tokens->consumeTokenType(Lexer::TOKEN_OPEN_PARENTHESES);
					return $this->parseArray($tokens, Lexer::TOKEN_CLOSE_PARENTHESES);
			}

			if ($tokens->tryConsumeTokenType(Lexer::TOKEN_DOUBLE_COLON)) {
				$classConstantName = $tokens->currentTokenValue();
				$tokens->consumeTokenType(Lexer::TOKEN_IDENTIFIER);
				return new Ast\ConstExpr\ConstFetchNode($classConstantName, $identifier);

			} else {
				return new Ast\ConstExpr\ConstFetchNode('', $identifier);
			}

		} elseif ($tokens->tryConsumeTokenType(Lexer::TOKEN_OPEN_SQUARE_BRACKET)) {
			return $this->parseArray($tokens, Lexer::TOKEN_CLOSE_SQUARE_BRACKET);
		}

		throw new \LogicException($tokens->currentTokenValue());
	}


	private function parseArray(TokenIterator $tokens, int $endToken): Ast\ConstExpr\ConstExprArrayNode
	{
		$items = [];

		if (!$tokens->tryConsumeTokenType($endToken)) {
			do {
				$items[] = $this->parseArrayItem($tokens);
			} while ($tokens->tryConsumeTokenType(Lexer::TOKEN_COMMA) && !$tokens->isCurrentTokenType($endToken));
			$tokens->consumeTokenType($endToken);
		}

		return new Ast\ConstExpr\ConstExprArrayNode($items);
	}


	private function parseArrayItem(TokenIterator $tokens): Ast\ConstExpr\ConstExprArrayItemNode
	{
		$expr = $this->parse($tokens);

		if ($tokens->tryConsumeTokenType(Lexer::TOKEN_DOUBLE_ARROW)) {
			$key = $expr;
			$value = $this->parse($tokens);

		} else {
			$key = null;
			$value = $expr;
		}

		return new Ast\ConstExpr\ConstExprArrayItemNode($key, $value);
	}
}
