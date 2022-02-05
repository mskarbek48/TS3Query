<?php
/*
 * TS3Query.php
 *
 * Created on Mon Jan 31 2022 20:21:23
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query;

use Lukieer\TS3Query\Node\Server;
use Lukieer\TS3Query\Helpers\Uri;
use Lukieer\TS3Query\Helpers\Debug;
use Lukieer\TS3Query\Exception;
use Lukieer\TS3Query\Adapters\ServerQuery;
use Lukieer\TS3Query\Adapters\FileTransfer;
use Lukieer\TS3Query\Node\Host;

class TS3Query
{

    /**
     * First line of TeamSpeak 3 telnet
     */
    const TS3_WELCOME_TELNET = "TS3";


    /**
     * TS3 Error string
     */
    const TS3_ERROR = "error";


    /**
     * TS3 Event string
     */
    const TS3_EVENT = "notify";


    /**
     * New line
     */
    const S_LINE = "\n";


    /**
     * Element separator
     */
    const S_LIST = "|";
    

    /**
     * Space
     */
    const S_SPACE = " ";


    /**
     * Data separator
     */
    const S_RESULTS = "=";


    /**
     * Escaping TS3 
     */
    const TS3_ESCAPE = array(
        "\\" => "\\\\",
        "/"  => "\\/",  
        " "  => "\\s",  
        "|"  => "\\p",  
        ";"  => "\\;",  
        "\a" => "\\a",  
        "\b" => "\\b",  
        "\f" => "\\f",  
        "\n" => "\\n",  
        "\r" => "\\r",  
        "\t" => "\\t",  
        "\v" => "\\v"  
    );


    /**
     * Unescaping TS3
     */
    const TS3_UNESCAPE = array(
        "\t" => '',
        "\v" => '',
        "\r" => '', 
        "\n" => '',
        "\f" => '', 
        "\s" => ' ',
        "\p" => '|',
        "\/" => '/',
    );


    /**
     * Event reasons
     */
    const EVENT_REASON_NONE = 0; # None
    const EVENT_REASON_MOVE = 1; # Client is moved
    const EVENT_REASON_SUBSCRIPTION = 2; # Changed subscribtion
    const EVENT_REASON_TIMEOUT = 3; # Client timeout
    const EVENT_REASON_CHANNEL_KICK = 4; # Client kicked from the channel
    const EVENT_REASON_SERVER_KICK = 5; # Client kicked from the server
    const EVENT_REASON_SERVER_BAN = 6; # Client banned
    const EVENT_REASON_SERVER_STOP = 7; # Server stopped
    const EVENT_REASON_DISCONNECT = 8; # Client disconnect
    const EVENT_REASON_CHANNEL_UPDATE = 9; # Channel updated
    const EVENT_REASON_CHANNEL_EDIT = 10; # Channel edit
    const EVENT_REASON_DISCONNECT_SHUTDOWN = 11; # Client disconnecting because server stopped


    /**
     * Max characters in line
     */
    const COMMAND_SPLIT = 1024;


    /**
     * Only once results
     */
    const ARRAY_ONCE = 1;


    /**
     * Multiple results
     */
    const ARRAY_MULTI = 2;


    /**
     * Error id when Success
     */
    const SUCCESS = 0;


    /**
     * Api keys
     */
    const APIKEY_MANAGE = "manage"; 
    const APIKEY_WRITE  = "write";   
    const APIKEY_READ   = "read"; 

    /**
     * LogLevel
     */
    const TS3_LOG_CRITICAL = 0;
    const TS3_LOG_ERROR = 1;
    const TS3_LOG_WARNING = 2;
    const TS3_LOG_DEBUG = 3;
    const TS3_LOG_INFO = 4;
    const TS3_LOG_DEVEL = 5;

    /**
     * Client types
     */
    const QueryClient = 1;
    const NormalClient = 0;


    const PRIVELEGE_KEY_SERVER_GROUP = 0;
    const PRIVELEGE_KEY_CHANNEL_GROUP = 1;


    const MESSAGE_READED = 1;
    const MESSAGE_UNREADED = 0;

    const BAN_PERM = 0;

    const SERVERGROUP_TEMPLATE = 0;
    const SERVERGROUP_NORMAL = 1;
    const SERVERGROUP_QUERY = 2;

    const TEXT_MESSAGE_CLIENT = 1;
    const TEXT_MESSAGE_CHANNEl = 2;
    const TEXT_MESSAGE_SERVER = 3;
    
    /**
     * Factory - Creating a connection to TeamSpeak 3
     *
     * @param string $uri
     * 
     * serverquery://serveradmin:password@127.0.1.1:10011 - connect to instance, cant manage server without serverselect 
     * @return Host
     * 
     * serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987 - connect to instance, and select server 
     * @return Server
     * 
     * serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900 - connect to instance, select server and switch to channel 
     * @return Server
     * 
     * serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900&client_name=hej - connect to instance, select server, switch to channel and set nickname 
     * @return Server
     * 
     * serverquery://serveradmin:password@127.0.1.1:10011?server_port=9987&channel_id=2900&client_name=hej&debug=1 - connect to instance, select server, switch to channel and set nickname. Enable debug logging
     * @return Server
     * 
     */
    public static function factory(string $uri)
    { 
        $uri = new Uri($uri);

        $uri->isValid() or throw new Exception("Uri is invalid");

        $adapter = self::getAdapter($uri->getType());
        $adapter = __NAMESPACE__ . '\Adapters\\' . $adapter;

        $settings = array(
            'host' => $uri->getHost(),
            'port' => $uri->getPort(),
            'timeout' => $uri->getTimeout(),
        );


        Debug::$enable = $uri->getDebug();

        $instance = new $adapter($settings);

        if($instance instanceof ServerQuery)
        { 
            $node = $instance->getHost();

            if($uri->hasUserAndPassword())
            {
                $login = $node->login($uri->getUser(), $uri->getPass());
                if(!$login['success'])
                {
                    throw new Exception($login['message']);
                }
            }
            if($uri->hasServerPort())
            { 
                if($uri->hasClientName())
                {
                    $select = $node->selectServer($uri->getServerPort(), 'port', false, $uri->getClientName());
                } else { 
                    $select = $node->selectServer($uri->getServerPort(), 'port', false);
                }
            } elseif($uri->hasServerId())
            { 
                if($uri->hasClientName())
                {
                    $select = $node->selectServer($uri->getServerId(), 'sid', false, $uri->getClientName());
                } else { 
                    $select = $node->selectServer($uri->getServerId(), 'sid', false);
                }
            }

            if(isset($select))
            {
                $node = $select;
            }

            if($node instanceof Server)
            { 
                if($uri->hasChannelId())
                { 
                    $node->channelSwitch($uri->getChannelId());
                }
            }
            return $node;
        } elseif($instance instanceof FileTransfer)
        {
            return $instance->getFtp();
        }
        return $instance;
        
    }

    /**
     * getAdapter
     *
     * @param string $name
     * @return string
     */
    public static function getAdapter(string $name): string
    { 
        switch(strtolower($name))
        { 
            case "serverquery":
                return "ServerQuery";
            case "filetransfer":
                return "FileTransfer";
            default:
                throw new Exception("Invalid adapter name");
        }
    }
}
