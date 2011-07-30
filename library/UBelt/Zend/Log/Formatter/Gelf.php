<?php

require_once 'Zend/Log/Formatter/Abstract.php';

class UBelt_Zend_Log_Formatter_Gelf extends Zend_Log_Formatter_Abstract {
	
	const VERSION = '1.0';

	public function format($event) {
		$gelf = array();
		
		$gelf['short_message'] = $event['message'];
		$gelf['timestamp'] = strtotime($event['timestamp']);
		$gelf['level'] = $event['priority'];
		
		$gelf['host'] = $event['host'];
		$gelf['version'] = self::VERSION;
		$gelf['facility'] = $event['facility'];
		
		unset($event['message']);
		unset($event['host']);
		unset($event['version']);
		unset($event['facility']);
		unset($event['timestamp']);
		unset($event['priorityName']);
		unset($event['priority']);
		
		$fields = array('full_message', 'type', 'file', 'line');
		
		foreach ($event as $key=>$value) {
			if (array_key_exists($key, $fields)) {
				$gelf[$key] = $value;
			} else {
				$gelf['_'.$key] = $value;
			}
		}
		
		$this->_validateEvent($gelf);
		
		return json_encode($gelf);
	}
	
	private function _validateEvent(array $event) {
		if (!array_key_exists('short_message', $event)) {
			throw new Exception('Events passed to ' . __CLASS__ . ' should have \'short_message\' key');
		}
		if (!array_key_exists('host', $event)) {
			throw new Exception('Events passed to ' . __CLASS__ . ' should have \'host\' key');
		}
		if (!array_key_exists('version', $event)) {
			throw new Exception('Events passed to ' . __CLASS__ . ' should have \'version\' key');
		}
		if (!array_key_exists('facility', $event)) {
			throw new Exception('Events passed to ' . __CLASS__ . ' should have \'facility\' key');
		}
	}
	
    /**
	 * Factory for UBelt_Zend_Log_Formatter_Gelf class
	 *
	 * @param array|Zend_Config $options
	 * @return Zend_Log_Formatter_Gelf
     */
    public static function factory($options)
    {
        $format = null;
        if (null !== $options) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }

            if (array_key_exists('format', $options)) {
                $format = $options['format'];
            }
        }

        return new self($format);
    }
	
}
