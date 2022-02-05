<?php
/*
 * Server.php
 *
 * Created on Mon Jan 31 2022 20:20:44
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Node;

use Lukieer\TS3Query\TS3Query;
use Lukieer\TS3Query\Exception;
use Lukieer\TS3Query\Adapters\ServerQuery;
use Lukieer\TS3Query\Transport\Transport;

/**
 * @class Server
 * @biref Instance of ServerQuery Server TeamSpeak 3
 */
class Server {

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
    

    /**
     * Stores bot information
     *
     * @var array
     */
    public array $bot_data;


    /**
     * Stores server id/uid/port
     *
     * @var mixed
     */
    public mixed $server;

    /**
     * Constructor of Server instance
     *
     * @param Host $Host
     * @param mixed $server
     */
    public function __construct(Host $Host, mixed $server)
    {
        $this->parent = $Host->parent;
        $this->transport = $Host->transport;
        $this->socket = $Host->socket;
        $this->info = $server;
        $this->host = $Host;

        $whoAmI = $this->whoAmI()['data'];
        
        $this->bot_data = array('client_id' => $whoAmI['client_id'], 'channel_id' => $whoAmI['client_channel_id'], 'client_nickname' => $whoAmI['client_nickname'], 'client_database_id' => $whoAmI['client_database_id'], 'client_login' => $whoAmI['client_login_name'], 'client_unique_identifier' => $whoAmI['client_unique_identifier']);
    }

    public function host()
    {
        return $this->host;
    }

    /** 
     * Events
     * 
     * Now you can catch event from teamspeak server! Simply use this funcion, and catchEvent or waitForEvent
     * to get event/events data 
     */
    public function serverNotifyRegister($event = 'server', $channel_id = 0)
    {

        if($event == 'all')
        { 
            $this->host->executeCommand("servernotifyregister event=server");
            $this->host->executeCommand("servernotifyregister event=channel id={$channel_id}");
            $this->host->executeCommand("servernotifyregister event=textserver");
            $this->host->executeCommand("servernotifyregister event=textchannel");
            $this->host->executeCommand("servernotifyregister event=textprivate");
        } else {
            $param['event'] = $event;
            $param['id'] = $channel_id;
    
            return $this->host->executeCommand("servernotifyregister", $param);
        }
    }

    /**
     * getEvents
     * 
     * if you set wait to 1, your application is wait for event, to execute next lines of code...
     * if you set wait to 0, your application must execute other commands in loop to get the events
     * 
     * WARNING! 
     * If you set wait to 0, and use function in loop without other teamspeak 3 command, function is never end!
     * For example:
     *  - If you creating a application with events and intervals, you can use parametr 0, for example:
     * 
     *      while(true) { 
     *          $events = $ts3->getEvents(0); 
     *          foreach($events as $event)
     *          {
     *              if($event['name'] == 'notifycliententerview)
     *              {
     *                  $ts3->sendMessage(1, $event['clid'], "Hello");
     *              }
     *          }
     *          $ts3->version(); # Reset idle time, important
     *          $clientList = $ts3->clientList(); # Every loop!
     *      }
     * 
     * - If you creating a application which always wait for event, your code shoudl be for example: 
     * 
     *      while(true)
     *      {
     *          $events = $ts3->getEvents(1);
     *          foreach($events as $event)
     *          {
     *              if($event['name'] == 'notifycliententerview)
     *              {
     *                  $ts3->sendMessage(1, $event['clid'], "Hello");
     *              }
     *          }
     *          $ts3->version(); # Reset idle time, important
     *          $clientList = $ts3->clientList(); # Only when event is called!
     *      }
     * 
     */
    public function getEvents($wait = 0)
    {
        switch($wait)
        {
            case 1:
                $events = array();
                if(!empty($this->transport->event_cache))
                { 
                    foreach($this->transport->event_cache as $key => $event)
                    {
                        $events[$key] = $this->parent->toArray($event);
                        unset($this->transport->event_cache[$key]);
                    }
                } else {
                    $events[] = $this->parent->toArray(fgets($this->socket, 4096));
                }
                break;
            case 0:
                $events = array();
                if(!empty($this->transport->event_cache))
                { 
                    foreach($this->transport->event_cache as $key => $event)
                    {
                        $events[$key] = $this->parent->toArray($event);
                        unset($this->transport->event_cache[$key]);
                    }
                }
                break;
            default:
                throw new Exception("Bad argument");
        }
        return $events;
    }
    
    
    /**
     * whoAmI - Return information about you (serverquery)
     *
     * @return array
     */
    public function whoAmI(): array
    {
        return $this->host->executeCommand("whoami");
    }

