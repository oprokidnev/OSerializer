<?php

namespace OSerializer;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;
use Zend\Session\Container;

class Module implements \Zend\ModuleManager\Feature\AutoloaderProviderInterface
{

    public function getConfig()
    {
        return require dirname(dirname(__DIR__)) . '/config/module.config.php';
    }
    
    public function onBootstrap(){
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace("OSerializer\Mapping", __DIR__ . "/Mapping");
    }

    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        ];
    }

}
