<?php

namespace W4OS\Views;

/**
 * The contract of the engine (its OpenAPI document), as a bridge reads it to show the lists and the items of its models: what the
 * list operation of a model (`GET /grids/{grid}/<type>`) says in its parameters (search, sort, page, `filter[...]` and their
 * values) and in its `x-list` (the columns in their order with their display type and the catalog key of their label, the sorts,
 * the filters and the keys of their values, the fields the search looks in, the actions of a row: their operation and when they
 * apply); what the operation that reads an item says in its `x-form`; who may do each operation (`x-access`).
 */
final class Contract
{
    /** @param array<string,mixed> $document the document of the engine (`/openapi.json`, or `OpenSim\Api\Contract::document()`) */
    public function __construct(private array $document) {}

    /**
     * The list of a type of the engine (avatars, regions...), null when the engine has no list of it. Its `x-list` as the engine
     * gives it, completed from the parameters of the operation when it has none: the filters with values (their enums), the sorts
     * (the enum of `sort`), the search (a `search` parameter).
     *
     * @return ?array{type: string, columns: list<array<string,mixed>>, sort: array{default: string, values: list<string>,
     *     descending: string}, search: ?list<string>, filters: list<array<string,mixed>>, actions: list<array<string,mixed>>,
     *     pageSize: ?int}
     */
    public function listOf(string $type): ?array
    {
        $operation = $this->listOperation($type);
        if ($operation === null) {
            return null;
        }
        $parameters = [];
        foreach ((array) ($operation['parameters'] ?? []) as $parameter) {
            $parameter = $this->resolve((array) $parameter);
            isset($parameter['name']) && $parameters[(string) $parameter['name']] = $parameter;
        }
        $x = (array) ($operation['x-list'] ?? []);
        $columns = array_map(
            fn (array $column) => $column + ['label' => 'column_'.$column['name'], 'type' => 'text'],
            array_values(array_filter((array) ($x['columns'] ?? []), fn ($column) => isset($column['name']))),
        );
        $sorts = array_values(array_unique(array_map(fn ($sort) => ltrim((string) $sort, '-'), (array) ($parameters['sort']['schema']['enum'] ?? []))));
        $sort = (array) ($x['sort'] ?? []) + ['default' => '', 'values' => $sorts, 'descending' => '-'];

        return [
            'type' => $type,
            'columns' => $columns,
            'sort' => ['default' => (string) $sort['default'], 'values' => array_values(array_map('strval', (array) $sort['values'])), 'descending' => (string) $sort['descending']],
            'search' => isset($parameters['search']) ? array_values((array) ($x['search'] ?? [])) : null,
            'filters' => isset($x['filters']) ? $this->filtersOf((array) $x['filters']) : $this->filters($parameters),
            'actions' => $this->withAccess((array) ($x['actions'] ?? [])),
            'read' => $this->access('GET', $this->listPath($type)),
            // A list of the grid (/grids/{grid}/...), else of the machine (its simulators)
            'scoped' => str_starts_with($this->listPath($type), '/grids/{grid}'),
            'pageSize' => isset($parameters['page[size]']['schema']['default']) ? (int) $parameters['page[size]']['schema']['default'] : null,
        ];
    }

    /**
     * The page of an item of a type (avatars, regions...), as its `x-form` describes it: the groups of its fields, its fields in
     * their order (FieldSet entries: name, label, type, group, readOnly, options, condition, access...), its actions. Null when the
     * engine describes none.
     *
     * @return ?array{type: string, groups: list<array{name: string, label: string}>, fields: list<array<string,mixed>>, actions: list<array<string,mixed>>}
     */
    public function itemOf(string $type): ?array
    {
        $x = null;
        $path = '';
        foreach ((array) ($this->document['paths'] ?? []) as $candidate => $item) {
            if (preg_match('#^(/grids/\{grid\})?/'.preg_quote($type, '#').'/\{id\}$#', (string) $candidate) === 1 && isset($item['get']['x-form'])) {
                $x = (array) $item['get']['x-form'];
                $path = (string) $candidate;
            }
        }
        if ($x === null) {
            return null;
        }
        $groups = array_values(array_map(fn ($group) => ['name' => (string) $group['name'], 'label' => (string) ($group['label'] ?? 'group_'.$group['name'])], array_filter((array) ($x['groups'] ?? []), fn ($group) => isset($group['name']))));
        // Who changes the item (its PATCH), and each field: its own level, else the one of the change
        $change = isset($this->document['paths'][$path]['patch']) ? $this->access('PATCH', $path) : null;
        $fields = array_values(array_map(function ($field) use ($groups, $change) {
            $field += ['label' => 'field_'.$field['name'], 'type' => 'text', 'group' => $groups[0]['name'] ?? ''] + $this->valuesOf($field);
            empty($field['readOnly']) && $change !== null && ! isset($field['access']['level']) && $field['access']['level'] = $change['level'];

            return $field;
        }, array_filter((array) ($x['fields'] ?? []), fn ($field) => isset($field['name']))));

        return [
            'type' => $type,
            'groups' => $groups,
            'fields' => $fields,
            'actions' => $this->withAccess((array) ($x['actions'] ?? [])),
            'read' => $this->access('GET', $path),
            'change' => $change,
            'scoped' => str_starts_with($path, '/grids/{grid}'),
        ];
    }