    public function serverInfo() : array 
    {
        return $this->host->executeCommand("serverinfo");
    }

    public function serverReqestConnectionInfo() : array 
    {
        return $this->host->executeCommand("serverrequestconnectioninfo");
    }

    public function serverEdit($params) : array 
    {
        return $this->host->executeCommand("serveredit", $params);
    }

    public function serverGroupList(): array 
    { 
        return $this->host->executeCommand("servergrouplist");
    }


    public function serverGroupAdd($name, $type = TS3Query::SERVERGROUP_NORMAL) : array 
    {
        return $this->host->executeCommand("servergroupadd", array("name" => $name, "type" => $type));
    }
    public function serverGroupDel($sgid, $force = 0) : array 
    {
        return $this->host->executeCommand("servergroupdel", array("sgid" => $sgid, "force" => $force));
    }

    public function serverGroupCopy($from, $name, $to = 0, $type = TS3Query::SERVERGROUP_NORMAL): array 
    { 
        return $this->host->executeCommand("servergroupcopy", array("ssgid" => $from, "tsgid" => $to, "name" => $name, "type" => $type));
    }

    public function serverGroupRename($sgid, $name) : array 
    {
        return $this->host->executeCommand("servergrouprename", array("sgid" => $sgid, "name" => $name));
    }

    public function serverGroupPermList($sgid, $permsid = false) : array 
    {
        $options = array();
        if($permsid)
        { 
            $options[] = 'permsid';
        }
        return $this->host->executeCommand("servergrouppermlist", array("sgid" => $sgid), $options);
    }

    public function serverGroupAddPerm($sgid, $permissions)
    {

    }

    public function serverGroupDelPerm($sgid, $permissions)
    {
        
    }

    public function serverGroupAddClient($sgid, $cldbid) : array 
    { 
        return $this->host->executeCommand("servergroupaddclient", array("sgid" => $sgid, "cldbid" => $cldbid));
    }

    public function serverGroupDelClient($sgid, $cldbid) : array 
    { 
        return $this->host->executeCommand("servergroupdelclient", array("sgid" => $sgid, "cldbid" => $cldbid));
    }

    public function serverGroupClientList($sgid, $names = false) : array 
    {
        $options = array();
        if($names)
        { 
            $options[] = 'names';
        }
        return $this->host->executeCommand("servergroupclientlist", array("sgid" => $sgid), $options);
    }

    public function serverGroupsByClientId($cldbid) : array 
    {
        return $this->host->executeCommand("servergroupsbyclientid", array("cldbid" => $cldbid));
    }

    public function serverGroupAutoAddPerm()
    {

    }

    public function serverGroupAutoDelPerm()
    {

    }

    public function serverSnapshotCreate() : array 
    {
        return $this->host->executeCommand("serversnapshotcreate");
    }

    public function serverSnapshotDeploy($snapshot, $mapping = false) : array 
    {
        if($mapping)
        {
            $mapping = "-mapping";
        } else {
            $mapping = '';
        }
        return $this->host->executeCommand("serversnapshotdeploy $mapping $snapshot");
    }

    public function serverNotifyUnregister() : array 
    {
        return $this->host->executeCommand("servernotifyunregister");
    }

    public function sendTextMessage($targetmode, $target, $msg) : array 
    {
        return $this->host->executeCommand("sendtextmessage", array("targetmode" => $targetmode, "target" => $target, "msg" => $msg));
    }

    public function serverLogView(int $lines, int $reverse = 0, int $begin_pos = 0) : array 
    {
        return $this->host->executeCommand("logview", array("lines" => $lines, "reverse" => $reverse, "instance" => 0, "begin_pos" => $begin_pos));
    }

    public function logAdd(int $logLevel = TS3Query::TS3_LOG_INFO, string $msg = "Hello"): array
    {
        return $this->host->executeCommand("logadd", array("loglevel" => $logLevel, "logmsg" => $msg));
    }

    public function channelList($options) : array 
    {
        return $this->host->executeCommand("channellist", array(), $options);
    }

    public function channelInfo($cid) : array 
    {
        return $this->host->executeCommand("channelinfo", array("cid" => $cid));
    }

    public function channelFind($name) : array 
    {
        return $this->host->executeCommand("channelfind", array("pattern" => $name));
    }

