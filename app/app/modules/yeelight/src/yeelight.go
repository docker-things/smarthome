package main

import (
    "fmt"
    "os"
    "strings"
    "sync"
    "goyeelight"
)

func usage() {
    fmt.Println("USAGE: ./yeelight <IP1,IP2,...> <ACTION>    <PARAMS>")
    fmt.Println("       ./yeelight <IP1,IP2,...> on          [duration]")
    fmt.Println("       ./yeelight <IP1,IP2,...> off         [duration]")
    fmt.Println("       ./yeelight <IP1,IP2,...> toggle")
    fmt.Println("       ./yeelight <IP1,IP2,...> brightness  <value> [duration]")
    fmt.Println("       ./yeelight <IP1,IP2,...> temperature <value>")
    fmt.Println("       ./yeelight <IP1,IP2,...> rgb         <r> <g> <b>")
    fmt.Println("       ./yeelight <IP1,IP2,...> hsv         <int> <int> [int]")
    fmt.Println("       ./yeelight <IP1,IP2,...> status")
    fmt.Println("       ./yeelight <IP1,IP2,...> discover")
    fmt.Println("       ./yeelight <IP1,IP2,...> set-default")
    os.Exit(1)
}

func main() {

    // Default values
    effect := "smooth"
    duration := "500"
    port := "55443"

    // Get command-line args
    args := os.Args[1:]

    // Check number of args
    if len(args) < 2 {
        usage()
    }

    // Check actions
    switch action := args[1]; action {
        case "on":
        case "off":
        case "toggle":
        case "brightness":
        case "temperature":
        case "rgb":
        case "hsv":
        case "status":
        case "set-default":
        default:
            fmt.Println("ERROR: Unknown action!")
            os.Exit(1)
    }

    // Split the IPs into an array
    ips := strings.Split(args[0], ",")

    // Get action params
    params := args[2:]

    // Get actual action
    action := args[1]

    // Init WaitGroup
    var wg sync.WaitGroup

    // For each IP, do the magic!
    for i := 0; i < len(ips); i++ {

        // Increment the WaitGroup counter
        wg.Add(1)

        // Launch the thread
        go sendYeelightAction(ips[i], port, action, effect, duration, params, &wg)
    }

    // Wait for all routines to complete
    wg.Wait()
}

func sendYeelightAction(ip string, port string, action string, effect string, duration string, params []string, wg *sync.WaitGroup) {

    // Decrement the counter when the goroutine completes.
    defer wg.Done()

    // New Yeelight instance
    bulb := goyeelight.New(ip, port)

    // Target action
    switch thisAction := action; thisAction {

        case "on":
            if len(params) == 1 {
                duration = params[0]
            }
            fmt.Println(bulb.On(effect, duration))

        case "off":
            if len(params) == 1 {
                duration = params[0]
            }
            fmt.Println(bulb.Off(effect, duration))

        case "toggle":
            fmt.Println(bulb.Toggle())

        case "brightness":
            if len(params) == 2 {
                duration = params[1]
            }
            fmt.Println(bulb.SetBright(params[0], effect, duration))

        case "temperature":
            if len(params) == 2 {
                duration = params[1]
            }
            fmt.Println(bulb.SetCtAbx(params[0], effect, duration))

        case "rgb":
            if len(params) == 2 {
                duration = params[1]
            }
            fmt.Println(bulb.SetRGB(params[0], effect, duration))

        case "hsv":
            if len(params) == 3 {
                duration = params[2]
            }
            fmt.Println(bulb.SetHSV(params[0], params[1], effect, duration))

        case "status":
            fmt.Println(bulb.GetProp(`"power","bright","ct","rgb","hue","sat","color_mode","music_on","name"`))

        // case "discover":
        //     from yeelight import discover_bulbs
        //     fmt.Println(json.dumps(discover_bulbs()))

        case "set-default":
            fmt.Println(bulb.SetDefault())
    }
}
