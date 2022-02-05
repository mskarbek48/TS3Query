<?php
/*
 * Host.php
 *
 * Created on Mon Jan 31 2022 20:20:36
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Node;

use Lukieer\TS3Query\Adapters\ServerQuery;
use Lukieer\TS3Query\Transport\Transport;
use Lukieer\TS3Query\Exception;
use Lukieer\TS3Query\TS3Query;

/**
 * @class Host
 * @brief TeamSpeak 3 ServerQuery instance
 */
class Host {

    /**
     * ServerQuery adapter
     *
     * @var ServerQuery
     */
    public ServerQuery $parent;


    /**
     * Transport telnet
     *
     * @var Transport
     */
    public Transport $transport;

    
    /**
     * TeamSpeak 3 Socket
     *
     * @var resource
     */
    public $socket;


    private $permissionList = array();
    


    /**
     * Host constructor - Create a connection to TeamSpeak 3 server
     *
     * @param ServerQuery $instance
     */
    public function __construct(ServerQuery $instance)
    {
        $this->parent = $instance;
        $this->socket = @fsockopen($this->parent->options['host'], $this->parent->options['port'], $error, $error_string, $this->parent->options['timeout']);
        $this->transport = new Transport($this->socket);

        if(str_contains($this->transport->get(), TS3Query::TS3_WELCOME_TELNET))
        { 
            $this->transport->get();
        } else { 
            throw new Exception("Host isn't TeamSpeak 3 instance");
        }

    }

    public function getHostAddress()
    { 
        return $this->parent->options['host'];
    }


    /**
     * executeCommand
     *
     * @param string $command
     * @param array $param
     * @param array $options
     * @return array
     */
    public function executeCommand(string $command, array $param = array(), array $options = array()): array
    { 
        if(is_resource($this->socket))
        { 
            return $this->parent->checkResponse($this->transport->executeCommand($this->parent->prepareCommand($command, $param, $options)));
        } else {
            throw new Exception("Connection lost!");
        }
        
    }

    /**
     * selectServer
     *
     * @param mixed $value
     * @param string $mode
     * @param boolean $virtual
     * @param string $name
     * @return Server
     */
    public function selectServer(mixed $value, string $mode = 'port', bool $virtual = false, string $name = ''): Server
    { 
        $command = 'use ' . $mode . '=' . $value;
        if($virtual)
        {
            $command .= ' -virtual';
        }
        if(strlen($name) != 0)
        {
            $command .= ' client_nickname='.$this->parent->Escape($name);
        }


        $data = $this->parent->checkResponse($this->transport->executeCommand($command));

        if($data['success'])
        {
            return new Server($this, $value);
        } else {
            throw new Exception("Cannot select server: ".$data['message']);
        }
    }


    /**
     * serverList
     *
     * @param array $options
     * @return array
     */
    public function serverList($options = array()): array
    { 
        return $this->executeCommand("serverlist", array(), $options);
    }


    /**
     * serverSelectByName
     *
     * @param string $name
     * @return Server
     */
    public function serverSelectByName(string $name)
    {
        foreach($this->serverList()['data'] as $server)
        { 
            if($server['virtualserver_name'] == $name)
            { 
                return $this->selectServer($server['virtualserver_port']);
            }
        }
        throw new Exception("Cannot select this server");
    }


    /**
     * serverSelectByUid
     *
     * @param string $uid
     * @return Server
     */
    public function serverSelectByUid(string $uid)
    {
        foreach($this->serverList(array("uid"))['data'] as $server)
        { 
            if($server['virtualserver_unique_identifier'] == $uid)
            { 
                return $this->selectServer($server['virtualserver_port']);
            }
        }
        throw new Exception("Cannot select this server");
    }


