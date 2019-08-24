package main

import (
    "fmt"
    "os"
    "sync"

    MQTT "github.com/eclipse/paho.mqtt.golang"
    EVDEV "github.com/gvalkov/golang-evdev"
)

func processEvent(ev *EVDEV.InputEvent, mqttClient MQTT.Client, topic string) {
    var code_name string

    code := int(ev.Code)
    etype := int(ev.Type)

    switch ev.Type {
    case EVDEV.EV_SYN:
        return
    case EVDEV.EV_KEY:
        val, haskey := EVDEV.KEY[code]
        if haskey {
            code_name = val
        } else {
            val, haskey := EVDEV.BTN[code]
            if haskey {
                code_name = val
            } else {
                code_name = "?"
            }
        }
    default:
        m, haskey := EVDEV.ByEventType[etype]
        if haskey {
            code_name = m[code]
        } else {
            code_name = "?"
        }
    }

    message := fmt.Sprintf(
        "{\"timestamp\":\"%d\",\"timestamp.usec\":\"%d\",\"type_code\":\"%d\",\"type_name\":\"%s\",\"event_code\":\"%d\",\"event_name\":\"%s\",\"value\":\"%d\"}",
        ev.Time.Sec, ev.Time.Usec, etype, EVDEV.EV[int(ev.Type)], ev.Code, code_name, ev.Value)

    mqttClient.Publish(topic, 0, false, message)
}


func listenDevice(dev *EVDEV.InputDevice, mqttClient MQTT.Client, wg *sync.WaitGroup) {
    defer wg.Done()

    var events []EVDEV.InputEvent
    var err error

    topic := "gamepad"+dev.Fn+"/rx"

    fmt.Printf("Listening to "+dev.Fn+" ("+dev.Name+")...\n")
    for {
        events, err = dev.Read()
        if err != nil {
            fmt.Println(err)
            os.Exit(1)
        }
        for i := range events {
            processEvent(&events[i], mqttClient, topic)
        }
    }
}


func main() {

    // Config MQTT
    opts := MQTT.NewClientOptions().AddBroker("tcp://localhost:1883").SetClientID("evdev")
    opts.SetCleanSession(true)

    // Connect to MQTT
    mqttClient := MQTT.NewClient(opts)
    if token := mqttClient.Connect(); token.Wait() && token.Error() != nil {
        panic(token.Error())
    }
    fmt.Println("Connected to MQTT")

    // Get available devices
    devices, _ := EVDEV.ListInputDevices("/dev/input/event*")

    // If no device found
    if len(devices) < 1 {
        fmt.Println("no accessible input devices found by /dev/input/event*")
        os.Exit(1)
    }

    // For each device launch a thread
    var wg sync.WaitGroup
    for i := range devices {
        if devices[i].Name != "Microsoft X-Box 360 pad" {
            continue
        }

        // Launch thread
        wg.Add(1)
        go listenDevice(devices[i], mqttClient, &wg)
    }

    // Wait for all threads to complete
    wg.Wait()
}
