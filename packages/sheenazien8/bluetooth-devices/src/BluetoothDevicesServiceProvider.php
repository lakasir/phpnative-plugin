<?php

namespace Sheenazien8\BluetoothDevices;

use Illuminate\Support\ServiceProvider;
use Sheenazien8\BluetoothDevices\Commands\CopyAssetsCommand;

class BluetoothDevicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BluetoothDevices::class, function () {
            return new BluetoothDevices;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
