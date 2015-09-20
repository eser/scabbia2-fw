<?php

// not working yet
class AopSample extends ManagedClass
{
    public function __construct()
    {
        $this->aopHandler(
            'before',
            'beforeHandler',
            [
                'a',
                'b'
            ]
        );
    }
    
    public function beforeHandler()
    {
        Logger::log('start');
    }
    
    public function _a()
    {
        Logger::log('a');
    }
    
    public function _b()
    {
        Logger::log('b');
    }
}

$sample = new AopSample();
$sample->a(); // will call beforeHandler, then _a
