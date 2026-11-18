<?php

namespace W4OS\Views;

/**
 * An item of the engine to show: its header (its name and its picture as its list shows them, its first column: `pictured` when
 * its list has one, even without one of its own; round for a person; its state, the indicators of its row in its list; the actions it is given, the safe ones first, the ones the engine asks
 * to confirm last) and its details (its groups of fields, without what the header shows).
 */
final readonly class ItemView
{
    /**
     * @param array<string,mixed> $item the item as the engine gives it
     * @param list<IndicatorView> $states
     * @param list<ActionView> $actions
     * @param list<array{label: string, fields: list<array<string,mixed>>}> $groups
     */
    public function __construct(
        public string $id,
        public array $item,
        public string $name,
        public bool $pictured,
        public ?string $picture,
        public ?string $pictureDefault,
        public bool $round,
        public array $states,
        public array $actions,
        public array $groups,
    ) {}

    /**
     * The view of an item.
     *
     * @param array<string,mixed> $list its list (Contract::listOf()): its first column names and pictures it, its indicators say its state
     * @param array<string,mixed> $item the item as the engine gives it
     * @param list<array{label: string, fields: list<array<string,mixed>>}> $groups its groups of fields a viewer sees (a bridge's)
     * @param list<array<string,mixed>> $actions the actions of the contract a viewer may do on it (Rows::actions())
     */
    public static function of(array $list, array $item, array $groups = [], array $actions = []): self
    {
        $list += ['columns' => []];
        $first = (array) ($list['columns'][0] ?? []);
        $id = Rows::id($item);
        $picture = isset($first['picture']) ? (($item[$first['picture']] ?? null) ?: null) : null;
        $default = isset($first['default']) ? (string) $first['default'] : null;
        $states = [];
        foreach (ListView::of($list, [$item], [], [])->rows[0]->indicators ?? [] as $indicators) {
            array_push($states, ...$indicators);
        }
        $views = array_map(fn (array $action) => ActionView::of($action), array_values($actions));
        usort($views, fn (ActionView $a, ActionView $b) => $a->danger <=> $b->danger);

        // What the header shows: its name, its picture, the attributes of its state
        $shown = array_filter([$first['name'] ?? null, $first['picture'] ?? null]);
        foreach ($list['columns'] as $column) {
            foreach ((array) ($column['indicators'] ?? []) as $indicator) {
                $shown[] = explode('.', (string) $indicator['name'])[0];
            }
        }
        $details = [];
        foreach ($groups as $group) {
            $fields = array_values(array_filter((array) ($group['fields'] ?? []), fn (array $field) => ! in_array($field['name'] ?? null, $shown, true)));
            $fields === [] || $details[] = ['label' => (string) ($group['label'] ?? ''), 'fields' => $fields];
        }

        return new self($id, $item, (string) (($item['name'] ?? '') ?: $id), isset($first['picture']), is_scalar($picture) ? (string) $picture : null, $default, $default === 'person', $states, $views, $details);
    }
}
