module smart

go 1.18

require app v0.0.0-00010101000000-000000000000

require (
	github.com/eclipse/paho.mqtt.golang v1.4.2 // indirect
	github.com/ghodss/yaml v1.0.0 // indirect
	github.com/go-sql-driver/mysql v1.6.0 // indirect
	github.com/gorilla/websocket v1.4.2 // indirect
	github.com/pkg/errors v0.8.1 // indirect
	golang.org/x/net v0.0.0-20200425230154-ff2c4b7c35a0 // indirect
	golang.org/x/sync v0.0.0-20210220032951-036812b2e83c // indirect
	gopkg.in/tucnak/telebot.v2 v2.5.0 // indirect
	gopkg.in/yaml.v2 v2.4.0 // indirect
)

replace app => ./
