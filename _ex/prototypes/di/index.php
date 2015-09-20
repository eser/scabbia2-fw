<?php

class DependencyInjectionContainer
{
    public $parameters;
    protected $serviceDefinitions = [];
    protected $sharedInstances = [];
    
    public function __construct(array $uParameters = [])
    {
        $this->parameters = $uParameters;
    }
    
    public function setService($uService, callable $uCallback, $uIsSharedInstance = true)
    {
        $this->serviceDefinitions[$uService] = [$uCallback, $uIsSharedInstance];
    }
    
    public function getService($uService)
    {
        return $this->serviceDefinitions[$uService][0];
    }
    
    public function hasService($uService)
    {
        return isset($this->serviceDefinitions[$uService]);
    }
    
    public function __get($uName)
    {
        if (array_key_exists($uName, $this->sharedInstances)) {
            return $this->sharedInstances[$uName];
        }
        
        $tService = $this->serviceDefinitions[$uName];
        $tReturn = call_user_func($tService[0], $this->parameters);
        
        if ($tService[1] === true) {
            $this->sharedInstances[$uName] = $tReturn;
        }
        
        return $tReturn;
    }
}

$x = new DependencyInjectionContainer(array('first' => 'second'));
$x->setService(
    'eser',
    function ($parms) {
        $instance = new stdClass();
        $instance->parameter = $parms['first'];
        return $instance;
    }
);

print_r($x->eser);
