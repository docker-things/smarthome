# smart-home

#### TODO: Update README.md because it's extremely outdated.

--------------------------------------------------------------------------------

#### Testing stuff - nothing polished yet - work in progress

For the moment this automatically manages an Yeelight smart bulb.

### What's this?

This is a docker image that can automatize stuff in the house.

### Startup flow

You're running ```bash launch.sh``` this happens:

 - A backup of the existing files is created
 - A RAM partition is mounted in the image and data is copied to RAM
 - A _Screen_ session is created
 - Apache, Cron & Sync are started in separated _Screen_ windows
 - The user is attached to the _Screen_ session
 - When detaching/exiting the image, files are synced back to the HDD

The files are synced to the HDD every minute while the image is running anyway.


### Hardware used

 - Yeelight smart bulb
 - Fix Android phone with Automate (stays at home as a light sensor)
 - Mobile Android phone wth Automate (triggers home arrival/departure)
 - Laptop which contains this docker image


### Structure

```php
 - Dockerfile             "The docker build configuration"
 - build.sh               "Run this to build your docker image"
 - launch.sh              "Run this to launch your docker image"

 - install/               "Files to  be included in the docker image (readonly in the image)"
 - install/bashrc/        "The basrc files to be used in the docker image"
 - install/paths.sh       "Keeps dir paths (ram dir, mount dir, dirs to be synced in ram)"
 - install/startup.sh     "The script launched when starting the docker image"
 - install/sync.sh        "Used to sync data between HDD and RAM"

 - mount/                 "Files to be mounted in the docker image (persistent changes in the image)"
 - mount/html/            "Contains the web php service"
 - mount/html/classes/    "Main classes (some of them are so poorly written you'll wish you didn't look)"
 - mount/html/crons/      "Cron jobs (like the sunrise time getter or the automatic bulb on/off)"
 - mount/html/config.php  "Configs used through the web service (IPs, WiFi names, home city...)"
 - mount/html/cron.php    "Launches the cron service"
 - mount/html/index.php   "The web service endpoint"
 - mount/yeelight/        "Contains the Yeelight Python API"
```

### Crons

 - BulbStateFix
    - Checks if the light is on/off and should be off/on depending on room light

 - BulbStatus
    - Gets the current status of the Yeelight Bulb and saves it in the DB every

 - DayNight
    - Depending on the sunrise and sunset times saves a dayNight variable in the DB which can hold "day" or "night"

 - SunriseSunset
    - Gets the sunrise and sunset times for the current day

### Yeelight Bulb Behaviour

Arrival/Departure:
 - On when arriving home (if the room is dark enough)
 - Off when leaving home (if the room is dark enough)

Already home:
 - On when the room goes dark (if not playing a movie)
 - Off when the room gets enough light

Plex:
 - Off when playing/resuming a movie
 - On when stopping a movie (if the room is dark enough)
 - On (dimmed) when pausing a movie (if the room is dark enough)
