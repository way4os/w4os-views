<?php

namespace W4OS\Views;

/**
 * A row of a list: its id and attributes, the link of its name (the first of the links the bridge gives that the row has), all
 * its links, its address under its name and the address it links to, its state, its indicators by column, the other actions of
 * its menu (the safe ones first, the ones asked last).
 */
final readonly class RowView
{
    /**
     * @param array<string,mixed> $item the row as the engine gives it
     * @param list<array{url: string, key: string}> $links
     * @param array<string,list<IndicatorView>> $indicators by the name of their column
     * @param list<ActionView> $actions
     */
    public function __construct(
        public string $id,
        public array $item,
        public ?string $link,
        public array $links,
        public ?string $subtitle,
        public ?string $subtitleLink,
        public ?string $status,
        public array $indicators,
        public array $actions,
    ) {}
}
