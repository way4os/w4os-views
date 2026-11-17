<?php

use OpenSim\Models\Avatar;
use W4OS\Views\Access;
use W4OS\Views\Contract;

describe('who may do an operation', function () {
    test('the engine\'s Access when it is there, the same rule read in the contract without it', function () {
        $contract = new Contract(contract());
        foreach (contract()['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (! is_array($operation) || ! isset($operation['operationId'], $operation['x-access'])) {
                    continue;
                }
                $access = $contract->access($method, $path);
                foreach (array_keys(Avatar::LEVELS) as $level) {
                    foreach ([false, true] as $own) {
                        $engine = OpenSim\Api\Access::allows($level, $operation['operationId'], $own);
                        expect(Access::byContract($access, $level, $own))->toBe($engine, "{$operation['operationId']} at $level")
                            ->and(Access::allows($access, $level, $own))->toBe($engine);
                    }
                }
            }
        }
    });

    test('anybody when it needs no level, on one\'s own account when it allows it, else from its level', function () {
        expect(Access::byContract(['level' => null, 'self' => false], 0))->toBeTrue()
            ->and(Access::byContract(['level' => 100, 'self' => true], 0, true))->toBeTrue()
            ->and(Access::byContract(['level' => 100, 'self' => false], 0, true))->toBeFalse()
            ->and(Access::byContract(['level' => 200, 'self' => false], 150))->toBeFalse()
            ->and(Access::allows(['level' => 200, 'self' => false, 'operation' => 'aNewerOperation'], 200))->toBeTrue();
    });
});
