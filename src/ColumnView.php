<?php

namespace W4OS\Views;

/**
 * A column of a list: its attribute, the catalog key of its title, its display type; whether it shows at every width (`primary`,
 * else under the name on a narrow screen), whether its value reads without its label (`bare`); its sort and its state (sorted,
 * descending) and the sort a click on its title asks (`next`).
 */
final readonly class ColumnView
{
    /** @param array<string,mixed> $definition the column as the contract gives it */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public bool $primary,
        public bool $bare,
        public ?string $sort,
        public bool $sorted,
        public bool $descending,
        public string $next,
        public array $definition,
    ) {}
}
