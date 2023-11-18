package main

import (
  "fmt"
  "log"
  "strings"
  "time"

  mqtt "app/helpers/mqtt"

  tb "gopkg.in/tucnak/telebot.v2"
)

const serviceName = "core/telegram-bot"
const broker = "tcp://mqtt:1883"

// DO NOT COMMIT !!!
const botToken = ""
// DO NOT COMMIT !!!

var topicRequest = strings.Join([]string{serviceName, "request"}, "/")
var topicPublish = strings.Join([]string{serviceName, "get"}, "/")

const topicPublishRun = "core-function/run"
const topicPublishSet = "core-state/set"
const topicPublishFreeText = "core/free-text-parser/parse"

const registered = true

var startText = strings.Join([]string{
  "Use the following command to register:",
  "",
  "`/register [TOKEN]`",
}, "\n")

var helpText = strings.Join([]string{
  "Commands:",
  "",
  "`[FREE-TEXT]`",
  "`/run [FUNCTION]`",
  "`/set [OBJECT] [PARAM] [VALUE]`",
  "`/help`",
}, "\n")

func main() {

  // Connect to MQTT
  mqtt.Connect(serviceName, broker)

  // Listen to incoming MQTT requests
  mqtt.Subscribe(topicRequest, func(msg string) {
    fmt.Println("RECEIVED: " + msg)
  })

  // Create Telegram bot
  bot, err := tb.NewBot(tb.Settings{
    Poller: &tb.LongPoller{Timeout: 15 * time.Second},
    Token:  botToken,
  })
  if err != nil {
    log.Fatal(err)
    return
  }

  // // Buttons
  // var (
  //   // Universal markup builders.
  //   menu     = &tb.ReplyMarkup{ResizeReplyKeyboard: true, OneTimeKeyboard: true}
  //   selector = &tb.ReplyMarkup{ReplyKeyboardRemove: true}

  //   // Reply buttons.
  //   btnHelp     = menu.Text("ℹ Help")
  //   btnSettings = menu.Text("⚙ Settings")

  //   // Inline buttons.
  //   //
  //   // Pressing it will cause the client to
  //   // send the bot a callback.
  //   //
  //   // Make sure Unique stays unique as per button kind,
  //   // as it has to be for callback routing to work.
  //   //
  //   btnPrev = selector.Data("⬅", "prev")
  //   btnNext = selector.Data("➡", "next")
  // )

  // menu.Reply(
  //   menu.Row(btnHelp, btnSettings),
  //   menu.Row(btnHelp),
  //   menu.Row(btnSettings),
  // )
  // selector.Inline(
  //   selector.Row(btnPrev, btnNext),
  //   selector.Row(btnPrev),
  //   selector.Row(btnNext),
  // )

  bot.Handle("/register", func(m *tb.Message) {
    // var token := m.Payload
    bot.Send(m.Sender, "Not implemented yet!")
  })

  bot.Handle("/run", func(m *tb.Message) {
    if registered {
      mqtt.PublishOn(topicPublishRun, m.Payload)
    }
  })

  bot.Handle("/set", func(m *tb.Message) {
    if registered {
      mqtt.PublishOn(topicPublishSet, m.Payload)
    }
  })

  bot.Handle("/help", func(m *tb.Message) {
    if registered {
      bot.Send(m.Sender, helpText, tb.ModeMarkdown)
    }
  })

  bot.Handle("/start", func(m *tb.Message) {
    if m.Private() {
      bot.Send(m.Sender, startText, tb.ModeMarkdown)
    }
  })

  bot.Handle(tb.OnText, func(m *tb.Message) {
      mqtt.PublishOn(topicPublishFreeText, m.Payload)
    })

  // // bot.Send(m.Sender, "showing menu", menu)

  // // On reply button pressed (message)
  // bot.Handle(&btnHelp, func(m *tb.Message) {
  //   fmt.Println(" > HELP BUTTON PRESSED 2!")
  // })

  // // On reply button pressed (message)
  // bot.Handle(&btnSettings, func(m *tb.Message) {
  //   fmt.Println(" > SETTINGS BUTTON PRESSED 2!")
  //   bot.Send(m.Sender, "showing selector", selector)
  // })

  // // On inline button pressed (callback)
  // bot.Handle(&btnPrev, func(c *tb.Callback) {
  //   fmt.Println(" > PREV BUTTON PRESSED 2!")
  //   // ...
  //   // Always respond!
  //   bot.Respond(c, &tb.CallbackResponse{Text: "prev button response"})
  // })

  // graceful shutdown
  // time.AfterFunc(time.Second, bot.Stop)

  // blocks until shutdown
  bot.Start()
}
