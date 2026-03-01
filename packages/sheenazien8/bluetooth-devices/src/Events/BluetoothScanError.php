<?php

namespace Sheenazien8\BluetoothDevices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BluetoothScanError
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $errorCode,
        public string $message
    ) {}
}
