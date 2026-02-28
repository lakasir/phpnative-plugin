<?php

namespace Sheenazien8\BluetoothDevices;

use RuntimeException;

class BluetoothDevices
{
    public function checkPermissions(): void
    {
        $this->callNative('BluetoothDevices.CheckPermissions', []);
    }

    public function requestPermissions(): void
    {
        $this->callNative('BluetoothDevices.RequestPermissions', []);
    }

    public function startScan(): void
    {
        $this->callNative('BluetoothDevices.StartScan', []);
    }

    public function stopScan(): void
    {
        $this->callNative('BluetoothDevices.StopScan', []);
    }

    public function connect(string $address): void
    {
        $this->callNative('BluetoothDevices.Connect', ['address' => $address]);
    }

    public function disconnect(): void
    {
        $this->callNative('BluetoothDevices.Disconnect', []);
    }

    /**
     * Execute the plugin functionality
     */
    public function execute(array $options = []): mixed
    {
        if (function_exists('nativephp_call')) {
            $result = nativephp_call('BluetoothDevices.Execute', json_encode($options));

            if ($result) {
                $decoded = json_decode($result);

                return $decoded->data ?? null;
            }
        }

        return null;
    }

    /**
     * Get the current status
     */
    public function getStatus(): ?object
    {
        if (function_exists('nativephp_call')) {
            $result = nativephp_call('BluetoothDevices.GetStatus', '{}');

            if ($result) {
                $decoded = json_decode($result);

                return $decoded->data ?? null;
            }
        }

        return null;
    }

    private function callNative(string $method, array $payload): array
    {
        if (! function_exists('nativephp_call')) {
            throw new RuntimeException("{$method}: nativephp_call is not available");
        }

        $jsonPayload = json_encode($payload);

        if (! is_string($jsonPayload)) {
            throw new RuntimeException("{$method}: failed to encode payload");
        }

        $raw = call_user_func('nativephp_call', $method, $jsonPayload);

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if ($raw === null || $raw === '') {
            throw new RuntimeException("{$method}: empty bridge response");
        }

        throw new RuntimeException("{$method}: unexpected bridge response");
    }
}
