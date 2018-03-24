### PiHole Blacklist Conglomerator
-----------------------------------

## Preface
Ok, so, this is a work in progress, essentially a very simple system for collating your lists and to reduce the amount of entries/work that pihole has to do.

I'm running php7.0 but am writing this in mind to run on most php versions and trying to keep dependancies as minimal as possible, so bear with me.

I thought about having the list stored locally on the pihole, but some people run multiple pi-holes, and manage others. This gives an easier method/way to manage them all/keep them in sync (in theory, I haven't tested this yet)

## How to use
 - Fork this project
 - Add the urls to `blocklist-in.list` that you want to pull lists from
 - Add any custom urls to your block list to `blocklist-manual.list`
 - Add any whitelist urls/domains to `whitelist-manual.list`
 - Run `make blacklist`
 - Push the blacklist to your repo (the diff should be kind of cool)
 - Get pi-hole to update your blacklist

## Upcoming changes
 - tagged release
 - bash/shell compatible functions
 - HEADing request/check for differences (might not be possible)
 - other cool functions (auto updating pihole et al)