    /**
     * A form of settings of a grid (`/grids/{grid}/settings/{group}`, read with GET, changed with PATCH), as its `x-form` describes
     * it: its title and the key of its button, the keys of the points of its help (`description`), the list of what it picks
     * (`preview`: its label, what it says when empty, its path, the fields given as its filters), its fields as the ones of an item
     * (values, conditions, who changes each), who reads and who changes it. Null when the engine describes none.
     *
     * @return ?array{group: string, title: string, submit: string, description: list<string>, preview: ?array{label: string, empty: string, path: string, filters: list<string>}, fields: list<array<string,mixed>>, read: array<string,mixed>, change: ?array<string,mixed>, scoped: bool}
     */
    public function settingsOf(string $group): ?array
    {
        $x = null;
        $path = '';
        foreach ((array) ($this->document['paths'] ?? []) as $candidate => $item) {
            if (preg_match('#^(/grids/\{grid\})?/settings/'.preg_quote($group, '#').'$#', (string) $candidate) === 1 && isset($item['get']['x-form'])) {
                $x = (array) $item['get']['x-form'];
                $path = (string) $candidate;
            }
        }
        if ($x === null) {
            return null;
        }
        $change = isset($this->document['paths'][$path]['patch']) ? $this->access('PATCH', $path) : null;
        $fields = array_values(array_map(function ($field) use ($change) {
            $field += ['label' => 'field_'.$field['name'], 'type' => 'text', 'group' => ''] + $this->valuesOf($field);
            $change !== null && ! isset($field['access']['level']) && $field['access']['level'] = $change['level'];

            return $field;
        }, array_filter((array) ($x['fields'] ?? []), fn ($field) => isset($field['name']))));

        return [
            'group' => $group,
            'title' => (string) ($x['title'] ?? 'settings_'.$group),
            'submit' => (string) ($x['submit'] ?? 'action_save'),
            'description' => array_values(array_map('strval', (array) ($x['description'] ?? []))),
            'preview' => isset($x['preview']['path']) ? (array) $x['preview'] + ['label' => '', 'empty' => '', 'filters' => []] : null,
            'fields' => $fields,
            'read' => $this->access('GET', $path),
            'change' => $change,
            'scoped' => str_starts_with($path, '/grids/{grid}'),
        ];
    }

    /**
     * The stream of an avatar, as the operation that writes a post describes it (`x-form`): its fields, the key of its button, the
     * actions of a post with who may do each, who may write one (`write`). Null when the engine describes none.
     *
     * @return ?array{fields: list<array<string,mixed>>, submit: string, actions: list<array<string,mixed>>, write: array<string,mixed>, path: string, scoped: bool}
     */
    public function postsOf(): ?array
    {
        foreach ((array) ($this->document['paths'] ?? []) as $path => $item) {
            if (preg_match('#^(/grids/\{grid\})?/avatars/\{id\}/posts$#', (string) $path) === 1 && isset($item['post']['x-form'])) {
                $x = (array) $item['post']['x-form'];
                $fields = array_values(array_map(fn ($field) => $field + ['label' => 'field_'.$field['name'], 'type' => 'text'], array_filter((array) ($x['fields'] ?? []), fn ($field) => isset($field['name']))));

                return [
                    'fields' => $fields,
                    'submit' => (string) ($x['submit'] ?? 'action_post'),
                    'actions' => $this->withAccess((array) ($x['actions'] ?? [])),
                    'write' => $this->access('POST', (string) $path),
                    'path' => (string) $path,
                    'scoped' => str_starts_with((string) $path, '/grids/{grid}'),
                ];
            }
        }

        return null;
    }

    /**
     * The filters of an x-list: a choice among values (several when `multiple`; the useful ones, when `dynamic`, in the meta of
     * the list: `available`), or a free value (`format`).
     *
     * @param array<int,mixed> $filters
     * @return list<array{name: string, label: string, values: list<array{value: string, label: string}>, format?: string}>
     */
    private function filtersOf(array $filters): array
    {
        $all = [];
        foreach ($filters as $filter) {
            if (! isset($filter['name'])) {
                continue;
            }
            $values = array_map(fn ($value) => ['value' => (string) ($value['value'] ?? ''), 'label' => (string) ($value['label'] ?? '')]
                + (isset($value['description']) ? ['description' => (string) $value['description']] : []), (array) ($filter['values'] ?? []));
            $all[] = ['name' => (string) $filter['name'], 'label' => (string) ($filter['label'] ?? 'filter_'.$filter['name']), 'values' => $values]
                + (isset($filter['format']) ? ['format' => (string) $filter['format']] : [])
                + (! empty($filter['multiple']) ? ['multiple' => true] : [])
                + (! empty($filter['dynamic']) ? ['dynamic' => true] : []);
        }

        return $all;
    }