    public function channelMove($cid, $cpid, $order = 0) : array 
    {
        return $this->host->executeCommand("channelmove", array("cid" => $cid, "cpid" => $cpid, "order" => $order));
    }

    public function channelCreate($params) : array 
    {
        return $this->host->executeCommand("channelcreate", $params);
    }

    public function channelEdit($cid, $param) : array 
    {
        $param['cid'] = $cid;
        return $this->host->executeCommand("channeledit", $param);
    }

    public function channelGroupList() : array 
    {
        return $this->host->executeCommand("channelgrouplist");
    }

    public function channelGroupAdd($name, $type = TS3Query::SERVERGROUP_NORMAL)
    {
        return $this->host->executeCommand("channelgroupadd", array("name" => $name, "type" => $type));
    }


    public function channelGroupDel($cgid, $force = 0) : array 
    {
        return $this->host->executeCommand("channelgroupdel", array("cgid" => $cgid, "force" => $force));
    }

    public function channelGroupCopy($from, $name, $to = 0, $type = TS3Query::SERVERGROUP_NORMAL): array 
    { 
        return $this->host->executeCommand("channelgroupcopy", array("scgid" => $from, "tcgid" => $to, "name" => $name, "type" => $type));
    }

    public function channelGroupRename($cgid, $name)  : array 
    {
        return $this->host->executeCommand("channelgrouprename", array("cgid" => $cgid, "name" => $name));
    }

    public function channelGroupPermList($cgid, $permsid = false)  : array 
    {
        $options = array();
        if($permsid)
        { 
            $options[] = 'permsid';
        }
        return $this->host->executeCommand("channelgrouppermlist", array("cgid" => $cgid), $options);
    }

    public function channelGroupAddPerm($sgid, $permissions)
    {

    }

    public function channelGroupDelPerm($sgid, $permissions)
    {
        
    }

    public function channelGroupClientList(int $cid = null, int $cldbid = null, int $cgid = null) : array 
    {
        return $this->host->executeCommand("channelgroupclientlist", array("cid" => $cid, "cldbid" => $cldbid, "cgid" => $cgid));
    }

    public function setClientChannelGroup(int $cgid, int $cid, int $cldbid) : array 
    { 
        return $this->host->executeCommand("setclientchannelgroup", array("cgid" => $cgid, "cid" => $cid, "cldbid" => $cldbid));
    }

    public function channelPermList(int $cid) : array 
    {
        return $this->host->executeCommand("channelpermlist", array("cid" => $cid));
    }

    public function channelAddPerm()
    {

    }

    public function channelDelPerm()
    {

    }



    /**
     * ftInitUpload
     *
     * @param mixed $clientftfid
     * @param integer $cid
     * @param string $name
     * @param integer $size
     * @param string $cpw
     * @param boolean $overwrite
     * @param boolean $resume
     * @return array
     */
    public function ftInitUpload(mixed $clientftfid, int $cid, string $name, int $size, string $cpw = "", bool $overwrite = false, bool $resume = false): array 
    {
        return $this->host->executeCommand("ftinitupload", array("clientftfid" => $clientftfid, "cid" => $cid, "name" => $name, "cpw" => $cpw, "size" => $size, "overwrite" => $overwrite, "resume" => $resume));
    }


    
    /**
     * ftInitDownload
     *
     * @param mixed $clientftfid
     * @param integer $cid
     * @param string $name
     * @param string $cpw
     * @param integer $seekpos
     * @return array
     */
    public function ftInitDownload(mixed $clientftfid, int $cid, string $name, string $cpw = "", int $seekpos = 0): array 
    {
        return $this->host->executeCommand("ftinitdownload", array("clientftfid" => $clientftfid, "name" => $name, "cid" => $cid,  "cpw" => $cpw, "seekpos" => $seekpos));
    }


    /**
     * ftList
     *
     * @return array
     */
    public function ftList(): array 
    {
        return $this->host->executeCommand("ftlist");
    }

    /**
     * ftStop
     *
     * @param integer $serverftfid
     * @param boolean $delete
     * @return array
     */
    public function ftStop(int $serverftfid, bool $delete = false): array 
    {
        return $this->host->executeCommand("ftstop", array("serverftfid" => $serverftfid, "delete" => $delete));
    }



    /**
     * ftGetFileInfo
     *
     * @param integer $cid
     * @param string $file
     * @param string $cpw
     * @return array
     */
    public function ftGetFileInfo(int $cid, string $file, string $cpw = '') : array
    {
        return $this->host->executeCommand("ftgetfileinfo", array("cid" => $cid, "cpw" => $cpw, "name" => $file));
    }
    

