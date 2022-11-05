<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeAbstract;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class ExecutableLinesFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-var array<int, int>
     */
    private $executableLines = [];

    /**
     * @psalm-var array<int, int>
     */
    private $propertyLines = [];

    /**
     * @psalm-var array<int, Return_|Expression|Assign|Array_>
     */
    private $returns = [];

    public function enterNode(Node $node): void
    {
        if (!$node instanceof NodeAbstract) {
            return;
        }

        $this->savePropertyLines($node);

        if (!$this->isExecutable($node)) {
            return;
        }

        foreach ($this->getLines($node, false) as $line) {
            if (isset($this->propertyLines[$line])) {
                return;
            }

            $this->executableLines[$line] = $line;
        }
    }

    public function afterTraverse(array $nodes): void
    {
        $this->computeReturns();

        sort($this->executableLines);
    }

    /**
     * @psalm-return array<int, int>
     */
    public function executableLines(): array
    {
        return $this->executableLines;
    }

    private function savePropertyLines(Node $node): void
    {
        if ($node instanceof Property) {
            foreach (range($node->getStartLine(), $node->getEndLine()) as $index) {
                $this->propertyLines[$index] = $index;
            }
        }
    }

    private function computeReturns(): void
    {
        foreach ($this->returns as $node) {
            foreach (range($node->getStartLine(), $node->getEndLine()) as $index) {
                if (isset($this->executableLines[$index])) {
                    continue;
                }
            }

            foreach ($this->getLines($node, true) as $line) {
                $this->executableLines[$line] = $line;
            }
        }
    }

    /**
     * @return int[]
     */
    private function getLines(NodeAbstract $node, bool $fromReturns): array
    {
        if (!$fromReturns && (
            $node instanceof Return_ ||
            $node instanceof Expression ||
            $node instanceof Assign ||
            $node instanceof Array_)
        ) {
            $this->returns[] = $node;

            return [];
        }

        if ($node instanceof Return_) {
            if ($node->expr === null) {
                return [$node->getEndLine()];
            } else {
                return $this->getLines($node->expr, $fromReturns);
            }
        } elseif ($node instanceof Expression) {
                return [$this->getNodeStartLine($node->expr)];
        } elseif ($node instanceof Assign) {
            return [$this->getNodeStartLine($node->var)];
        }

        if ($node instanceof BinaryOp) {
            return $fromReturns ? $this->getLines($node->right, $fromReturns) : [];
        }

        if ($node instanceof PropertyFetch ||
            $node instanceof NullsafePropertyFetch ||
            $node instanceof StaticPropertyFetch) {
            return [$this->getNodeStartLine($node->name)];
        }

        if ($node instanceof ArrayDimFetch && null !== $node->dim) {
            return [$this->getNodeStartLine($node->dim)];
        }

        if ($node instanceof ClassMethod) {
            if ($node->name->name !== '__construct') {
                return [];
            }

            $existsAPromotedProperty = false;

            foreach ($node->getParams() as $param) {
                if (0 !== ($param->flags & Class_::VISIBILITY_MODIFIER_MASK)) {
                    $existsAPromotedProperty = true;

                    break;
                }
            }

            if ($existsAPromotedProperty) {
                // Only the line with `function` keyword should be listed here
                // but `nikic/php-parser` doesn't provide a way to fetch it
                return range($node->getStartLine(), $node->name->getEndLine());
            }

            return [];
        }

        if ($node instanceof MethodCall) {
            return [$this->getNodeStartLine($node->name)];
        }

        if ($node instanceof Ternary) {
            $lines = [$this->getNodeStartLine($node->cond)];

            if (null !== $node->if) {
                $lines[] = $this->getNodeStartLine($node->if);
            }

            $lines[] = $this->getNodeStartLine($node->else);

            return $lines;
        }

        if ($node instanceof Match_) {
            return [$this->getNodeStartLine($node->cond)];
        }

        if ($node instanceof MatchArm) {
            return [$this->getNodeStartLine($node->body)];
        }

        // TODO this concept should be extended for every statement class like Foreach_
        if ($node instanceof If_ ||
            $node instanceof ElseIf_) {
            return [$this->getNodeStartLine($node->cond)];
        }

        return [$this->getNodeStartLine($node)];
    }

    private function getNodeStartLine(NodeAbstract $node): int
    {
        if ($node instanceof Node\Expr\Cast ||
            $node instanceof Node\Expr\BooleanNot ||
            $node instanceof Node\Expr\UnaryMinus ||
            $node instanceof Node\Expr\UnaryPlus
        ) {
            return $this->getNodeStartLine($node->expr);
        }

        if ($node instanceof BinaryOp) {
            return $this->getNodeStartLine($node->right);
        }

        if ($node instanceof Node\Scalar\String_ && (
            $node->getAttribute('kind') === Node\Scalar\String_::KIND_HEREDOC ||
            $node->getAttribute('kind') === Node\Scalar\String_::KIND_NOWDOC
        )) {
            return $node->getStartLine() + 1;
        }

        if ($node instanceof Array_) {
            if ([] === $node->items || $node->items[0] === null) {
                return $node->getEndLine();
            }

            return $this->getNodeStartLine($node->items[0]);
        }

        if ($node instanceof Assign) {
            return $this->getNodeStartLine($node->expr);
        }

        return $node->getStartLine(); // $node should be only a scalar here
    }

    private function isExecutable(Node $node): bool
    {
        return $node instanceof Assign ||
               $node instanceof ArrayDimFetch ||
               $node instanceof BinaryOp ||
               $node instanceof Break_ ||
               $node instanceof CallLike ||
               $node instanceof Case_ ||
               $node instanceof Catch_ ||
               $node instanceof ClassMethod ||
               $node instanceof Closure ||
               $node instanceof Continue_ ||
               $node instanceof Do_ ||
               $node instanceof Echo_ ||
               $node instanceof ElseIf_ ||
               $node instanceof Else_ ||
               $node instanceof Encapsed ||
               $node instanceof Expression ||
               $node instanceof Finally_ ||
               $node instanceof For_ ||
               $node instanceof Foreach_ ||
               $node instanceof Goto_ ||
               $node instanceof If_ ||
               $node instanceof Match_ ||
               $node instanceof MatchArm ||
               $node instanceof MethodCall ||
               $node instanceof NullsafePropertyFetch ||
               $node instanceof Print_ ||
               $node instanceof PropertyFetch ||
               $node instanceof Return_ ||
               $node instanceof StaticPropertyFetch ||
               $node instanceof Switch_ ||
               $node instanceof Ternary ||
               $node instanceof Throw_ ||
               $node instanceof TryCatch ||
               $node instanceof Unset_ ||
               $node instanceof While_;
    }
}