    /**
     * The filters of a list without an x-list: every `filter[...]` parameter with values (its enum).
     *
     * @param array<string,array<string,mixed>> $parameters
     * @return list<array{name: string, label: string, values: list<array{value: string, label: string}>}>
     */
    private function filters(array $parameters): array
    {
        $filters = [];
        foreach ($parameters as $name => $parameter) {
            $values = (array) ($parameter['schema']['enum'] ?? []);
            if (preg_match('/^filter\[(.+)\]$/', $name, $m) === 1 && $values !== []) {
                $filters[] = ['name' => $m[1], 'label' => 'filter_'.$m[1], 'values' => array_map(fn ($value) => ['value' => (string) $value, 'label' => ''], array_values($values))];
            }
        }

        return $filters;
    }

    /**
     * The labels of the values of a field with choices (`options`: value => key) as the values of a column (value, label), so its
     * value is shown by its label too.
     *
     * @param array<string,mixed> $field
     * @return array{values?: list<array{value: string, label: string}>}
     */
    private function valuesOf(array $field): array
    {
        return isset($field['values']) || ! isset($field['options'])
            ? []
            : ['values' => array_map(fn ($value, $label) => ['value' => (string) $value, 'label' => (string) $label], array_keys((array) $field['options']), array_values((array) $field['options']))];
    }

    /**
     * Who may do an operation, as the contract says (`x-access`: the lowest level on what is not one's own, whether one may on
     * one's own account); an engine that does not say it yet: its rule (Api\Access::of()).
     *
     * @return array{level: ?int, self: bool, operation: string}
     */
    public function access(string $method, string $path): array
    {
        $operation = (array) ($this->document['paths'][$path][strtolower($method)] ?? []);
        $id = ['operation' => (string) ($operation['operationId'] ?? '')];
        if (isset($operation['x-access']) && is_array($operation['x-access'])) {
            $level = $operation['x-access']['level'] ?? null;

            return ['level' => $level === null ? null : (int) $level, 'self' => (bool) ($operation['x-access']['self'] ?? false)] + $id;
        }

        return (class_exists(\OpenSim\Api\Access::class)
            ? \OpenSim\Api\Access::of($method, $path, $id['operation'])
            : ['level' => PHP_INT_MAX, 'self' => false]) + $id;
    }

    /**
     * Who may change what belongs to the site, not to the engine (`settings`: its own settings, its theme...), as the contract says
     * it (`x-site`); an engine that does not say it yet: its rule (Api\Access::site()); nobody else.
     *
     * @return array{level: ?int, self: bool}
     */
    public function siteAccess(string $name): array
    {
        $site = $this->document['x-site'] ?? (class_exists(\OpenSim\Api\Access::class) && method_exists(\OpenSim\Api\Access::class, 'site') ? \OpenSim\Api\Access::site() : []);
        $access = (array) ($site[$name] ?? []);

        return isset($access['level']) || array_key_exists('level', $access)
            ? ['level' => $access['level'] === null ? null : (int) $access['level'], 'self' => (bool) ($access['self'] ?? false)]
            : ['level' => PHP_INT_MAX, 'self' => false];
    }

    /**
     * The actions of a list or an item, each with who may do it (`access`).
     *
     * @param array<mixed> $actions
     * @return list<array<string,mixed>>
     */
    private function withAccess(array $actions): array
    {
        $actions = array_values(array_filter($actions, fn ($action) => isset($action['name'], $action['method'], $action['path'])));

        return array_map(fn ($action) => $action + ['access' => $this->access((string) $action['method'], (string) $action['path'])], $actions);
    }

    /** The path of the list of a type in the document. */
    private function listPath(string $type): string
    {
        foreach (array_keys((array) ($this->document['paths'] ?? [])) as $path) {
            if (preg_match('#^(/grids/\{grid\})?/'.preg_quote($type, '#').'$#', (string) $path) === 1) {
                return (string) $path;
            }
        }

        return '';
    }

    /** @return ?array<string,mixed> the list operation of a type: `GET` on `/grids/{grid}/<type>` or `/<type>` */
    private function listOperation(string $type): ?array
    {
        foreach ((array) ($this->document['paths'] ?? []) as $path => $item) {
            if (preg_match('#^(/grids/\{grid\})?/'.preg_quote($type, '#').'$#', (string) $path) === 1 && isset($item['get'])) {
                return (array) $item['get'];
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $object
     * @return array<string,mixed> a part of the document, its `$ref` followed (`#/components/...`)
     */
    private function resolve(array $object): array
    {
        $ref = (string) ($object['$ref'] ?? '');
        if (! str_starts_with($ref, '#/')) {
            return $object;
        }
        $node = $this->document;
        foreach (explode('/', substr($ref, 2)) as $part) {
            $node = (array) ($node[$part] ?? []);
        }

        return $node;
    }
}