    /**
     * downloadClientAvatar
     *
     * @param string $uid
     * @param integer|bool $base64_encode
     * @return array
     */
    public function downloadClientAvatar(string $uid, int|bool $base64_encode = 1) : array
    {
        $huid = $this->parent->hashUid($uid);
        
        $fileExists = $this->ftGetFileInfo(0, '/avatar_'.$huid);

        if($fileExists['success'])
        { 
            $init = $this->ftInitDownload(rand(0x0000, 0xFFFF), 0, '/avatar_'.$huid);
            if($init['success'])
            { 
                $downloadedFile = $this->downloadFile($init);
                if($base64_encode)
                {
                    $downloadedFile = base64_encode($downloadedFile);
                }
                if(strlen($downloadedFile) > 0)
                {
                    return $this->parent->formatOutput(true, 'OK', ['file' => $downloadedFile]);
                } else {
                    return $this->parent->formatOutput(false, 'Error while get content', []);
                }
            } else {
                return $this->parent->formatOutput(false, 'Init error', []);
            }
        } else {
            return $this->parent->formatOutput(false, 'File doesn\'t exists', []);
        }
    }


    /**
     * downloadIconById
     *
     * @param integer $id
     * @param integer $base64_encode
     * @return array
     */
    public function downloadIconById(int $id, int|bool $base64_encode = 1) : array
    {
        $fileExists = $this->ftGetFileInfo(0, '/icon_'.$id);
        if($fileExists['success'])
        { 
            $init = $this->ftInitDownload(rand(0x0000, 0xFFFF), 0, '/icon_'.$id);
            if($init['success'])
            { 
                $downloadedFile = $this->downloadFile($init);
                if($base64_encode)
                {
                    $downloadedFile = base64_encode($downloadedFile);
                }
                if(strlen($downloadedFile) > 0)
                {
                    return $this->parent->formatOutput(true, 'OK', ['file' => $downloadedFile]);
                } else {
                    return $this->parent->formatOutput(false, 'Error while get content', []);
                }
            } else {
                return $this->parent->formatOutput(false, 'Init error', []);
            }
        } else {
            return $this->parent->formatOutput(false, 'File doesn\'t exists', []);
        }
    }


    /**
     * downloadIconByServerGroupId
     *
     * @param integer $sgid
     * @return array
     */
    public function downloadIconByServerGroupId(int $sgid) : array
    { 
        foreach($this->serverGroupList()['data'] as $group)
        { 
            if($group['sgid'] == $sgid)
            {
                if($group['iconid'] != 0)
                {
                    return $this->downloadIconById($group['iconid']);
                } else {
                    return $this->parent->formatOutput(false, 'This group doesn\'t have icon', []);
                }
            }
        }
        return $this->parent->formatOutput(false, 'Unknown error', []);
    }

    /**
     * downloadFile
     *
     * @param array $init
     * @return array
     */
    public function downloadFile(array $init) : array
    {
        $fileTransfer = TS3Query::factory("filetransfer://" . $this->host->getHostAddress() . ":" . $init['data']['port'] . "");
        $fileTransfer->sendFtKey($init['data']['ftkey']);
        $data = $fileTransfer->getContent($init['data']['size']);
        $fileTransfer->close();
        unset($fileTransfer);
        return $data;
    }

        
    /**
     * clientMove - Move client to specific channel
     *
     * @param  mixed $clid - Client id
     * @param  mixed $cid - Channel id
     * @param  mixed $cpw - Channel password (optional)
     * @return array
     */
    public function clientMove(int $clid, int $cid, mixed $cpw = ''): array
    {    
        return $this->host->executeCommand("clientmove", array("clid" => $clid, "cid" => $cid, "cpw" => $cpw));
    }
    


    /**
     * channelSwitch - Switch channel
     *
     * @param  int $cid - Channel id
     * @return array
     */
    public function channelSwitch(int $cid): array
    {
        return $this->clientMove($this->bot_data['client_id'], $cid);
    }


    
    /**
     * clientList - Get clientList
     *
     * @param  array $options - Possible parametrs: -uid, -away, -voice, -times, -groups, -info, -icon, -country
     * @return array
     */
    public function clientList(array $options = array()): array
    {
        return $this->host->executeCommand("clientlist", array(), $options);
    }

    
    
    /**
     * clientInfo - Get client informations by client id
     *
     * @param  int $clid - Client id
     * @return array
     */
    public function clientInfo(int $clid): array
    { 
        return $this->host->executeCommand("clientinfo", array('clid' => $clid));
    }


    
    
