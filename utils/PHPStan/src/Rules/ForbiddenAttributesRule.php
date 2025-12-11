<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

use function array_map;
use function in_array;
use function sprintf;
use function strtolower;

class ForbiddenAttributesRule implements Rule
{
    /**
     * @param array<string> $forbiddenAttributes
     */
    public function __construct(private array $forbiddenAttributes)
    {
        // Normalize forbidden attributes to lowercase
        $this->forbiddenAttributes = array_map(strtolower(...), $this->forbiddenAttributes);
    }

    public function getNodeType(): string
    {
        return Node\Attribute::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Attribute) {
            return [];
        }

        $attributeName = $node->name->toString();
        $fullyQualifiedName = $scope->resolveName($node->name);

        // Check both short name and fully qualified name
        if ($this->checkAttribute($attributeName, $fullyQualifiedName)) {
            return [sprintf('Usage of the attribute "%s" is not allowed.', $attributeName)];
        }

        return [];
    }

    private function checkAttribute(string $attributeName, string $fullyQualifiedName): bool
    {
        return in_array(strtolower($attributeName), $this->forbiddenAttributes, true) ||
            in_array(strtolower($fullyQualifiedName), $this->forbiddenAttributes, true);
    }
}
