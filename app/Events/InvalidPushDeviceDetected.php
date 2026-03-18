<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class InvalidPushDeviceDetected
{
    use Dispatchable;

    /**
     * @param  array<string>  $providerDeviceIds
     */
    public function __construct(
        public readonly array $providerDeviceIds,
    ) {}
}
