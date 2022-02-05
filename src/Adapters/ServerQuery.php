<?php 
/*
 * ServerQuery.php
 *
 * Created on Mon Jan 31 2022 20:20:14
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 */

namespace Lukieer\TS3Query\Adapters;

use Lukieer\TS3Query\Node\Host;
use Lukieer\TS3Query\TS3Query;
use Lukieer\TS3Query\Helpers\Debug;

/** 
 * @class   ServerQuery
 * @brief   Adapter for TeamSpeak 3 ServerQuery
 */
class ServerQuery {

    /**
     * Stores ServerQuery options
     *
     * @var array
     */ 
    public array $options;


    /**
     * Stores Host object
     *
     * @var Host
     */
    private Host $host;
    

    /**
     * __construct
     *
     * @param  array $options
     * @return void
     */
    public function __construct(array $options) 
    {
        $this->options = $options;
        $this->host = new Host($this);
    }
    
        
    /**
     * getHost
     *
     * @return Host
     */
    public function getHost(): Host
    {
        return $this->host;
    }
    
        
    /**
     * Escape
     *
     * @param  mixed $text
     * @return mixed
     */
    public function Escape(mixed $text): mixed
    { 
        if(is_null($text))
        {
            return null;
        }
        if(!is_array($text))
        { 
            return str_replace(array_keys(TS3Query::TS3_ESCAPE), TS3Query::TS3_ESCAPE, $text);
        } else { 
            $return = array();
            foreach($text as $key => $once)
            { 
                $return[$key] = str_replace(array_keys(TS3Query::TS3_ESCAPE), TS3Query::TS3_ESCAPE, $once);
            }
            return $return;
        }
    }
    
        
    /**
     * UnEscape
     *
     * @param  mixed $text
     * @return mixed
     */
    public function UnEscape(mixed $text): mixed
    { 
        if(!is_array($text))
        { 
            return str_replace(array_keys(TS3Query::TS3_UNESCAPE), TS3Query::TS3_UNESCAPE, $text);
        } else { 
            $return = array();
            foreach($text as $key => $once)
            { 
                $return[$key] = str_replace(array_keys(TS3Query::TS3_UNESCAPE), TS3Query::TS3_UNESCAPE, $once);
            }
            return $return;
        }
    }
    
        
    /**
     * toArray
     *
     * @param  string $string
     * @param  string $mode
     * @return array
     */
    public function toArray(string $string, int $mode = 1): array
    { 
        $return = [];
        if($mode == TS3Query::ARRAY_ONCE)
        {
            $matches = explode(' ', $string);
            foreach($matches as $match)
            { 
                $matches_once = explode('=',$match);
                if(isset($matches_once[1]))
                {
                    $return[$this->UnEscape($matches_once[0])] = $this->UnEscape($matches_once[1]);
                } else { 
                    $return[$this->UnEscape($matches_once[0])] = NULL;
                }
            }
        } elseif($mode == TS3Query::ARRAY_MULTI)
        { 
            $ex = explode("|",$string);
            $i = 0;
            foreach($ex as $string)
            { 
                $i++;
                $matches = explode(' ', $string);
                foreach($matches as $match)
                { 
                    $matches_once = explode('=',$match);
                    if(isset($matches_once[1]))
                    {
                        if($this->UnEscape($matches_once[0]) == 'client_servergroups')
                        {
                            $return[$i]['client_servergroups_list'] = explode(",",$this->UnEscape($matches_once[1]));
                        }
                        $return[$i][$this->UnEscape($matches_once[0])] = $this->UnEscape($matches_once[1]);
                    }
                }
            }
        }
        return $return;
    }
    
        
    /**
     * removeLine
     *
     * @param  string $text
     * @return string
     */
    public function removeLine(string $text): string
    { 
        return str_replace("\n", "", $text);
    }
    
        
    /**
     * formatError
     *
     * @param  array $results
     * @return string
     */
    public function formatError(array $results): string
    {
        $msg = "error({$results['id']}) ";
        $msg .= $this->removeLine($results['msg']);
        if(isset($results['extra_msg']))
        {
            $msg .= ' ('.$this->removeLine($results['extra_msg']).')';
        }

        return $msg;
    }
    
        
    /**
     * checkResponse
     *
     * @param  array $response
     * @return array
     */
    public function checkResponse(array $response): array
    { 

        $results = $this->toArray($response['results'], TS3Query::ARRAY_ONCE);
        
        if($results['id'] != TS3Query::SUCCESS)
        { 
            $error = $this->formatError($results);
            $output = $this->formatOutput(false, $error, []);

            Debug::addDebugLog($error);

        } else {
            $output = $this->formatOutput(true, $this->removeLine($results['msg']), $this->getData($response['data']));
        }

        return $output;
    }
    
        
    /**
     * getData
     *
     * @param  string $data
     * @return array
     */
    public function getData(string $data): array
    { 

        if(strlen($data) == 0)
        { 
            return array();
        } else {

            if(str_contains($data, "|"))
            { 
                return $this->toArray($data, TS3Query::ARRAY_MULTI);
            } else { 
                return $this->toArray($data, TS3Query::ARRAY_ONCE);
            }
        }
    }
    
        
    /**
     * formatOutput
     *
     * @param  bool $success
     * @param  string $results
     * @param  array $data
     * @return array
     */
    public function formatOutput(bool $success, string $results, array $data): array
    { 
        return array('success' => $success, 'message' => $results, 'data' => $data);
    }


    public function hashUid($uid)
    { 
        $c = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p');
        $huid = '';
  
        for ($i = 0; $i <= 19; $i++) {
            $char = ord(substr(base64_decode($uid), $i, 1));
            $huid .= $c[($char & 0xF0) >> 4];
            $huid .= $c[$char & 0x0F];
        }

        return $huid;
    }
    
        
    /**
     * prepareCommand - Prepare command to execute in transport
     *
     * @param  string $cmd
     * @param  array $params
     * @param  array $options
     * @return string
     */
    public function prepareCommand(string $cmd, array $params, array $options = array()): string
    { 
        $command = $cmd;
        foreach($params as $key => $once)
        {
            if(!is_null($once))
            { 
                $command .= " " . $key . '=' . $this->Escape($once);
            }
            
        }

        if(!empty($options))
        {
            $command .= ' -'.implode(" -",$options);
        }

        return $command;
    }


}