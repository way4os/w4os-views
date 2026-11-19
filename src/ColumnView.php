<?php

namespace W4OS\Views;

/**
 * A column of a list: its attribute, the catalog key of its title, its display type; the order it is hidden in as the width
 * shrinks (`priority`: 1 at every width, `primary`, then 2, 3, 4..., a higher number hidden first, its value under the name),
 * whether its value reads without its label (`bare`); its sort and its state (sorted, descending) and the sort a click on its
 * title asks (`next`).
 */
final readonly class ColumnView
{
    /** @param array<string,mixed> $definition the column as the contract gives it */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public int $priority,
        public bool $primary,
        public bool $bare,
        public ?string $sort,
        public bool $sorted,
        public bool $descending,
        public string $next,
        public array $definition,
    ) {}
}