    /**
     * serverIdGetByUid
     *
     * @param string $uid
     * @return array
     */
    public function serverIdGetByUid(string $uid): array
    {
        foreach($this->serverList(array("uid"))['data'] as $server)
        { 
            if($server['virtualserver_unique_identifier'] == $uid)
            { 
                return $this->parent->formatOutput(true, "OK", ['virtualserver_id' => $server['virutalserver_id']]);
            }
        }
        return $this->parent->formatOutput(false, "Cannot find server", []);
    }

    /**
     * serveridGetByName
     *
     * @param string $name
     * @return array
     */
    public function serverIdGetByName(string $name): array
    {
        foreach($this->serverList(array("uid"))['data'] as $server)
        { 
            if($server['virtualserver_name'] == $name)
            { 
                return $this->parent->formatOutput(true, "OK", ['virtualserver_id' => $server['virutalserver_id']]);
            }
        }
        return $this->parent->formatOutput(false, "Cannot find server", []);
    }
    
    /**
     * serverIdGetByPort
     *
     * @param  int   $port
     * @return array
     */
    public function serverIdGetByPort(int $port): array
    { 
        return $this->executeCommand("serveridgetbyport", array('virtualserver_port' => $port));
    }


    /**
     * Login
     *
     * @param string $login
     * @param string $password
     * @return array
     */
    public function login(string $login, string $password): array
    { 
        return $this->executeCommand("login", array('client_login_name' => $login, 'client_login_password' => $password));
    }


    /**
     * clientUpdate
     *
     * @param array $properties
     * @return array
     */
    public function clientUpdate(array $properties): array
    { 
        return $this->executeCommand("clientupdate ", $properties);
    }


    /**
     * Quit
     *
     * @return array
     */
    public function quit(): array
    {   
        $return = $this->executeCommand("quit");
        $this->socket = null;
        $this->transport->close();
        return $return;
    }

    
    /**
     * Version
     *
     * @return array
     */
    public function version(): array
    { 
        return $this->executeCommand("version");
    }


    /**
     * HostInfo
     *
     * @return array
     */
    public function hostInfo(): array
    { 
        return $this->executeCommand("hostinfo");
    }


    /**
     * instanceInfo
     *
     * @return array
     */
    public function instanceInfo(): array
    { 
        return $this->executeCommand("instanceinfo");
    }


    /**
     * instanceEdit
     *
     * @param [type] $param
     * @return array
     */
    public function instanceEdit($param): array
    { 
        return $this->executeCommand("instanceedit", $param);
    }


    /**
     * bindingList
     *
     * @return array
     */
    public function bindingList(): array
    { 
        return $this->executeCommand("bindinglist");
    }





    /**
     * serverCreate
     *
     * @param array $params
     * @return array
     */
    public function serverCreate(array $params): array
    { 
        return $this->executeCommand("servercreate", $params);
    }


    /**
     * serverDelete
     *
     * @param array $params
     * @return array
     */
    public function serverDelete(int $sid): array
    { 
        return $this->executeCommand("serverdelete",array("sid" => $sid));
    }


    /**
     * serverProcessStop
     *
     * @param string $msg
     * @return array
     */
    public function serverProcessStop(string $msg = null): array
    {
        return $this->executeCommand("serverprocesstop", array("reasonmsg" => $msg));
    }


    /**
     * serverStop
     *
     * @param integer $sid
     * @param string|null $msg
     * @return array
     */
    public function serverStop(int $sid, string $msg = null): array
    {
        return $this->executeCommand("serverstop", array("sid" => $sid, "reasonmsg" => $msg));
    }


    /**
     * serverStart
     *
     * @param integer $sid
     * @return array
     */
    public function serverStart(int $sid): array
    {
        return $this->executeCommand("serverstart", array("sid" => $sid));
    }


    /**
     * logAdd
     *
     * @param int $logLevel
     * @param string $msg
     * @return array
     */
    public function logAdd(int $logLevel = TS3Query::TS3_LOG_INFO, string $msg = "Hello"): array
    {
        return $this->executeCommand("logadd", array("loglevel" => $logLevel, "logmsg" => $msg));
    }


