<?php

namespace W4OS\Views;

/**
 * A filter of a list to show: its name (the parameter of the page), the catalog key of its label, its field (`select`: one value
 * among its values; `multiple`: several of them; `text`: a value written in its format, a UUID or a server), its values with the
 * catalog key of their label (or the `text` a bridge offers from the answer of the engine, shown as it is) and of their help
 * (`description`), the values the page asks (`chosen`), and whether it asks any (`active`).
 */
final readonly class FilterView
{
    /**
     * @param list<array{value: string, label: ?string, text: ?string, description: ?string, chosen: bool}> $values
     * @param list<string> $chosen
     * @param array<string,mixed> $definition the filter as the contract gives it
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $field,
        public array $values,
        public array $chosen,
        public bool $active,
        public array $definition,
    ) {}

    /**
     * The view of a filter, from the query of the page.
     *
     * @param array<string,mixed> $filter the filter as the contract gives it (with the values a bridge offers)
     * @param array<string,mixed> $query the query of the page
     */
    public static function of(array $filter, array $query): self
    {
        $name = (string) ($filter['name'] ?? '');
        $field = ($filter['multiple'] ?? false) ? 'multiple' : (isset($filter['format']) ? 'text' : 'select');
        $asked = $query[$name] ?? [];
        $chosen = array_values(array_filter(array_map('strval', is_array($asked) ? $asked : [$asked]), fn (string $value) => $value !== ''));
        $values = array_map(
            fn (array $value) => [
                'value' => (string) ($value['value'] ?? ''),
                'label' => isset($value['label']) ? (string) $value['label'] : null,
                'text' => isset($value['text']) ? (string) $value['text'] : null,
                'description' => isset($value['description']) ? (string) $value['description'] : null,
                'chosen' => in_array((string) ($value['value'] ?? ''), $chosen, true),
            ],
            array_values((array) ($filter['values'] ?? [])),
        );

        return new self($name, (string) ($filter['label'] ?? $name), $field, $values, $chosen, $chosen !== [], $filter);
    }
}
