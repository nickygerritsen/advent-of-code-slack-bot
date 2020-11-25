# Advent of Code Slack bot

This project is a Slack bot that will report updates for a private [Advent of Code](https://adventofcode.com) leaderboard.

It will report when someone solved a problem. Also, if any changes occured, it will report the current leaderboard.

## Installation

Make sure you have a recent PHP version installed (tested with PHP 7.4) as well as [Composer](https://getcomposer.org).

Then clone the repository and run

```bash
composer install
```

## Configuration

Create a [Incoming Slack Webhook](https://my.slack.com/services/new/incoming-webhook/).
Also log in to Advent of Code and get the private leaderboard ID number and well as your session cookie.
The session cookie can be found in your browser's development console.

Now create a file `.env.local` in the root of the cloned repository with the following contents:

```bash
AOC_YEAR=2020 # Defaults to 2020
AOC_SESSION_ID=<your AoC session ID>
AOC_LEADERBORD_ID=<your AoC private leaderboard ID>
SLACK_WEBHOOK=<your Slack webhook URL>
BOT_NAME="Custom bot name" # Defaults to AoC bot
```

Now create a cronjob that runs `<path to repo>/bin/console aoc` every so often. Note that the Advent of Code site
states to not run it more than once every 15 minutes.

## Credits

This project is created by [Nicky Gerritsen](https://github.com/nickygerritsen) and is based of the
[Python Advent of Code bot](https://github.com/tomswartz07/AdventOfCodeLeaderboard.git) by Tom Swartz.