    /**
     * clientFind - Search client with specific pattern
     *
     * @param  mixed $pattern - Client nickname
     * @return array
     */
    public function clientFind($pattern): array
    { 
        return $this->host->executeCommand("clientfind", array('pattern' => $pattern));
    }



        
    /**
     * clientEdit - edit client
     *
     * @param  int $clid
     * @param  array $params possible params: CLIENT_NICKNAME, CLIENT_DESCRIPTION, CLIENT_ICON_ID
     * @return array
     */
    public function clientEdit(int $clid, array $params): array
    { 
        return $this->host->executeCommand("clientedit clid={$clid}", $params);
    }



    
    
    /**
     * clientDbList - list of clients in database
     *
     * @param  int $start - Start from
     * @param  int $duration - Max clients (max 200)
     * @param  int $count - Add number to client?
     * @return array
     */
    public function clientDbList(int $start = 0, int $duration = 200, $count = false): array
    { 
        $options = array();
        if($count)
        { 
            $options[] = 'count';
        }
        return $this->host->executeCommand("clientdblist", array('start' => $start, 'duration' => $duration), $options);
    }



        
    /**
     * loopClientDbList - returns all clients from teamspeak 3 database.
     *
     * @return array
     */
    public function loopClientDbList(): array
    { 
        $clients = array();
        $start = 0;
        do { 
            $dblist = $this->clientDbList($start)['data'];
            foreach($dblist as $client) 
            {
                $clients[] = $client;
            }
            $start = $start + 200;
        } while(!empty($dblist));

        return $clients;
    }


    /**
     * clientDbInfo
     *
     * @param integer $cldbid
     * @return array
     */
    public function clientDbInfo(int $cldbid): array
    { 
        return $this->host->executeCommand("clientdbinfo", array("cldbid" => $cldbid));
    }


    /**
     * clientDbFind
     *
     * @param string $pattern
     * @param array $options
     * @return array
     */
    public function clientDbFind(string $pattern, array $options = array()): array
    { 
        return $this->host->executeCommand("clientdbfind", array("pattern" => $pattern), $options);
    }


    /**
     * clientDbEdit
     */
    public function clientDbEdit(int $cldbid, array $params): array
    { 
        return $this->host->executeCommand("clientdbedit cldbid={$cldbid}", $params);
    }


    /**
     * clientDbDelete
     *
     * @param integer $cldbid
     * @return array
     */
    public function clientDbDelete(int $cldbid): array
    { 
        return $this->host->executeCommand("clientdbdelete", array('cldbid' => $cldbid));
    }


    /**
     * clientGetIds
     *
     * @param string $cluid
     * @return array
     */
    public function clientGetIds(string $cluid): array
    {
        return $this->host->executeCommand("clientgetids", array('cluid' => $cluid));
    }

    
    /**
     * clientGetDbidFromUid
     *
     * @param string $cluid
     * @return array
     */
    public function clientGetDbidFromUid(string $cluid): array
    {
        return $this->host->executeCommand("clientgetdbidfromuid", array('cluid' => $cluid));
    }


    /**
     * clientGetNameFromUid
     *
     * @param string $cluid
     * @return array
     */
    public function clientGetNameFromUid(string $cluid): array
    {
        return $this->host->executeCommand("clientgetnamefromuid", array('cluid' => $cluid));
    }


    /**
     * clientGetNameFromDbid
     *
     * @param integer $cldbid
     * @return array
     */
    public function clientGetNameFromDbid(int $cldbid): array
    {
        return $this->host->executeCommand("clientgetnamefromdbid", array('cldbid' => $cldbid));
    }


    /**
     * clientGetNameFromClid
     *
     * @param integer $clid
     * @return array
     */
    public function clientGetNameFromClid(int $clid): array
    {
        return $this->host->executeCommand("clientgetnamefromclid", array('clid' => $clid));
    }


    /**
     * clientUpdate
     *
     * @param array $properties
     * @return array
     */
    public function clientUpdate(array $properties): array
    { 
        return $this->host->executeCommand("clientupdate ", $properties);
    }

    /**
     * clientKick
     *
     * @param integer $clid
     * @param integer $reasonid
     * @param string $reasonmsg
     * @return array
     */
    public function clientKick(int $clid, int $reasonid = TS3Query::EVENT_REASON_CHANNEL_KICK, string $reasonmsg = ''): array
    { 
        $params = array("clid" => $clid, "reasonid" => $reasonid);
        if(strlen($reasonmsg) > 0)
        { 
            $params['reasonmsg'] = $reasonmsg;
        }
        return $this->host->executeCommand("clientkick", $params);
    }