    /**
     * clientSetServerQueryLogin
     *
     * @param string $login_name
     * @return array
     */
    public function clientSetServerQueryLogin(string $login_name): array
    {
        return $this->executeCommand("clientsetserverquerylogin", array('client_login_name' => $login_name));
    }

    
    /**
     * globalMessage
     *
     * @param string $msg
     * @return array
     */
    public function globalMessage(string $msg): array
    { 
        return $this->executeCommand("gm",array("msg" => $msg));
    }


    /**
     * logOut
     *
     * @return array
     */
    public function logOut(): array
    {
        return $this->executeCommand("logout");
    }


    /**
     * instanceLogView
     *
     * @param int $lines
     * @param integer $reverse
     * @param integer $begin_pos
     * @return array
     */
    public function instanceLogView(int $lines, int $reverse = 0, int $begin_pos = 0): array
    {
        return $this->executeCommand("logview", array("lines" => $lines, "reverse" => $reverse, "instance" => 1, "begin_pos" => $begin_pos));
    }


    /**
     * permIdGetByName
     *
     * @param array $permissions
     * @return array
     */
    public function permIdGetByName(array $permissions):array
    {
        $perms = '';
        foreach($permissions as $once)
        { 
            $perms .= 'permsid='.$once;
        }
        return $this->executeCommand("permidgetbyname ".$this->parent->Escape($perms));
    }
    


    /**
     * permissionList
     *
     * @return array
     */
    public function permissionList(): array
    {

        if(empty($this->permissionList))
        {
            $this->permissionList = $this->executeCommand("permissionlist");
        }

        return $this->permissionList;
    }


    /**
     * apiKeyAdd
     *
     * @param [type] $scope
     * @param integer $lifetime
     * @param integer|null $cldbid
     * @return array
     */
    public function apiKeyAdd(string $scope = TS3Query::APIKEY_READ, int $lifetime = 7, int $cldbid = null): array
    {
        return $this->executeCommand("apikeyadd", array("scope" => $scope, "lifetime" => $lifetime, "cldbid" => $cldbid));
    }


    /**
     * apiKeyDel
     *
     * @param integer $id
     * @return array
     */
    public function apiKeyDel(int $id): array
    {
        return $this->executeCommand("apikeydel", array("id" => $id));
    }


    /**
     * apiKeyList
     *
     * @param integer|null $start
     * @param integer|null $duration
     * @param integer|null $cldbid
     * @param boolean $count
     * @return array
     */
    public function apiKeyList(int $start = null, int $duration = null, int $cldbid = null, bool $count = true): array
    { 
        if($count) $count = '-count'; else $count = '';
        return $this->executeCommand("apikeylist $count", array("start" => $start, "duration" => $duration, "cldbid" => $cldbid));
    }


    /**
     * queryLoginList
     *
     * @param integer|null $start
     * @param integer|null $duration
     * @param integer|null $pattern
     * @return array
     */
    public function queryLoginList(int $start = null, int $duration = null, int $pattern = null): array
    { 
        return $this->executeCommand("queryloginlist -count", array("start" => $start, "duration" => $duration, "pattern" => $pattern));
    }


    /**
     * globalQueryLoginAdd
     *
     * @param string $login
     * @return array
     */
    public function globalQueryLoginAdd(string $login): array
    {
        return $this->executeCommand("queryloginadd", array("client_login_name" => $login));
    }


    /**
     * queryLoginDel
     *
     * @param integer $cldbid
     * @return array
     */
    public function queryLoginDel(int $cldbid): array
    {
        return $this->executeCommand("querylogindel", array("cldbid" => $cldbid));
    }


    /**
     * whoAmI
     *
     * @return array
     */
    public function whoAmI(): array
    {
        return $this->executeCommand("whoami");
    }
}
