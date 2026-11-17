<?php

namespace W4OS\Views;

/**
 * Who may do an operation of the engine: the engine's own `OpenSim\Api\Access` when its library is there, else the same rule on
 * the access the contract gives (`x-access`, read by Contract::access()).
 */
final class Access
{
    /**
     * Whether a person of this level may do an operation.
     *
     * @param array{level: ?int, self: bool, operation?: string} $access
     * @param bool $own whether it is on their own account
     */
    public static function allows(array $access, int $level, bool $own = false): bool
    {
        $operation = (string) ($access['operation'] ?? '');
        if ($operation !== '' && class_exists(\OpenSim\Api\Access::class)) {
            try {
                return \OpenSim\Api\Access::allows($level, $operation, $own);
            } catch (\InvalidArgumentException) {
                // An operation of a newer engine than the library: its access as the contract gives it
            }
        }

        return self::byContract($access, $level, $own);
    }

    /**
     * The rule of Api\Access::allows() on the access the contract gives: anybody when it needs no level, on one's own account when
     * it allows it (`self`), else from its level.
     *
     * @param array{level: ?int, self: bool} $access
     */
    public static function byContract(array $access, int $level, bool $own = false): bool
    {
        return $access['level'] === null || ($own && $access['self']) || $level >= $access['level'];
    }
}