    /**
     * clientPoke
     *
     * @param integer $clid
     * @param string $msg
     * @return array
     */
    public function clientPoke(int $clid, string $msg = ''): array
    {
        return $this->host->executeCommand("clientpoke", array('clid' => $clid, 'msg' => $msg));
    }
 
    /**
     * clientPermList
     *
     * @param integer $cldbid
     * @param array $options
     * @return array
     */
    public function clientPermList(int $cldbid, array $options): array
    {
        return $this->host->executeCommand("clientpermlist", array("cldbid" => $cldbid), $options);
    }


    /**
     * clientAddPerm
     *
     * @param integer $cldbid
     * @param array $permissions
     * @return array
     */
    public function clientAddPerm(int $cldbid, array $permissions) : array
    {

        $errors = '';
        if(!empty($permissions))
        {
            $permissions = array_chunk($permissions, 50, true);

            foreach($permissions as $perms)
            {    

                $command = array();

                foreach($perms as $key => $value)
                {
                    $command [] = (is_numeric($key) ? "permid=" : "permsid=") . $this->parent->Escape($key) . " permvalue=".$this->parent->Escape($value[0])." permskip=".$this->parent->Escape($value[1]);
                }

                $command = 'clientaddperm cldbid='.$this->parent->Escape($cldbid).' '.implode(TS3Query::S_LIST,$command);

                $results = $this->host->executeCommand($command);
                if(!$results['success'])
                {
                    $errors = $results['message'] . "\n";
                }

            }
        }
        if(strlen($errors) == 0)
        {
            return $this->parent->formatOutput(true, "OK", array());
        } else {
            return $this->parent->formatOutput(false, $errors, array());
        }
    }


    /**
     * clientDelPerm
     *
     * @param integer $cldbid
     * @param array $permissions
     * @return array
     */
    public function clientDelPerm(int $cldbid, array $permissions) : array
    { 
        $errors = '';
        if(!empty($permissions))
        {
            $permissions = array_chunk($permissions, 50, true);

            foreach($permissions as $perms)
            {    

                $command = array();

                foreach($perms as $value)
                {
                    $command [] = (is_numeric($value) ? "permid=" : "permsid=") . $value;
                }

                $command = 'clientdelperm cldbid='.$this->parent->Escape($cldbid).' '.implode(TS3Query::S_LIST,$command);

                $results = $this->host->executeCommand($command);
                if(!$results['success'])
                {
                    $errors = $results['message'] . "\n";
                }

            }
        }
        if(strlen($errors) == 0)
        {
            return $this->parent->formatOutput(true, "OK", array());
        } else {
            return $this->parent->formatOutput(false, $errors, array());
        }
    }


    /**
     * channelClientPermList
     *
     * @param integer $cid
     * @param integer $cldbid
     * @param boolean $permsid
     * @return array
     */
    public function channelClientPermList(int $cid, int $cldbid, bool|int $permsid = false) : array
    { 
        $options = array();
        if($permsid)
        { 
            $options[] = 'permsid';
        }

        return $this->host->executeCommand("channelclientpermlist",array("cid" => $cid, "cldbid" => $cldbid), $options);
    }


