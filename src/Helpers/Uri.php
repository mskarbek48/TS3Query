<?php
/*
 * Uri.php
 *
 * Created on Mon Jan 31 2022 20:19:22
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Helpers;

/**
 * @class Uri
 * @brief Uri parser for teamspeak 3 serverquery connection
 */
class Uri {

    /**
     * Stores uri scheme
     *
     * @var string
     */
    private $type;


    /**
     * Stores host
     *
     * @var string
     */
    private $host;


    /**
     * Stores port
     *
     * @var integer
     */
    private $port;

    
    /**
     * Stores username
     *
     * @var string
     */
    private $user;

    /**
     * Stores password
     */
    private $pass;

    
    /**
     * Stores server port
     *
     * @var integer
     */
    private $server_port;

    
    /**
     * Stores channel id
     *
     * @var integer
     */
    private $channel_id;


    /**
     * Stores client name
     *
     * @var string
     */
    private $client_name;


    /**
     * Stores timeout
     *
     * @var integer
     */
    private $timeout;


    /**
     * Stores server id
     *
     * @var integer
     */
    private $server_id;


    /**
     * Stores a debug mode
     *
     * @var integer
     */
    private $debug = 0;

    
    /**
     * __construct
     *
     * @param  string $uri
     * @return void
     */
    public function __construct(string $uri)
    {
        $this->parse($uri);
    }
    
    /**
     * parse
     *
     * @param  string $uri
     * @return bool
     */
    private function parse(string $uri): bool
    {
        $parsed = parse_url($uri);
        $this->type = isset($parsed['scheme']) ? $parsed['scheme'] : "";
        $this->host = isset($parsed['host']) ? $parsed['host'] : "";
        $this->port = isset($parsed['port']) ? $parsed['port'] : "";
        $this->user = isset($parsed['user']) ? $parsed['user'] : "";
        $this->pass = isset($parsed['pass']) ? $parsed['pass'] : "";
        if(isset($parsed['query']))
        { 
            parse_str($parsed['query'], $query);
        }
        $this->server_port = isset($query['server_port']) ? $query['server_port'] : "";
        $this->channel_id = isset($query['channel_id']) ? $query['channel_id'] : "";
        $this->client_name = isset($query['client_name']) ? $query['client_name'] : "";
        $this->timeout = isset($query['timeout']) ? $query['timeout'] : "";
        $this->server_id = isset($query['server_id']) ? $query['server_id'] : "";
        $this->debug = isset($query['debug']) ? $query['debug'] : 0;
        return true;
    }

    public function isValid(): bool
    {
        if($this->type != "" && $this->host != "" && $this->port != "")
        {
            return true;
        }
        return false;
    }

    /**
     * getDebug
     *
     * @return int|bool
     */
    public function getDebug()
    {
        return $this->debug;
    }
        
    /**
     * getTimeout
     *
     * @param  int $default
     * @return int
     */
    public function getTimeout(int $default = 2): int
    {
        return strlen($this->timeout) == 0 ? $default : $this->timeout;
    }

    
    /**
     * getType
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * getHost
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }


    /**
     * getPort
     *
     * @return integer
     */
    public function getPort():int
    {
        return $this->port;
    }


    public function hasUserAndPassword(): bool 
    {
        if(!empty($this->user) && !empty($this->pass))
        {
            return true;
        }
        return false;
    }

    /**
     * getUser
     *
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * getPass
     *
     * @return string
     */
    public function getPass(): string
    {
        return $this->pass;
    }

 
    /**
     * hasServerPort
     *
     * @return bool
     */
    public function hasServerPort(): bool
    {
        if(!empty($this->server_port))
        {
            return true;
        }
        return false;
    }

    
    /**
     * hasServerId
     *
     * @return bool
     */
    public function hasServerId(): bool
    {
        if(!empty($this->server_id))
        {
            return true;
        }
        return false;
    }
    

    /**
     * getServerId
     *
     * @return int
     */
    public function getServerId(): int
    {
        return $this->server_id;
    }

        
    /**
     * getServerPort
     *
     * @return int
     */
    public function getServerPort(): int
    {
        return $this->server_port;
    }

        
    /**
     * hasChannelId
     *
     * @return bool
     */
    public function hasChannelId(): bool
    {
        if(!empty($this->channel_id))
        {
            return true;
        }
        return false;
    }

    
    /**
     * getChannelId
     *
     * @return int
     */
    public function getChannelId(): int
    {
        return $this->channel_id;
    }

    
    /**
     * hasClientName
     *
     * @return bool
     */
    public function hasClientName(): bool
    {
        if(!empty($this->client_name))
        {
            return true;
        }
        return false;
    }

        
    /**
     * getClientName
     *
     * @return string
     */
    public function getClientName(): string
    {
        return $this->client_name;
    }

}