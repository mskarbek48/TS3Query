<?php 
/*
 * FileTransfer.php
 *
 * Created on Mon Jan 31 2022 20:20:02
 *
 * PHP Version 8.1.2
 *
 * @package      TS3Query
 * @copyright    2019 - 2022 mskarbek.pl. All rights reserved.
 * @author       Maciej 'Lukieer' Skarbek <macieqskarbek@gmail.com>
 * 
 */
namespace Lukieer\TS3Query\Adapters;
use Lukieer\TS3Query\Node\FTP;

class FileTransfer {

    /**
     * Stores FileTransfer options
     *
     * @var array
     */ 
    public array $options;
    

    /**
     * Stores a FTP object
     *
     * @var FTP
     */
    private $ftp;

    public function __construct(array $options) 
    {
        $this->options = $options;
        $this->ftp = new FTP($this);
    }

    public function getFtp()
    {
        return $this->ftp;
    }
}