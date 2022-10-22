<?php

class Foo
{
    public function BitwiseAnd(): int
    {
        return
            1
            &
            1
        ;
    }

    public function BitwiseOr(): int
    {
        return
            1
            |
            1
        ;
    }

    public function BitwiseXor(): int
    {
        return
            1
            ^
            1
        ;
    }

    public function BooleanAnd(): bool
    {
        return
            true
            &&
            false
        ;
    }

    public function BooleanOr(): bool
    {
        return
            false
            ||
            true
        ;
    }

    public function Coalesce(): bool
    {
        return
            true
            ??
            false
        ;
    }

    public function Concat(): string
    {
        return
            'foo'
            .
            'bar'
        ;
    }

    public function Div(): int
    {
        return
            2
            /
            1
        ;
    }

    public function Equal(): bool
    {
        return
            2
            ==
            1
        ;
    }

    public function Greater(): bool
    {
        return
            2
            >
            1
        ;
    }

    public function GreaterOrEqual(): bool
    {
        return
            2
            >=
            1
        ;
    }

    public function Identical(): bool
    {
        return
            2
            ===
            1
        ;
    }

    public function LogicalAnd(): bool
    {
        return
            true
            and
            false
        ;
    }

    public function LogicalOr(): bool
    {
        return
            true
            or
            false
        ;
    }

    public function LogicalXor(): bool
    {
        return
            true
            xor
            false
        ;
    }

    public function Minus(): int
    {
        return
            2
            -
            1
        ;
    }

    public function Mod(): int
    {
        return
            2
            %
            1
        ;
    }

    public function Mul(): int
    {
        return
            2
            *
            1
        ;
    }

    public function NotEqual(): bool
    {
        return
            2
            !=
            1
        ;
    }

    public function NotIdentical(): bool
    {
        return
            2
            !==
            1
        ;
    }

    public function Plus(): int
    {
        return
            2
            +
            1
        ;
    }

    public function Pow(): int
    {
        return
            2
            **
            1
        ;
    }

    public function ShiftLeft(): int
    {
        return
            2
            <<
            1
        ;
    }

    public function ShiftRight(): int
    {
        return
            2
            >>
            1
        ;
    }

    public function Smaller(): bool
    {
        return
            2
            <
            1
        ;
    }

    public function SmallerOrEqual(): bool
    {
        return
            2
            <=
            1
        ;
    }

    public function Spaceship(): int
    {
        return
            2
            <=>
            1
        ;
    }

    public function nowdocSimpleA(): string
    {
        return <<<'EOF'
            foo
            EOF;
    }

    public function nowdocSimpleB(): string
    {
        return
            <<<'EOF'
                foo
                EOF;
    }

    public function nowdocSimpleC(): string
    {
        return
            <<<'EOF'
                foo
                EOF
        ;
    }

    public function nowdocConcatA(): string
    {
        return '' .
            <<<'EOF'
                foo
                EOF;
    }

    public function nowdocConcatB(): string
    {
        return ''
            . <<<'EOF'
                foo
                EOF;
    }

    public function nowdocConcatC(): string
    {
        return <<<'EOF'
                foo
                EOF
            . '';
    }

    public function nowdocConcatNested(): string
    {
        return (<<<'EOF'
                foo
                EOF
            . <<<'EOF'
                foo
                EOF)
            . (<<<'EOF'
                foo
                EOF
            . <<<'EOF'
                foo
                EOF);
    }

    public function complexAssociativityRight(): int
    {
        return
            1
            **
            2
            **
            3;
    }

    public function complexAssociativityLeft(): int
    {
        return
            1
            >>
            2
            >>
            3;
    }

    public function complexAssociativityNa(): bool
    {
        return
            !
            !
            !
            false;
    }

    public function complexTernary(): int
    {
        return
            1
            ? (
                2
                ? 3
                : 4
            )
            : 5;
    }

    public function complexNullCoalescing(): int
    {
        return
            null
            ??
            1
            ??
            null;
    }

    public function constFromArray(): string
    {
        return [
            'foo',
            'bar',
            'ro',
            'fi',
            'omega',
        ][2];
    }

    public function withNotConstInTheMiddle(): string
    {
        return
            ''
            . ''
            . phpversion()
            . ''
            . '';
    }

    public function andA(): bool
    {
        return
            true
            && false;
    }

    public function andB(): bool
    {
        return
            true
            && true;
    }

    public function andC(): bool
    {
        return
            false
            && true;
    }

    public function andD(): bool
    {
        return
            false
            && false;
    }

    public function andE(): bool
    {
        return
            __TRAIT__ // compile time constant evaluated to false
            && 1
            && 0;
    }

    public function andF(): bool
    {
        return
            PHP_VERSION_ID // compile time constant evaluated to true
            && 1
            && 0;
    }

    public function orA(): bool
    {
        return
            true
            || false;
    }

    public function orB(): bool
    {
        return
            true
            || true;
    }

    public function orC(): bool
    {
        return
            false
            || true;
    }

    public function orD(): bool
    {
        return
            false
            || false;
    }

    public function orE(): bool
    {
        return
            __TRAIT__
            || true
            || false;
    }

    public function orF(): bool
    {
        return
            PHP_VERSION_ID
            || true
            || false;
    }

    public function orG(): bool
    {
        return
            PHP_VERSION_ID === PHP_VERSION_ID
            || true
            || false;
    }

    public function orH(): bool
    {
        return
            PHP_VERSION_ID !== PHP_VERSION_ID
            || true
            || false;
    }

    public function constIfFalseA(): bool
    {
        if (false) {
            return true;
        }

        return false;
    }

    public function constIfFalseB(): bool
    {
        if (__TRAIT__) {
            return true;
        }

        return false;
    }

    public function constIfTrueA(): bool
    {
        if (true) {
            return true;
        }

        return false;
    }

    public function constIfTrueB(): bool
    {
        if (PHP_VERSION_ID) {
            return true;
        }

        return false;
    }

    public function constIfUnknown(): bool
    {
        if (__NOT_EXPECTED_TO_BE_KNOWN_CONSTANT__) {
            return true;
        }

        return false;
    }
}
