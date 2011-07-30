What?
=====
This is a library for PHP to log messages to Graylog2.
It extends the Zend_Log classes so it can be integrated
into your projects already using Zend_Log.

Usage:
======

    $formatter = new UBelt_Zend_Log_Formatter_Gelf();
    $writer = new UBelt_Zend_Log_Writer_Graylog2('my-facility', 'graylog-server');
    $writer->setFormatter($formatter);
    $log = new Zend_Log($writer);

Logging a simple message

    $log->log('something simple');

Logging some additional info

    $log->log('blah blah', Zend_Log::INFO, array(
        'full_message' => 'some more blah blah',
        'file' => __FILE__,
        'line' => __LINE__,
        'misc' => 'somemore fields' // you can pass additional data
    );
