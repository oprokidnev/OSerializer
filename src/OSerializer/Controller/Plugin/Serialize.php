<?php

namespace OSerializer\Controller\Plugin;

/**
 * Serializer
 * 
 * @author oprokidnev
 * 
 */
class Serialize extends \Zend\Mvc\Controller\Plugin\AbstractPlugin implements \Zend\ServiceManager\ServiceLocatorAwareInterface
{

    use \Zend\ServiceManager\ServiceLocatorAwareTrait;
    protected $serializer = null;
    protected function getSerializer(){
        if($this->serializer === null){
            $serviceLocator = $this->getServiceLocator() instanceof \Zend\ServiceManager\AbstractPluginManager ? 
                    $this->getServiceLocator()->getServiceLocator() : $this->getServiceLocator();
            $this->serializer = $serviceLocator->get('DoctrineORMSerializer');
        }
        return $this->serializer;
    }
    /**
     * 
     * @param object $object
     * @return array
     */
    public function __invoke($object)
    {
        $serializer = $this->getSerializer();
        return $serializer->serialize($object);
    }

}
