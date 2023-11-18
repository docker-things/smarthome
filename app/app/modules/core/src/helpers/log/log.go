package log

import (
  "fmt"
  "os"
  "strings"

  json "app/helpers/json"
)

var active bool
var ignore bool
var indentation int
var path []string

var buffer []string

func Init() {
  indentation = 0
  path = make([]string, 0)
  Activate()
  IgnoreTo()
}

func Activate() {
  active = true
  buffer = make([]string, 0)
}
func Deactivate() { active = false }

func IgnoreFrom() { ignore = true }
func IgnoreTo()   { ignore = false }

func ShowDeactivatedMessages() {
  for i := 0; i < len(buffer); i++ {
    fmt.Printf(buffer[i])
  }
  buffer = make([]string, 0)
}

func PushPath(str string) {
  indentation++
  if currentPath() == str {
    path = append(path, ".")
  } else {
    path = append(path, str)
  }
}

func PopPath() {
  indentation--
  path = path[:len(path)-1]
}

func Dump(value interface{}, str string) {
  show("DUMP", fmt.Sprintf("[%p] %s: %s", value, str, json.Encode(value)), false)
}

func Info(str string) {
  show("INFO", str, false)
}

func Warn(str string) {
  show("WARN", str, false)
}

func Error(str string) {
  Activate()
  show("ERROR", str, true)
}

func Panic(str string) {
  Activate()
  show("PANIC", str, true)
  os.Exit(1)
}

func show(prefix string, str string, showFullPath bool) {
  if ignore {
    return
  }

  var message string
  if showFullPath {
    message = fmt.Sprintf("[%-5s][%s] %s\n", prefix, strings.Join(path, ":"), str)
  } else {
    message = fmt.Sprintf("[%-5s][%-21s]", prefix, currentPath())
    for i := 0; i < indentation; i++ {
      message += "  "
    }
    message += fmt.Sprintf("> %s\n", str)
  }

  if active {
    fmt.Printf(message)
  } else {
    buffer = append(buffer, message)
  }
}

func currentPath() string {
  for i := len(path) - 1; i >= 0; i-- {
    if path[i] != "." {
      return path[i]
    }
  }
  return "unknown"
}
