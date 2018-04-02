### PiHole Blacklist Manager
-----------------------------------

## Preface
This is a quick and dirty version of simple block-list collator. 

Some users maintain server Piholes and I thought this would be a nice challenge for some simple PHP code to create one list that all their remote deployments can pull from (essentially to cut down on maintenance). While the teleporter function ality of PiHole is nice, this will allow for the `cron` on each deployment to keep a single user maintained list up to date. 

Tested and running on PHP 5.4.16 on CentOS Linux 7.4.1708. 

## How to use
 1. Fork/download this project
 2. Create a `config.ini` based off of `sample-config.ini` (it will be created automatically if it doesn't exists)
 3. Run `php run.php`
 4. Watch and wait for it to finish it's thing
 5. Push the blacklist to your repo (the diff should be kind of cool)
 6. Get pi-hole to update from your blacklist

## Current Issues
 - creating/destruction of many (cURL) resources
