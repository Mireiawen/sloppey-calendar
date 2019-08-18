# Sloppey Calendar notifier
This application is used by the Sloppey raiding to fetch different notifications from [Teamup](https://www.teamup.com/) API and [Doodle](https://doodle.com/) ICS feed and send those to the [Discord](https://discordapp.com/) channel.

## Requirements
* Docker
* Redis container

## Using
 1. Run the `build.sh` to build the container.
 2. Copy the `config-sample.json` to `config.json`
 3. Edit the `config.json` and change the fields to match your settings
 4. Run the `cronjob.sh`
    * Note: This expects Redis to be available as container `redis`
