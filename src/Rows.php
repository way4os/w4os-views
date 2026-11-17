<?php

namespace W4OS\Views;

/**
 * The rules of a row of a list of the engine (its attributes as the engine gives them): which actions of the list apply to it and
 * whoever acts may do, the request of the engine that does one, a value by its dotted path, its id.
 */
final class Rows
{
    /**
     * The actions of a row: the ones whose conditions it meets and that whoever acts may do. `when`: each attribute has one of the
     * values; `requires`: each attribute has a value; `notSelf`: the row is not the avatar of whoever acts; `access`: their level
     * (Access::allows(), on their own account when the row is theirs); `level`: at least this level, when the action asks more than
     * its operation (a ban: a god).
     *
     * @param array<string,mixed> $list
     * @param array<string,mixed> $item
     * @param ?string $self the UUID of the avatar of whoever acts
     * @param ?int $level the user level of whoever acts; null: not checked
     * @return list<array<string,mixed>>
     */
    public static function actions(array $list, array $item, ?string $self = null, ?int $level = null): array
    {
        return array_values(array_filter((array) ($list['actions'] ?? []), fn (array $action) => self::applies($action, $item, $self)
            && ($level === null || ((! isset($action['access']) || Access::allows($action['access'], $level, $self !== null && self::id($item) === $self))
                && $level >= (int) ($action['level'] ?? 0)))));
    }

    /**
     * The request of the engine that does an action on a row: its method, its path (under the grid when it is the grid's), its
     * document, and whether it is asked under the grid (else of the machine: its simulators, its instances). The `{attribute}`s
     * of the path are the row's (`{id}` its id).
     *
     * @param array<string,mixed> $action an action of the list
     * @param array<string,mixed> $item the row, its attributes
     * @param array<string,mixed> $more attributes the site adds (the link of its page for a token)
     * @return array{string, string, array<string,mixed>, bool}
     */
    public static function request(array $action, array $item, array $more = []): array
    {
        $id = self::id($item);
        $scoped = preg_match('#^/grids/\{grid\}#', (string) $action['path']) === 1;
        $path = (string) preg_replace('#^/grids/\{grid\}#', '', (string) $action['path']);
        // The type of the document: the last name of the path (`avatars` in /avatars/{id}, `password-resets`, `start`)
        $names = array_values(array_filter(explode('/', $path), fn ($part) => $part !== '' && ! str_starts_with($part, '{')));
        $type = (string) ($names[count($names) - 1] ?? '');
        $attributes = array_map(fn ($value) => self::filled($value, $item), (array) ($action['body'] ?? [])) + $more;
        $data = ['type' => $type] + (str_contains($path, '{id}') ? ['id' => $id] : []) + ['attributes' => $attributes];
        $path = (string) preg_replace_callback('/\{(\w+)\}/', fn ($m) => rawurlencode($m[1] === 'id' ? $id : (string) ($item[$m[1]] ?? '')), $path);

        return [strtoupper((string) $action['method']), $path, $data, $scoped];
    }

    /** A value of a row by its name, or its dotted path into an object of the row (`connections.console`); null when it has none. */
    public static function value(array $item, string $path): mixed
    {
        $value = $item;
        foreach (explode('.', $path) as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /** The id of a row: its UUID, as the engine gives it in the attributes. */
    public static function id(array $item): string
    {
        return (string) ($item['uuid'] ?? $item['id'] ?? '');
    }

    /** @param array<string,mixed> $action */
    private static function applies(array $action, array $item, ?string $self): bool
    {
        if (($action['notSelf'] ?? false) && $self !== null && self::id($item) === $self) {
            return false;
        }
        foreach ((array) ($action['requires'] ?? []) as $attribute) {
            $value = self::value($item, (string) $attribute);
            if ($value === null || $value === '' || $value === []) {
                return false;
            }
        }
        foreach ((array) ($action['when'] ?? []) as $attribute => $values) {
            $value = self::value($item, (string) $attribute);
            $met = array_filter((array) $values, fn ($expected) => is_bool($expected) ? $value === $expected : $value !== null && (string) $value === (string) $expected);
            if ($met === []) {
                return false;
            }
        }

        return true;
    }

    /** A value of the body of an action: `{attribute}` replaced by the row's (the value itself when it is the whole string). */
    private static function filled(mixed $value, array $item): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        if (preg_match('/^\{(\w+)\}$/', $value, $m) === 1) {
            return $item[$m[1]] ?? null;
        }

        return (string) preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($item[$m[1]] ?? ''), $value);
    }
}
