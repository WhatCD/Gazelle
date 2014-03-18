# Gazelle
Gazelle is a web framework geared towards private BitTorrent trackers. Although naturally focusing on music, it can be modified for most needs. Gazelle is written in PHP, JavaScript, and MySQL.

## Gazelle Runtime Dependencies
* [Nginx](http://wiki.nginx.org/Main) (recommended)
* [PHP 5.4 or newer](https://www.php.net/) (required)
* [Memcached](http://memcached.org/) (required)
* [Sphinx 2.0.6 or newer](http://sphinxsearch.com/) (required)
* [procps-ng](http://sourceforge.net/projects/procps-ng/) (recommended)

## Gazelle/Ocelot Compile-time Dependencies
* [Git](http://git-scm.com/) (required)
* [GCC/G++](http://gcc.gnu.org/) (4.7+ required; 4.8.1+ recommended)
* [Boost](http://www.boost.org/) (1.55.0+ required)

_Note: This list may not be exhaustive._

## Change Log
You may have noticed that commits in the repository do not have descriptive messages. If you are looking for a change log of Gazelle, it can be [viewed here](https://raw.github.com/WhatCD/Gazelle/master/docs/CHANGES.txt). The change log is generated daily and includes new additions or modifications to Gazelle's source.

## Coding Standards
Gazelle's code adheres to a set of coding standards that can be found [here](https://github.com/WhatCD/Gazelle/wiki/Coding-Standards). If you plan on sending pull requests, these standards must be followed.

## Installation
[This guide](https://github.com/WhatCD/Gazelle/wiki/Gazelle-installation) will walk you through setting up Gazelle on a machine running Gentoo Linux. Although installing Gazelle is relatively straightforward, we recommend a working knowledge of PHP if you plan to modify the source code.

## Gazelle development using Vagrant
[VagrantGazelle](https://github.com/dr4g0nnn/VagrantGazelle) allows for convenient development of Gazelle, without going through the trouble of setting it all up yourself.

Vagrant uses virtual machines to allow for easy development in consistent environments. The setup linked above allows for development on your local machine and the Gazelle setup to run without altering your system.

Once set up, the Gazelle source files will be present in `src/`, which is shared to `/var/www/` on the machine. A port forward from port 80 on the guest to 8080 on the host will also be established.
