<?php

use App\Inventory\Support\Registration;

test('normalises and formats uk plates', function () {
    expect(Registration::normalize(' ab22 cde '))->toBe('AB22CDE')
        ->and(Registration::format('AB22CDE'))->toBe('AB22 CDE')
        ->and(Registration::isValid('AB22 CDE'))->toBeTrue()
        ->and(Registration::format('A123BCD'))->toBe('A123 BCD')
        ->and(Registration::isValid('A123 BCD'))->toBeTrue()
        ->and(Registration::format('ABC123D'))->toBe('ABC 123D')
        ->and(Registration::isValid('ABC123D'))->toBeTrue()
        ->and(Registration::format('ABC123'))->toBe('ABC 123')
        ->and(Registration::format('123ABC'))->toBe('123 ABC')
        ->and(Registration::format('OWN1'))->toBe('OWN 1')
        ->and(Registration::isValid('OWN1'))->toBeTrue()
        ->and(Registration::format('HELLO'))->toBe('HELLO')
        ->and(Registration::isValid('HELLO'))->toBeFalse()
        ->and(Registration::isValid('NOTAPLATE'))->toBeFalse();
});
