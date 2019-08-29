# smart-home

A Docker image meant to connect and automate smart devices in a house.

--------------------------------------------------------------------------------

### Supported stuff

- Zigbee2MQTT
- Bluetooth detection
- HTTP incoming webhooks
- Tasmota sockets
- Yeelight WIFI bulbs
- WakeOnLan
- XBOX controllers
- CEC
- Roborock vacuum cleaners

Probably more but that's what I thought of really quick.

--------------------------------------------------------------------------------

### Languages & Advantages

It's mainly written in `PHP`, `YAML`, `BASH` and it also has a few modules written in `GO`.

I'm saying it's also written in YAML because one of the advantages of this app is that you don't have to know PHP or the source code to create your automation. You can script everything through YAML configs: initiate devices from existing templates, create event triggers, functions and crons or even create new device templates.

Another great advantage over other automation apps is that it's language agnostic. So you can create modules/services written in about any language you want and then integrate them through YAML configs.

--------------------------------------------------------------------------------

### Description

This description is quite incomplete but I'll update it once the projects reaches a relatively stable version.

--------------------------------------------------------------------------------

### Rewrite in Rust

Once the project is stable I plan to rewrite the PHP part in Rust to make it as snappy as possible. But until then I want to make sure I have a clear image of what the app has to do. I still find new use cases which require code changes.

For the moment its description is extremely out of date because I've fully refactored the project a few times but I'll update it once it'll reach a relatively stable version.

Also, I've published the repo rather late because it wasn't decent enough until recently.
