<?php

namespace W4OS\Views;

/**
 * The message a bridge says once an action is done: the key of the engine's catalog (`estateBan`: `manage_estate_ban`), by the
 * engine's `OpenSim\I18n\Catalog::doneKey()` when its library is there, else by the same rule.
 */
final class Done
{
    /** The key of the message once an action is done. */
    public static function key(string $action): string
    {
        return method_exists(\OpenSim\I18n\Catalog::class, 'doneKey') ? \OpenSim\I18n\Catalog::doneKey($action) : self::byRule($action);
    }

    /** The rule of Catalog::doneKey(): `manage_` and the name of the action in snake case. */
    public static function byRule(string $action): string
    {
        return 'manage_'.strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $action));
    }
}
