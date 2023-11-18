package main

import (
  incoming "app/incoming"
)

func main() {
  incoming.StartService(false)
}