    /**
     * channelClientAddPerm
     *
     * @param integer $cldbid
     * @param integer $cid
     * @param array $permissions
     * @return array
     */
    public function channelClientAddPerm(int $cldbid, int $cid, array $permissions) : array
    {

        $errors = '';
        if(!empty($permissions))
        {
            $permissions = array_chunk($permissions, 50, true);

            foreach($permissions as $perms)
            {    

                $command = array();

                foreach($perms as $key => $value)
                {
                    $command [] = (is_numeric($key) ? "permid=" : "permsid=") . $this->parent->Escape($key) . " permvalue=".$this->parent->Escape($value[0])." permskip=".$this->parent->Escape($value[1]);
                }

                $command = 'channelclientaddperm cid='. $this->parent->Escape($cid) .' cldbid='.$this->parent->Escape($cldbid).' '.implode(TS3Query::S_LIST,$command);

                $results = $this->host->executeCommand($command);
                if(!$results['success'])
                {
                    $errors = $results['message'] . "\n";
                }

            }
        }
        if(strlen($errors) == 0)
        {
            return $this->parent->formatOutput(true, "OK", array());
        } else {
            return $this->parent->formatOutput(false, $errors, array());
        }
    }

    
    /**
     * channelClientDelPerm
     *
     * @param integer $cldbid
     * @param integer $cid
     * @param array $permissions
     * @return array
     */
    public function channelClientDelPerm(int $cldbid, int $cid, array $permissions) : array
    { 
        $errors = '';
        if(!empty($permissions))
        {
            $permissions = array_chunk($permissions, 50, true);

            foreach($permissions as $perms)
            {    

                $command = array();

                foreach($perms as $value)
                {
                    $command [] = (is_numeric($value) ? "permid=" : "permsid=") . $value;
                }

                $command = 'channelclientdelperm cid='. $this->parent->Escape($cid) .' cldbid='.$this->parent->Escape($cldbid).' '.implode(TS3Query::S_LIST,$command);

                $results = $this->host->executeCommand($command);
                if(!$results['success'])
                {
                    $errors = $results['message'] . "\n";
                }

            }
        }
        if(strlen($errors) == 0)
        {
            return $this->parent->formatOutput(true, "OK", array());
        } else {
            return $this->parent->formatOutput(false, $errors, array());
        }
    }

    
    /**
     * permOverView
     *
     * @param integer $cid
     * @param integer $cldbid
     * @param integer $perm
     * @return array
     */
    public function permOverView(int $cid, int $cldbid, int|string $perm = 0) : array
    { 
        $args = array("cid" => $cid, "cldbid" => $cldbid);

        if($perm != 0)
        { 
            if(is_numeric($perm))
            {
                $args['permid'] = $perm;
            } else {
                $args['permsid'] = $perm;
            }
        }

        return $this->host->executeCommand("permoverview", $args);
    }
    
    /**
     * permGet
     *
     * @param integer|string $perm
     * @return array
     */
    public function permGet(int|string $perm) : array
    {
        if(is_numeric($perm))
        {
            $perm = 'permid='.$this->parent->Escape($perm);
        } else {
            $perm = 'permsid='.$this->parent->Escape($perm);
        }
        return $this->host->executeCommand("permget $perm");
    }

    /**
     * permFind
     *
     * @param int|string $perm
     * @return array
     */
    public function permFind(int|string $perm) : array
    {
        if(is_numeric($perm))
        {
            $perm = 'permid='.$this->parent->Escape($perm);
        } else {
            $perm = 'permsid='.$this->parent->Escape($perm);
        }
        return $this->host->executeCommand("permfind $perm");
    }

    /**
     * permReset
     *
     * @return array
     */
    public function permReset() : array
    {
        return $this->host->executeCommand("permreset");
    }


    /**
     * privilegeKeyList
     *
     * @return array
     */
    public function privilegeKeyList() : array
    {
        return $this->host->executeCommand("privilegekeylist");
    }


    /**
     * privilegeKeyAdd
     *
     * @param integer $tokenType - 1 (servergroup), 2 (channelgroup)
     * @param integer $token1 - Server or Channel Group ID
     * @param integer $cid - If tokenType is 2
     * @param string $description - Description of token
     * @param string $customField - Custom fields
     * @return array
     */
    public function pirivilegeKeyAdd(int $tokenType = TS3Query::PRIVELEGE_KEY_SERVER_GROUP, int $token1 = 0, int $cid = 0, string $description = '', string $customSet = '') : array
    {
        return $this->host->executeCommand("privilegekeyadd", array("tokentype" => $tokenType, "token1" => $token1, "token2" => $cid, "description" => $description, "tokencustomset" => $customSet));
    }



    /**
     * privilegeKeyDelete
     *
     * @param string $token
     * @return array
     */
    public function privilegeKeyDelete(string $token) : array
    {
        return $this->host->executeCommand("privilegekeydelete", array("token" => $token));
    }

    /**
     * privilegeKeyUse
     *
     * @param string $token
     * @return array
     */
    public function privilegeKeyUse(string $token): array
    {
        return $this->host->executeCommand("privilegekeyuse", array("token" => $token));
    }

    /**
     * messageList
     *
     * @return array
     */
    public function messageList(): array
    {
        return $this->host->executeCommand("messagelist");
    }


    /**
     * messageAdd
     *
     * @param string $cluid
     * @param string $subject
     * @param string $message
     * @return array
     */
    public function messageAdd(string $cluid, string $subject, string $message): array
    {
        return $this->host->executeCommand("messageadd", array("cluid" => $cluid, "subject" => $subject, "message" => $message));
    }


