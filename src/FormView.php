<?php

namespace W4OS\Views;

/**
 * The form of an item of the engine for a viewer, from its description (Contract::itemOf()): who the viewer is to it (its roles:
 * manager of the grid, self when the item is its avatar, owner), its groups with the fields the viewer sees (their condition and
 * access), the fields it changes and their values, the ones it may not change (`locked`), and what a posted form changes. Each
 * bridge draws its own inputs from the fields.
 */
final readonly class FormView
{
    /**
     * @param list<string> $roles
     * @param list<array<string,mixed>> $groups each group with the fields the viewer sees (`fields`)
     * @param list<array<string,mixed>> $fields the fields the viewer sees and changes, in their order
     * @param list<string> $locked the names of the fields the viewer may not change
     * @param array<string,mixed> $values the id of the item and the values of the fields the viewer changes
     * @param array<string,mixed> $form the description of the item
     * @param array<string,mixed> $item
     */
    public function __construct(
        public array $roles,
        public array $groups,
        public array $fields,
        public array $locked,
        public bool $changeable,
        public array $values,
        public array $form,
        public array $item,
        public int $level,
    ) {}

    /**
     * The form of an item for a viewer.
     *
     * @param array<string,mixed> $form the item's description (Contract::itemOf())
     * @param array<string,mixed> $item
     * @param ?string $self the UUID of the avatar of the viewer
     * @param bool $manager whether the viewer manages the grid
     * @param int $level the user level of the viewer
     */
    public static function of(array $form, array $item, ?string $self, bool $manager, int $level = 0): self
    {
        $form += ['groups' => [], 'fields' => []];
        $roles = self::roles($item, $self, $manager);
        $groups = [];
        foreach ($form['groups'] as $group) {
            $fields = array_values(array_filter($form['fields'], fn ($field) => ($field['group'] ?? null) === $group['name'] && self::visible($field, $item, $roles)));
            $fields === [] || $groups[] = $group + ['fields' => $fields];
        }
        $changed = array_values(array_filter($form['fields'], fn ($field) => self::visible($field, $item, $roles) && self::editable($field, $roles, $level)));
        $values = ['id' => Rows::id($item)];
        foreach ($form['fields'] as $field) {
            if (self::editable($field, $roles, $level) && ($field['type'] ?? '') !== 'secret' && empty($field['writeOnly'])) {
                $value = $item[$field['name']] ?? null;
                $values[$field['name']] = match ($field['type'] ?? '') {
                    'boolean' => (bool) $value,
                    'checkboxes' => self::checked($value ?? []),
                    default => $value === null ? '' : $value,
                };
            }
        }

        return new self($roles, $groups, $changed, self::locked($form, $roles, $level), $changed !== [], $values, $form, $item, $level);
    }

    /**
     * What a posted form changes: the fields the viewer changes whose value is not the item's (a password only when one is typed, a
     * box left unchecked when the engine does not know the value).
     *
     * @param array<string,mixed> $posted the data posted
     * @return array<string,mixed>
     */
    public function changes(array $posted): array
    {
        $changes = [];
        foreach ($this->fields as $field) {
            $name = $field['name'];
            $before = $this->item[$name] ?? null;
            $value = match ($field['type'] ?? '') {
                'boolean' => (bool) ($posted[$name] ?? false),
                'checkboxes' => self::checked($posted[$name] ?? []),
                'integer' => ($posted[$name] ?? '') === '' ? null : (int) $posted[$name],
                default => trim((string) ($posted[$name] ?? '')),
            };
            $same = match ($field['type'] ?? '') {
                'boolean' => $before === null ? $value === false : $value === (bool) $before,
                'checkboxes' => $value === self::checked($before ?? []),
                'secret' => $value === '',
                default => (string) $value === (string) ($before ?? ''),
            };
            $same || $changes[$name] = $value;
        }

        return $changes;
    }

    /**
     * The roles of a viewer for an item: manager (of the grid), self (the item is its avatar), owner (the item is its).
     *
     * @param array<string,mixed> $item
     * @return list<string>
     */
    public static function roles(array $item, ?string $self, bool $manager): array
    {
        return array_values(array_filter([
            $manager ? 'manager' : null,
            $self !== null && Rows::id($item) === $self ? 'self' : null,
            $self !== null && ($item['owner'] ?? null) === $self ? 'owner' : null,
        ]));
    }

    /**
     * Whether a field shows for an item: its condition met (each attribute has one of the values), and seen by one of the roles
     * (`access.view`; whoever sees the item without it).
     *
     * @param array<string,mixed> $field
     * @param array<string,mixed> $item
     * @param list<string> $roles
     */
    public static function visible(array $field, array $item, array $roles): bool
    {
        foreach ((array) ($field['condition'] ?? []) as $attribute => $values) {
            $value = $item[$attribute] ?? null;
            $met = array_filter((array) $values, fn ($expected) => is_bool($expected) ? $value === $expected : $value !== null && (string) $value === (string) $expected);
            if ($met === []) {
                return false;
            }
        }

        return ! isset($field['access']['view']) || array_intersect((array) $field['access']['view'], $roles) !== [];
    }

    /**
     * Whether a field is changed by the viewer: not read only, and one of its roles is in `access.edit` (the managers without it); a
     * manager from the level of the field (`access.level`, the contract's), the item's own viewer or owner without one.
     *
     * @param array<string,mixed> $field
     * @param list<string> $roles
     */
    public static function editable(array $field, array $roles, int $level = 0): bool
    {
        $by = array_intersect((array) ($field['access']['edit'] ?? ['manager']), $roles);
        if (! empty($field['readOnly']) || $by === []) {
            return false;
        }

        return array_diff($by, ['manager']) !== [] || $level >= (int) ($field['access']['level'] ?? PHP_INT_MAX);
    }

    /**
     * The fields of an item a viewer of these roles may not change (editable()), whatever they see: what a form leaves out, and what
     * it never sends for them.
     *
     * @param array<string,mixed> $form the item's description (Contract::itemOf())
     * @param list<string> $roles
     * @return list<string>
     */
    public static function locked(array $form, array $roles, int $level): array
    {
        return array_values(array_map(fn ($field) => (string) $field['name'], array_filter((array) ($form['fields'] ?? []), fn ($field) => ! self::editable($field, $roles, $level))));
    }

    /**
     * The names of the choices a form posts: unique, sorted, none empty.
     *
     * @return list<string>
     */
    public static function checked(mixed $list): array
    {
        $names = array_values(array_unique(array_filter(array_map('strval', (array) $list), fn (string $name) => $name !== '')));
        sort($names);

        return $names;
    }
}
