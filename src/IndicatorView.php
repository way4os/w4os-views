<?php

namespace W4OS\Views;

/**
 * An indicator of a row, in the state its value meets: the semantic name of its icon (the state's own, else the indicator's), its
 * tone (ok, off, warning, failed), the catalog key of the label of its state, and the action of its icon, when the row has one.
 */
final readonly class IndicatorView
{
    public function __construct(
        public string $name,
        public string $icon,
        public string $tone,
        public string $label,
        public ?ActionView $action,
    ) {}
}
