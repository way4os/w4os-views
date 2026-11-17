<?php

namespace W4OS\Views;

/**
 * An action a viewer may do on a row or an item: its name, the catalog key of its text, the key of the question to ask before
 * (`ask`), whether it is to be marked (it is asked), and its description in the contract (for its request, Rows::request()).
 */
final readonly class ActionView
{
    /** @param array<string,mixed> $definition the action as the contract gives it */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $ask,
        public bool $danger,
        public array $definition,
    ) {}

    /** @param array<string,mixed> $action an action of the contract */
    public static function of(array $action): self
    {
        $ask = isset($action['ask']) ? (string) $action['ask'] : null;

        return new self((string) $action['name'], (string) ($action['label'] ?? 'action_'.$action['name']), $ask, $ask !== null, $action);
    }
}
