<?php

use App\Core\Support\RelativeTime;

it('formats today as a clock time', function () {
    $this->travelTo('2026-11-14 10:30:00');

    expect(RelativeTime::format(now()))->toBe('10:30 AM');
});

it('formats yesterday as Yesterday', function () {
    $this->travelTo('2026-11-14 10:30:00');

    expect(RelativeTime::format(now()->subDay()))->toBe('Yesterday');
});

it('formats an older time as a relative phrase', function () {
    $this->travelTo('2026-11-14 10:30:00');

    expect(RelativeTime::format(now()->subDays(4)))->toBe('4 days ago');
});
