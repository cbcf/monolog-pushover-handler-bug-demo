# monolog-pushover-handler-bug-demo
Demonstrate randomly dropped packages in Monolog PushoverHandler
and potential fixes. Includes a rudimentary performance measure.

## How to run
- Clone and install dependencies: `composer update`
- Create a [Pushover](https://pushover.net/) account
- Create a new [Pushover App](https://pushover.net/apps/build)
- Copy `token.template.php` to `token.local.php`
- Insert your App- and User-Tokens in `token.local.php`
- If you like, change the test settings in `index.php` below `/* Configure me here */`
- Run the test `php ./index.php` (keep in mind the message limit of 7500 per month)

## Results so far
Ubuntu root sever, datacenter (Falkenstein, Germany), PHP 7.2.24 by @cbcf

Implementation               | Message Loss | Average Time | Median Time 
-----------------------------|-------------:|-------------:|------------:
original PushoverHandler     |         60 % |       121 ms |      124 ms
PushoverHandlerWithFread     |          0 % |       720 ms |      358 ms
PushoverHandlerWithKeepAlive |          0 % |       535 ms |      239 ms

Windows notebook, domestic WiFi, PHP 7.4.8 by @cbcf

Implementation               | Message Loss | Average Time | Median Time 
-----------------------------|-------------:|-------------:|------------:
original PushoverHandler     |         45 % |       201 ms |      209 ms
PushoverHandlerWithFread     |          0 % |       780 ms |      505 ms
PushoverHandlerWithKeepAlive |          0 % |       381 ms |      268 ms

