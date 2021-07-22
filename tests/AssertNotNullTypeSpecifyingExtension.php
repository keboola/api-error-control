<?php

declare(strict_types=1);

namespace Keboola\ErrorControl\Tests;

//namespace PHPStan\Type;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\Assert;

class AssertNotNullTypeSpecifyingExtension implements TypeSpecifierAwareExtension
{
    public function getClass(): string
    {
        return Assert::class;
    }

    public function isStaticMethodSupported(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        TypeSpecifierContext $context
    ): bool {
        // The $context argument tells us if we're in an if condition or not (as in this case).
        return $staticMethodReflection->getName() === 'assertNotNull' && $context->null();
    }

    public function specifyTypes(
        MethodReflection $staticMethodReflection,
        StaticCall $node,
        Scope $scope,
        TypeSpecifierContext $context
    ): SpecifiedTypes {
        $arg = $node->args[0]->value;
        $newType = (TypeCombinator::removeNull($scope->getType($arg)));
        $printer = new Standard();
        return new SpecifiedTypes([$printer->prettyPrintExpr($arg) => [$arg, $newType]], []);
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
    }
}
