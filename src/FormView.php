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
     * @param list<array<string,mixed>> $shown the fields the viewer sees and no one changes here (`readOnly`: what the options of the grid set)
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
        public array $shown = [],
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
        // A field whose condition is on the fields the viewer changes is in the form whatever their values now: what is chosen shows it;
        // so is one whose condition is also on what the options set (shown with them), when the item meets that part now (Provide,
        // and the module of the economy)
        $editable = array_column(array_filter($form['fields'], fn ($field) => self::editable($field, $roles, $level)), 'name');
        $fixed = array_column(array_filter($form['fields'], fn ($field) => ! empty($field['readOnly'])), 'name');
        $reachable = fn (array $field) => self::live($field, $editable) || (self::live($field, [...$editable, ...$fixed])
            && array_intersect(array_keys((array) $field['condition']), $editable) !== []
            && self::met(['condition' => array_intersect_key((array) $field['condition'], array_flip($fixed))], $item));
        $changed = array_values(array_filter($form['fields'], fn ($field) => self::editable($field, $roles, $level)
            && (self::visible($field, $item, $roles) || ($reachable($field) && self::visible(['condition' => []] + $field, $item, $roles)))));
        $values = ['id' => Rows::id($item)];
        foreach ($form['fields'] as $field) {
            if (self::editable($field, $roles, $level) && ($field['type'] ?? '') !== 'secret' && empty($field['writeOnly'])) {
                $value = $item[$field['name']] ?? null;
                $values[$field['name']] = match ($field['type'] ?? '') {
                    'boolean' => (bool) $value,
                    'checkboxes' => self::checked($value ?? []),
                    // Its default (a database the grid has without it): no value of its own
                    'connection-string' => self::parts($field, $value) + (isset($field['fallback']) ? ['useDefault' => $value === null] : []),
                    default => $value === null ? '' : $value,
                };
            }
        }

        // What the options set, shown; with a condition on the fields the viewer changes, whatever their values now
        $shown = array_values(array_filter($form['fields'], fn ($field) => ! empty($field['readOnly'])
            && (self::visible($field, $item, $roles) || ($reachable($field) && self::visible(['condition' => []] + $field, $item, $roles)))));

        return new self($roles, $groups, $changed, self::locked($form, $roles, $level), $changed !== [], $values, $form, $item, $level, $shown);
    }

    /**
     * What a posted form changes: the fields the viewer changes whose value is not the item's (a password only when one is typed, a
     * box left unchecked when the engine does not know the value), the ones whose condition the values posted do not meet left out.
     *
     * @param array<string,mixed> $posted the data posted
     * @return array<string,mixed>
     */
    public function changes(array $posted): array
    {
        $changes = [];
        // The conditions met by the values posted, the item's for what is not posted
        $values = array_intersect_key($posted, array_flip(array_column($this->fields, 'name'))) + $this->item;
        foreach ($this->fields as $field) {
            $name = $field['name'];
            if (! self::met($field, $values)) {
                continue;
            }
            $before = $this->item[$name] ?? null;
            // A database: its parts, its password only when one is typed
            if (($field['type'] ?? '') === 'connection-string') {
                // Its default chosen: no parts of its own
                if (isset($field['fallback']) && self::met(['condition' => ['useDefault' => [true]]], (array) ($posted[$name] ?? []))) {
                    $before === null || $changes[$name] = ['useDefault' => true];
                    continue;
                }
                $value = self::parts($field, (array) ($posted[$name] ?? []), true);
                if ($before === null && isset($field['fallback'])) {
                    $changes[$name] = $value;
                    continue;
                }
                $value === self::parts($field, $before) && ! isset($value['password']) || $changes[$name] = $value;
                continue;
            }
            // A field of several values (the avatars of a list): a list of strings, the same in any order
            if (! in_array($field['type'] ?? '', ['checkboxes', 'boolean'], true) && (! empty($field['multiple']) || is_array($posted[$name] ?? null))) {
                $value = self::listOf($posted[$name] ?? []);
                $sorted = $value;
                $was = self::listOf($before ?? []);
                sort($sorted);
                sort($was);
                $sorted === $was || $changes[$name] = $value;
                continue;
            }
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
     * The parts of a database (a field `connection-string`, its `fields`) as a form has them: each as its type, its password left
     * out, or the one typed when it is asked (`$typed`) and one is.
     *
     * @param array<string,mixed> $field
     * @return array<string,mixed>
     */
    private static function parts(array $field, mixed $value, bool $typed = false): array
    {
        $value = is_array($value) ? $value : [];
        $parts = [];
        foreach ((array) ($field['fields'] ?? []) as $part) {
            $name = (string) ($part['name'] ?? '');
            $given = $value[$name] ?? null;
            if (($part['type'] ?? '') === 'secret') {
                $typed && trim((string) $given) !== '' && $parts[$name] = trim((string) $given);
                continue;
            }
            $parts[$name] = match ($part['type'] ?? '') {
                'integer' => $given === null || $given === '' ? null : (int) $given,
                default => trim((string) ($given ?? '')),
            };
        }

        return $parts;
    }

    /**
     * The values of a field of several: its strings, the empty ones left out (one value alone counts as a list of one).
     *
     * @return list<string>
     */
    private static function listOf(mixed $values): array
    {
        $values = is_array($values) ? $values : ($values === null || $values === '' ? [] : [$values]);

        return array_values(array_filter(array_map(fn ($value) => trim((string) $value), $values), fn (string $value) => $value !== ''));
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
        return self::met($field, $item) && (! isset($field['access']['view']) || array_intersect((array) $field['access']['view'], $roles) !== []);
    }

    /**
     * Whether the condition of a field is met by values (an item's, or a form's): each attribute has one of its values.
     *
     * @param array<string,mixed> $field
     * @param array<string,mixed> $values
     */
    public static function met(array $field, array $values): bool
    {
        foreach ((array) ($field['condition'] ?? []) as $attribute => $expected) {
            $value = $values[$attribute] ?? null;
            // A value of several (a list) meets it with one of them
            $value = is_array($value) ? (array_values(array_intersect(array_map('strval', array_filter($value, 'is_scalar')), array_map('strval', (array) $expected)))[0] ?? null) : $value;
            // A box: checked as a form sends it ('1', 'on', 'true'), else unchecked, left out included
            $checked = $value === true || in_array(is_scalar($value) ? strtolower((string) $value) : '', ['1', 'on', 'true'], true);
            if (array_filter((array) $expected, fn ($one) => is_bool($one) ? $checked === $one : $value !== null && (string) $value === (string) $one) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the condition of a field is on fields of the form alone (the ones the viewer changes): a site shows or hides it as
     * they are changed.
     *
     * @param array<string,mixed> $field
     * @param list<string> $names the fields the viewer changes
     */
    public static function live(array $field, array $names): bool
    {
        $on = array_keys((array) ($field['condition'] ?? []));

        return $on !== [] && array_diff($on, $names) === [];
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
