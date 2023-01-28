[![Discord](https://badgen.net/badge/icon/discord?icon=discord&label)](https://discord.gg/memUh56u8z)
[![Docker](https://img.shields.io/badge/dockerhub-images-important.svg?logo=Docker)](https://hub.docker.com/r/tkhamez/neucore)
[![Test](https://github.com/tkhamez/neucore/actions/workflows/test.yml/badge.svg)](https://github.com/tkhamez/neucore/actions/workflows/test.yml)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tkhamez_neucore&metric=coverage)](https://sonarcloud.io/summary/overall?id=tkhamez_neucore)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=tkhamez_neucore&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=tkhamez_neucore) <!-- previous project: https://sonarcloud.io/dashboard?id=brvneucore -->
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/4573/badge)](https://bestpractices.coreinfrastructure.org/projects/4573)
[![Open Hub](https://www.openhub.net/p/neucore/widgets/project_thin_badge?format=gif)](https://www.openhub.net/p/neucore/)

# ![](setup/logo-small.svg) Neucore - Alliance Core Services

<!-- toc -->

- [Overview](#overview)
- [Getting started](#getting-started)
  * [Installation](#installation)
  * [First Login and Customization](#first-login-and-customization)
  * [Setting up Member Tracking and Watchlists](#setting-up-member-tracking-and-watchlists)
- [Plugins and other related software](#plugins-and-other-related-software)
- [Final Notes](#final-notes)

<!-- tocstop -->

## Overview

A web application for [EVE Online](https://www.eveonline.com) communities to organise their members into groups,
monitor them and provide access to external services.

This application focuses on providing core functionality related to player identities and an API that other
applications can build upon.

Demo: https://neucore.tian-space.net.

Main features:

- Management of group memberships, manually and with various ways to automate membership.
- API for various data including an [ESI](http://esi.evetech.net) proxy for all characters.
- [Plugin](doc/Plugins.md) system, e.g. for service registration (Discord, Mumble etc.).
- Corporation member tracking and character watchlists.
- ... [and much more](doc/Documentation.md#features)

For more information, see the [doc](doc/README.md) directory, which also contains some 
[screenshots](doc/screenshots/README.md).

## Getting started

### Installation

See [doc/Install.md](doc/Install.md) for installation instructions.

### First Login and Customization

- Login with an EVE character to create an account.
- Open a console and run `backend/bin/console make-admin 1`.
- Navigate to *Admin -> Settings* and change texts, links and images that are specific to your installation.

### Setting up Member Tracking and Watchlists

Group for permissions
- Go to Administration -> Groups, create a new group and add yourself as a manager.
- Go to Management -> Groups, select the new group and add yourself as a member.

Member Tracking
- Click the "Add additional ESI tokens" button on the home page, and then use the "core.tracking" login button
  to log in a character with director role for your corporation.
- Open a console and run `backend/bin/console update-member-tracking`.
- Go to Administration -> Tracking, select your corporation and add a group you are a member of.
- Go to Member Data -> Member Tracking and select your corporation.

Watchlist
- Go to Administration -> Watchlist and add a new watchlist. Open the "View" and "Manage" tabs and add your group.
- Go to Member Data -> Watchlist -> Settings and add alliances and/or corporations for watching.

## Plugins and other related software

Official plugins:

- Discord auth: [neucore-discord-plugin](https://github.com/tkhamez/neucore-discord-plugin)
- Mumble auth: [neucore-mumble-plugin](https://github.com/tkhamez/neucore-mumble-plugin)
- General plugin example [neucore-example-plugin](https://github.com/tkhamez/neucore-example-plugin)

Other plugins:

- Brave Collective [Slack](https://github.com/bravecollective/neucore-plugin-slack)
- Brave Collective [phpBB forum](https://github.com/bravecollective/neucore-plugin-forum)

OpenAPI clients for the Neucore API:

- [PHP](https://github.com/bravecollective/neucore-api-php)
- [Python](https://github.com/bravecollective/neucore-api-python)
- [Go](https://github.com/bravecollective/neucore-api-go)

Other software that use the Neucore API:

- [slack-channel-manage](https://github.com/bravecollective/slack-channel-manage) A Slack app to
  manage channel members based on Neucore groups.
- [Eve Overseer](https://github.com/1adog1/eve-overseer) A fleet participation tracking application.
- [Eve Pingboard](https://github.com/cmd-johnson/eve-pingboard) Pings/Timers/Calendar.
- [finance-check](https://github.com/tkhamez/finance-check)
- [EVE-SRP](https://github.com/bravecollective/evesrp/tree/eb-deploy) integration.
- [Neucore connector boilerplate](https://github.com/bravecollective/neucore-connector-boilerplate)
  An example PHP application that uses EVE SSO and Neucore groups for access control.
- A [TimerBoard](https://github.com/tkhamez/neucore-timerboard) (based on the boilerplate).
- A [Ping](https://github.com/bravecollective/ping-app) app for Slack.

## Final Notes

**Contact**

If you have any questions or feedback, you can join the [Neucore Discord Server](https://discord.gg/memUh56u8z) or
contact [Tian Khamez](https://evewho.com/character/96061222) on 
[Tweetfleet Slack](https://tweetfleet.slack.com) (get invites 
[here](https://www.fuzzwork.co.uk/tweetfleet-slack-invites/)).

**Donations**

If you want to support the development of this application, you can send ISK to the character `Tian Khamez` so 
I can spend more time coding instead of earning ISK in the game ;).

**Report Vulnerabilities**

Vulnerabilities can be reported privately to tkhamez@gmail.com.

**Origin**

The software was originally developed for the [Brave Collective](https://www.bravecollective.com),
when CCP shut down the old API, and we had to replace our Core system.

This is also where the name "Neucore" (new Core) comes from.

**Copyright notice**

Neucore is licensed under the [MIT license](LICENSE).

"EVE", "EVE Online", "CCP" and all related logos and images are trademarks or registered trademarks of
[CCP hf](http://www.ccpgames.com/).
