<?php

namespace Sheenazien8\BluetoothDevices\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed execute(array $options = [])
 * @method static object|null getStatus()
 *
 * @see \Sheenazien8\BluetoothDevices\BluetoothDevices
 */
class BluetoothDevices extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Sheenazien8\BluetoothDevices\BluetoothDevices::class;
    }
}