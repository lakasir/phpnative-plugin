package com.sheenazien8.plugins.bluetooth_devices

import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import android.util.Log

object MyPluginFunctions {

    class DoSomething : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val option = parameters["option"] as? String ?: ""
            Log.d("BluetoothBridge", "Starting a new BLE scan...")

            return BridgeResponse.success(mapOf(
                "result" to "completed",
                "option" to option
            ))
        }
    }
}
