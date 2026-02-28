# BluetoothDevices Plugin for NativePHP Mobile

A plugin that can fetch bluetooth devices for you nativephp

## Installation

```bash
composer require sheenazien8/bluetooth-devices
```

## Usage

```php
use Sheenazien8\BluetoothDevices\Facades\BluetoothDevices;

// Execute functionality
$result = BluetoothDevices::execute(['option1' => 'value']);

// Get status
$status = BluetoothDevices::getStatus();
```

## Listening for Events

```php
use Livewire\Attributes\On;

#[On('native:Sheenazien8\BluetoothDevices\Events\BluetoothDevicesCompleted')]
public function handleBluetoothDevicesCompleted($result, $id = null)
{
    // Handle the event
}
```

## License

MIT