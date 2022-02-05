# TS3Query

TS3Query library to manage TeamSpeak 3 server or create bots/api.

### Why this library?
* Created for making a unprecedented performance querys bot
* Access to every available function in ServerQuery
* Access to manage files in TeamSpeak 3 
* Fully object oriented components
* **Support for all events type!**
* **Caching system to reduce commands!**

## Installation

### Requirements
* PHP 8.0 or higher
* TeamSpeak 3 server 3.12.0 or higher


You can install the TS3Query by using Composer:

```bash
composer require Lukieer\TS3Query
```

## Usage

Before you start managing teamspeak 3 server, you must include library and connect to teamspeak 3 serverquery.

```php
<?php
require_once "vendor/autoload.php"; # Autoload library
?>
```

Now, you must prepare the URI. You have a few options:


## Connect only to Host (without selecting server)
```php
<?php
$ts3_host = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011");
?>
```
Now, $ts3_host variable is a Host object, which you can manage TeamSpeak 3 instance!
### Examples
#### Get a serverList
```php
<?php
$ts3_host = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011");
$serverList = $ts3_host->serverList();

print_r($serverList)

/** Output 

Array
(
    [success] => 1
    [message] => ok
    [data] => Array
        (
            [virtualserver_id] => 1
            [virtualserver_port] => 9987
            [virtualserver_status] => online
            [virtualserver_clientsonline] => 134
            [virtualserver_queryclientsonline] => 11
            [virtualserver_maxclients] => 256
            [virtualserver_uptime] => 1430693
            [virtualserver_name] => ServerName
            [virtualserver_autostart] => 1
            [virtualserver_machine_id] =>
        )

)
**/
?>
```

#### Send Message to all clients in all servers
```php
<?php
$ts3_host = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011");

$ts3_host->globalMessage("Hello world!");
?>
```

## Connect to Host and select server

Uri examples:
```
serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987 - connect to instance, and select server 
serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900 - connect to instance, select server and switch to channel 
serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900&client_name=hej - connect to instance, select server, switch to channel and set nickname 
serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900&client_name=hej&debug=1 - connect to instance, select server, switch to channel and set nickname. Enable debug logging
```

### Examples
#### Connect to Host, select server and poke client with id 23

```php
<?php
$ts3_server = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987");

$ts3_server->clientPoke(23, "Hello!");
?>
```
#### Connect to Host, select server and only wait for events

```php
<?php
$ts3_server = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987");

$ts3_server->erverNotifyRegister('all');

      while(true)
      {
          $events = $ts3->getEvents(1);
          foreach($events as $event)
          {
              if($event['name'] == 'notifycliententerview')
              {
                  $ts3->sendMessage(1, $event['clid'], "Hello");
             }
          }
          $ts3->keepAlive(); # Reset idle time, important
          $clientList = $ts3->clientList(); # Only when event is called!
      }

?>
```
#### Connect to Host, select server, wait for events and move AFK clients to AFK channel

```php
<?php
$ts3_server = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987");

$ts3_server->erverNotifyRegister('all');

      while(true)
      {
          $events = $ts3->getEvents(0);
          foreach($events as $event)
          {
              if($event['name'] == 'notifycliententerview')
              {
                  $ts3->sendMessage(1, $event['clid'], "Hello");
             }
          }

        $clientList = $ts3->clientList(array('away'))['data']; # Every loop

        foreach($clientList as $client)
        {
            if($client['client_type'] !== TS3Query::QueryClient) # filtr normal clients
            {
                if($client['client_away'])
                {
                    $ts3->clientMove($client['clid'], 123); # 123 is id of afk channel
                }
            }
        }
    }

?>
```
#### If you want to execute a single command on an Instance rather than the server, you must use the following method
```php
<?php
$ts3_server = new \Lukieer\TS3Query\TS3Query::factory("serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987");

var_dump($ts3_server->host->apiKeyAdd());
?>
```


## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)