<?php
/*
 * Transport.php
 *
 * Created on Mon Jan 31 2022 20:21:03
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Transport;

use Lukieer\TS3Query\TS3Query;
use Lukieer\TS3Query\Exception;

/**
 * @class Transport
 * @breif Transport for ServerQuery TCP
 */
class Transport {


     /**
     * TeamSpeak 3 Socket
     *
     * @var resource
     */
    private $socket;

    /**
     * Stores event cache
     *
     * @var array
     */
    public array $event_cache = array();


    /**
     * Close
     *
     * @return void
     */
    public function close()
    { 
        fclose($this->socket);
        unset($this->socket);
    }

    
    /**
     * Transport constructor
     *
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    
    /**
     * send
     *
     * @param string $cmd
     * @return void
     */
    public function send(string $cmd)
    { 
        fputs($this->socket, $cmd);
    }


    /**
     * get
     *
     * @return string
     */
    public function get($size = 4096)
    {
        $d = fgets($this->socket, $size);

        return $d;
    }


    /**
     * executeCommand
     *
     * @param string $cmd
     * @return array
     */
    public function executeCommand(string $cmd): array
    { 
        print_r($cmd.PHP_EOL);

        if(is_resource($this->socket))
        { 
            $prepare = str_split($cmd, TS3Query::COMMAND_SPLIT);
            $prepare[array_key_last($prepare)] .= TS3Query::S_LINE;
    
            foreach($prepare as $command_part)
            { 
                $this->send($command_part);
            }
            $results = '';
            $response = '';
            do {
    
                $line = $this->get();
    
                if(str_contains($line, TS3Query::TS3_EVENT))
                {
                    $this->event_cache[] = $line;
                } 
                elseif(str_contains($line, 'error id='))
                { 
                    $results .= $line;
                } 
                else
                {
                    $response .= $line;
                }
    
            } while(strlen($results) == 0);
            return array('data' => $response, 'results' => $results);
        } else {
            throw new Exception("Connection lost.");
        }
    }
}