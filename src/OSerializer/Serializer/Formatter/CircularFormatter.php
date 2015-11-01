<?php

namespace OSerializer\Serializer\Formatter;

/**
 * Description of DateTimeFormatter
 *
 * @author oprokidnev
 */
class CircularFormatter implements FormatterInterface, \Zend\ServiceManager\ServiceLocatorAwareInterface
{

    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    
    protected $annotationReader = null;

    /**
     * 
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    protected function getAnnotationReader()
    {
        if ($this->annotationReader === null) {
            $this->annotationReader = $this->getServiceLocator()->get('AnnotationReader');
        }
        return $this->annotationReader;
    }

    /**
     * 
     * @param \DateTime $value
     * @param Entity $targetObject
     * @throws \Exception
     */
    public function format($value, &$property, $targetObject, &$commonData)
    {
        $Serializer         = $this->getServiceLocator()->get(\OSerializer\Serializer\DoctrineObject::class);
        try{
            $this->preventCircular($value, $targetObject, $property, $Serializer);
        } catch (\Exception $ex) {
            throw $ex;
        }
       
        $value = $Serializer->extract($value);
        return $value;
    }

    protected $renderedEntities = [];
    protected static $c         = 0;

    /**
     * 
     * @param object $entity
     */
    protected function preventCircular($value, $targetObject, $property, $serializer)
    {
        $contexts = $serializer->getContexts();
        if (in_array($serializer::objectHash($value,$serializer->getObjectManager()), $contexts)) {
            throw new \Exception(sprintf('Circular serialization found for entity "%s::%s": trying to render entity "%s" in context [%s]',
                $serializer::objectHash($targetObject,$serializer->getObjectManager()), $property,
                $serializer::objectHash($value,$serializer->getObjectManager()), implode(',', $contexts)));
        }
    }

    protected function getEntityManager()
    {
        $serviceLocator = $this->getServiceLocator();
        return $serviceLocator->get(\Doctrine\ORM\EntityManager::class);
    }

    /**
     * @param EntityManager $entityManager
     * @param string|object $object
     *
     * @return boolean
     */
    protected function isEntity($object)
    {
        $entityManager = $this->getEntityManager();
        if (is_object($object)) {
            $object = ($object instanceof \Doctrine\Common\Proxy\Proxy) ? get_parent_class($object)
                    : get_class($object);
        }

        return !$entityManager->getMetadataFactory()->isTransient($object);
    }

    protected function getClass($object)
    {
        if (is_object($object)) {
            $object = ($object instanceof \Doctrine\Common\Proxy\Proxy) ? get_parent_class($object)
                    : get_class($object);
        }
        return $object;
    }

    public function isFormattable($targetEntity, $property, $value)
    {
        return is_object($value) && $this->isEntity($value);
    }

    public function decode($value)
    {
        return (boolean) \DateTime::createFromFormat(\DateTime::ISO8601, $value);
    }

    /**
     * 
     * @param boolean $value
     */
    public function isDecodeable($value)
    {
        return (boolean) \DateTime::createFromFormat(\DateTime::ISO8601, $value);
    }

}
