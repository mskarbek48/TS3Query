<?php
/*
 * FTP.php
 *
 * Created on Sat Feb 05 2022 17:32:59
 *
 * PHP Version 8.1.2
 *
 * @package      Lukieer\TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */
namespace Lukieer\TS3Query\Node;

use Lukieer\TS3Query\Adapters\FileTransfer;
use Lukieer\TS3Query\Transport\Transport;
use Lukieer\TS3Query\Exception;
use Lukieer\TS3Query\TS3Query;

/**
 * @class FTP
 * @brief TeamSpeak 3 FTP instance
 */
class FTP {

    /**
     * FileTransfer adapter
     *
     * @var FileTransfer
     */
    public FileTransfer $parent;


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
     * Host constructor - Create a connection to TeamSpeak 3 server
     *
     * @param FileTransfer $instance
     */
    public function __construct(FileTransfer $instance)
    {
        $this->parent = $instance;
        $this->socket = @fsockopen($this->parent->options['host'], $this->parent->options['port'], $error, $error_string, $this->parent->options['timeout']);
        $this->transport = new Transport($this->socket);
    }

    public function close()
    {
        $this->transport->close();
        unset($this->transport);
    }

    public function sendFtKey($key)
    {
        return $this->transport->send($key);
    }

    public function getContent($length)
    {
        $content = '';
        while(strlen($content) < $length)
        {    
            $content .= $this->transport->get();
        }
        return $content;
    }

}
