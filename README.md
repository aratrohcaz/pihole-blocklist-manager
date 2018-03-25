### PiHole Blacklist Conglomerator
-----------------------------------

## Preface
Ok, so, this is a work in progress, essentially a very simple system for collating your lists and to reduce the amount
of entries/work that pihole has to do.

I'm running php7.0 but am writing this in mind to run on most php versions and trying to keep dependancies as minimal
as possible, so bear with me. This mean nicities such as guzzle have been avoided (for the time being), and using 
`file_get_contents` has also been avoided as some people may not have the `allow_fopen` ini setting set.

I thought about having the list stored locally on the pihole, but some people run multiple pi-holes, and manage others.
This gives an easier method/way to manage them all/keep them in sync (in theory, I haven't tested this yet)

## How to use
 - Fork this project
 - Add the urls to `blacklist-in.list` that you want to pull lists from
 - Add any custom urls to your block list to `blacklist-manual.list`
 - Add any whitelist urls/domains to `whitelist-manual.list`
 - Run `make blacklist`
 - Push the blacklist to your repo (the diff should be kind of cool)
 - Get pi-hole to update your blacklist

## Resources
[Wally 3k List]     - Wally3k Blocklists, a list of lists, a handy resource for blocklist urls
[Common Whitelist]  - Commonly whitelisted domains, user-editable from/via the PiHole community
[r/pihole]          - Reddit PiHole community
[pi-hole.net]       - Well, you're here, and it would seem silly to not put it here

## Upcoming changes
 - tagged release
 - bash/shell compatible functions
 - HEADing request/check for differences (might not be possible)
 - other cool functions (auto updating pihole et al)

[Wally 3k List]:    https://wally3k.github.io/
[Common Whitelist]: https://discourse.pi-hole.net/t/commonly-whitelisted-domains/212
[r/pihole]:         https://reddit.com/r/pihole
[pi-hole.net]:      https://pi-hole.net
