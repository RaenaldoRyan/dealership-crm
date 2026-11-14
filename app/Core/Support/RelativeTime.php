<?php

namespace App\Core\Support;

use Carbon\CarbonInterface;

class RelativeTime
{
    /**
     * A clock time for today, "Yesterday", or a phrase such as "4 days ago".
     */
    public static function format(CarbonInterface $at): string
    {
        $at = $at->copy()->timezone((string) config('app.timezone'));

        if ($at->isToday()) {
            return $at->format('g:i A');
        }

        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        return $at->diffForHumans();
    }
}
