package main

import (
  "os"
  "os/signal"
  "syscall"

  config "app/config"
  functions "app/functions"
  incoming "app/incoming"
  state "app/state"
)

func main() {
  // Create channel to monitor interrupt signals
  c := make(chan os.Signal, 1)
  signal.Notify(c, os.Interrupt, syscall.SIGTERM)

  go config.StartService(true)
  go functions.StartService(true)
  go incoming.StartService(true)
  go state.StartService(true)

  // Keep alive until interrupt is received
  <-c
}
