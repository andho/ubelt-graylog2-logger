<?php
/**
 * Graylog2 logger using Zend_Log
 * The two classes in this package combined will assist you in sending
 * GELF formatted messages to a Graylog2 server.
 * 
 * Copyright (C) 2011  Amjad Mohamed
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Amjad Mohamed <andhos@gmail.com>
 * 
 */

require_once 'Zend/Log/Writer/Abstract.php';

/**
 * Writes log messages to Graylog2 in GELF format
 * 
 * @author amjad
 * 
 * @depends Zend_Log_Writer_Abstract, UBelt_Zend_Log_Formatter_GELF
 *
 */
class UBelt_Zend_Log_Writer_Graylog2 extends Zend_Log_Writer_Abstract {
	
	private $_facility;
	private $_server;
	private $_port;
	private $_host;
	private $_maxChunkSize;
	
	private $_socket;
	
	public function __construct($facility, $graylog_server, $port='12201', $maxChunkSize='LAN') {
		$this->_facility = $facility;
		$this->_server = $graylog_server;
		$this->_port = $port;
		$this->_maxChunkSize = $maxChunkSize;
		
		$this->_host = gethostname();
		
		$this->_setupStreamParameters();
	}
	
    /**
     * Create a new instance of UBelt_Zend_Log_Writer_Graylog2
     *
     * @param  array|Zend_Config $config
     * @return UBelt_Zend_Log_Writer_Graylog2
     */
    static public function factory($config)
    {
        return new self(self::_parseConfig($config));
    }
	
	private function _setupStreamParameters() {
		if (!is_numeric($this->_port)) {
            throw new Exception("Port must be numeric");
        }

        switch ($this->_maxChunkSize) {
            case 'WAN':
                $this->_maxChunkSize = 1420;
                break;
            case 'LAN':
                $this->_maxChunkSize = 8154;
                break;
        }
	}
	
	protected function _write($event) {
		$event['host'] = $this->_host;
		$event['facility'] = $this->_facility;
		
        if ($this->_formatter instanceof Zend_Log_Formatter_Interface) {
            $message = $this->_formatter->format($event);
        }
		
		$message = gzcompress($message);
		
		$this->_openSocket();
	
        // Maximum size is 8192 byte. Split to chunks. (GELFv2 supports chunking)
        if (strlen($message) > $this->_maxChunkSize) {
            // Too big for one datagram. Send in chunks.
            $msgId = microtime(true) . rand(0,10000) . $this->_host;

            $parts = str_split($message, $this->_maxChunkSize);
            $i = 0;
            foreach($parts as $part) {
            	if (fwrite($this->_socket, $this->_prependChunkData($part, $msgId, $i, count($parts))) === false) {
            		throw new Exception('Aborting log. Could not write to socket');
            	}
                $i++;
            }

        } else {
            // Send in one datagram.
            if (fwrite($this->_socket, $message) === false) {
            	throw new Exception('Aborting log. Could not write to socket');
            }
        }
	}
	
	private function _prependChunkData($data, $msgId, $seqNum, $seqCnt) {
        if (!is_string($data) || $data === '') {
            throw new Exception('Data must be a string and not be empty');
        }

        if (!is_integer($seqNum) || !is_integer($seqCnt) || $seqCnt <= 0) {
            throw new Exception('Sequence number and count must be integer. Sequence count must be bigger than 0.');
        }

        if ($seqNum > $seqCnt) {
            throw new Exception('Sequence number must be bigger than sequence count');
        }

        return pack('CC', 30, 15) . hash('sha256', $msgId, true) . pack('nn', $seqNum, $seqCnt) . $data;
    }
    
	private function _openSocket() {
		if (!is_null($this->_socket)) {
			return;
		}
		
		$connection_string = 'udp://' . $this->_server . ':' . $this->_port;
		$this->_socket = stream_socket_client($connection_string);
	}
		
}