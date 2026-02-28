package com.sheenazien8.plugins.bluetooth_devices

import android.bluetooth.*
import android.bluetooth.le.ScanCallback
import android.bluetooth.le.ScanResult
import android.content.Context
import com.nativephp.mobile.bridge.Bridge
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import com.nativephp.mobile.event.NativeEvent

object BluetoothDevicesFunctions {
    class StartScan(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
            val scanner = adapter.bluetoothLeScanner

            BluetoothState.scannerCallback = object : ScanCallback() {
                override fun onScanResult(callbackType: Int, result: ScanResult) {
                    Bridge.dispatchEvent(NativeEvent("bluetooth.device_found", mapOf(
                        "name" to (result.device.name ?: "Unknown"),
                        "address" to result.device.address
                    )))
                }
            }
            scanner.startScan(BluetoothState.scannerCallback)
            return BridgeResponse.success(mapOf("status" to "scanning"))
        }
    }

    class Connect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val address = parameters["address"] as String
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
            val device = adapter.getRemoteDevice(address)

            BluetoothState.activeGatt = device.connectGatt(context, false, object : BluetoothGattCallback() {
                override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
                    val stateString = if (newState == BluetoothProfile.STATE_CONNECTED) "connected" else "disconnected"
                    Bridge.dispatchEvent(NativeEvent("bluetooth.state_changed", mapOf(
                        "address" to gatt.device.address,
                        "state" to stateString
                    )))
                }
            })
            return BridgeResponse.success(mapOf("status" to "connecting"))
        }
    }

    class Disconnect(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            BluetoothState.activeGatt?.disconnect()
            BluetoothState.activeGatt?.close()
            BluetoothState.activeGatt = null
            return BridgeResponse.success(mapOf("status" to "disconnected"))
        }
    }

    class StopScan(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val adapter = (context.getSystemService(Context.BLUETOOTH_SERVICE) as BluetoothManager).adapter
            BluetoothState.scannerCallback?.let { adapter.bluetoothLeScanner.stopScan(it) }
            return BridgeResponse.success(mapOf("status" to "stopped"))
        }
    }
}
