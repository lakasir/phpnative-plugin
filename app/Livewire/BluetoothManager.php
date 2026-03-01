<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Native\Mobile\Attributes\OnNative;
use Sheenazien8\BluetoothDevices\Events\BluetoothDeviceFound;
use Sheenazien8\BluetoothDevices\Events\BluetoothPermissionsChecked;
use Sheenazien8\BluetoothDevices\Events\BluetoothScanError;
use Sheenazien8\BluetoothDevices\Events\BluetoothScanStopped;
use Sheenazien8\BluetoothDevices\Events\BluetoothStateChanged;
use Sheenazien8\BluetoothDevices\Facades\BluetoothDevices;

#[Layout('layouts.app')]
#[Title('BluetoothDevices Manager')]
class BluetoothManager extends Component
{
    public $devices = [];

    public bool $isScanning = false;

    public $connectionStatus = 'Idle';

    public $permissionStatus = 'Initializing...';

    public $errorMessage = null;

    public function mount(): void
    {
        $this->checkPermissions();
    }

    public function checkPermissions(): void
    {
        // Only show "Checking..." on initial load
        if ($this->permissionStatus === 'Initializing...') {
            $this->permissionStatus = 'Checking...';
        }
        $this->errorMessage = null;

        try {
            BluetoothDevices::checkPermissions();
        } catch (\Exception $e) {
            $this->permissionStatus = 'Error checking permissions';
            $this->errorMessage = $e->getMessage();
        }
    }

    #[OnNative(BluetoothPermissionsChecked::class)]
    public function onPermissionsChecked(
        $hasPermissions,
        $bluetoothEnabled,
        $missingPermissions = [],
        $androidVersion = 0,
    ): void {
        if (! $hasPermissions) {
            $this->permissionStatus = 'Permissions needed';
        } elseif (! $bluetoothEnabled) {
            $this->permissionStatus = 'Bluetooth off';
        } else {
            $this->permissionStatus = 'Ready';
        }

        $this->errorMessage = null;
    }

    public function requestPermissions(): void
    {
        $this->permissionStatus = 'Requesting...';
        $this->errorMessage = null;

        try {
            BluetoothDevices::requestPermissions();
        } catch (\Exception $e) {
            $this->permissionStatus = 'Error requesting';
            $this->errorMessage = $e->getMessage();
        }
    }

    public function start()
    {
        $this->isScanning = true;
        $this->devices = [];
        BluetoothDevices::startScan();
    }

    #[OnNative(BluetoothDeviceFound::class)]
    public function onDeviceFound($name, $address, $rssi = null): void
    {
        if (! collect($this->devices)->contains('address', $address)) {
            $this->devices[] = [
                'name' => $name,
                'address' => $address,
                'rssi' => $rssi,
            ];
        }
    }

    public function connect($address)
    {
        $this->connectionStatus = 'Connecting...';
        BluetoothDevices::stopScan();
        BluetoothDevices::connect($address);
    }

    #[OnNative(BluetoothStateChanged::class)]
    public function onStateChanged($address, $state): void
    {
        $this->connectionStatus = ucfirst($state);
    }

    #[OnNative(BluetoothScanError::class)]
    public function onScanError($errorCode, $message): void
    {
        $this->errorMessage = "Scan error ({$errorCode}): {$message}";
        $this->isScanning = false;
    }

    #[OnNative(BluetoothScanStopped::class)]
    public function onScanStopped($reason = 'manual', $deviceCount = 0): void
    {
        $this->isScanning = false;
        if ($reason === 'timeout') {
            $this->errorMessage = "Scan completed: found {$deviceCount} device(s)";
        }
    }

    public function disconnect()
    {
        BluetoothDevices::disconnect();
    }

    public function render()
    {
        return view('livewire.bluetooth-manager');
    }
}