    /**
     * messageDel
     *
     * @param integer $message_id
     * @return array
     */
    public function messageDel(int $message_id): array
    {
        return $this->host->executeCommand("messagedel", array("msgid" => $message_id));
    }



    /**
     * messageGet
     *
     * @param integer $message_id
     * @return array
     */
    public function messageGet(int $message_id): array
    {
        return $this->host->executeCommand("messageget", array("msgid" => $message_id));
    }


    /**
     * messageUpdateFlag
     *
     * @param integer $message_id
     * @param int $flag
     * @return array
     */
    public function messageUpdateFlag(int $message_id, int $flag = TS3Query::MESSAGE_READED): array
    {
        return $this->host->executeCommand("messageupdateflag", array("msgid" => $message_id, "flag" => $flag));;
    }


    /**
     * complainList
     *
     * @param integer|null $tcldbid
     * @return array
     */
    public function complainList(int $tcldbid = null): array
    {
        $args = [];
        if($tcldbid != null)
        { 
            $args['tcldbid'] = $tcldbid;
        }
        return $this->host->executeCommand("complainlist", $args);
    }


    /**
     * complainAdd
     *
     * @param integer $tcldbid
     * @param string $msg
     * @return array
     */
    public function complainAdd(int $tcldbid, string $msg): array
    {
        return $this->host->executeCommand("complainadd", array("tcldbid" => $tcldbid, "message" => $msg));
    }


    /**
     * complainDelAll
     *
     * @param integer $tcldbid
     * @return array
     */
    public function complainDelAll(int $tcldbid): array
    {
        return $this->host->executeCommand("complaindelall", array("tcldbid" => $tcldbid));
    }


    /**
     * complainDel
     *
     * @param integer $tcldbid
     * @param integer $fcldbid
     * @return array
     */
    public function complainDel(int $tcldbid, int $fcldbid): array
    {
        return $this->host->executeCommand("complaindel", array("tcldbid" => $tcldbid, "fcldbid" => $fcldbid));
    }


    /**
     * banClient
     *
     * @param integer $clid
     * @param int $time
     * @param string $banreason
     * @return array
     */
    public function banClient(int $clid, int $time = TS3Query::BAN_PERM, string $banreason = ''): array
    {
        return $this->host->executeCommand("banclient", array("clid" => $clid, "time" => $time, "banreason" => $banreason));
    }


    /**
     * banList
     *
     * @return array
     */
    public function banList(): array
    {
        return $this->host->executeCommand("banlist");
    }


    /**
     * banAddForIp
     *
     * @param string $ip
     * @param integer $time
     * @param string $banreason
     * @return array
     */
    public function banAddForIp(string $ip, int $time = TS3Query::BAN_PERM, string $banreason = ''): array
    {
        return $this->host->executeCommand("banadd", array("ip" => $ip, "time" => $time, "banreason" => $banreason));
    }


    /**
     * banAddForUid
     *
     * @param string $uid
     * @param integer $time
     * @param string $banreason
     * @return array
     */
    public function banAddForUid(string $uid, int $time = TS3Query::BAN_PERM, string $banreason = ''): array
    {
        return $this->host->executeCommand("banadd", array("uid" => $uid, "time" => $time, "banreason" => $banreason));
    }


    /**
     * banAddForName
     *
     * @param string $name
     * @param int $time
     * @param string $banreason
     * @return array
     */
    public function banAddForName(string $name, int $time = TS3Query::BAN_PERM, string $banreason = ''): array
    {
        return $this->host->executeCommand("banadd", array("name" => $name, "time" => $time, "banreason" => $banreason));
    }


    /**
     * banDel
     *
     * @param int $ban_id
     * @return array
     */
    public function banDel(int $ban_id): array
    {
        return $this->host->executeCommand("bandel", array("banid" => $ban_id));
    }


    /**
     * banDelAll
     *
     * @return array
     */
    public function banDelAll(): array
    {
        return $this->host->executeCommand("bandelall");
    }


    /**
     * customSearch
     *
     * @param mixed $ident
     * @param mixed $pattern
     * @return array
     */
    public function customSearch(string|int $ident, string|int $pattern): array
    {
        return $this->host->executeCommand("customsearch", array("ident" => $ident, "pattern" => $pattern));
    }


    /**
     * customInfo
     *
     * @param integer $cldbid
     * @return array
     */
    public function customInfo(int $cldbid): array
    {
        return $this->host->executeCommand("custominfo", array("cldbid" => $cldbid));
    }
}