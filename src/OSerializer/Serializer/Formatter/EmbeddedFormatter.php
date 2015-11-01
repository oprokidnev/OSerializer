<?php

namespace OSerializer\Serializer\Formatter;

/**
 * Description of DateTimeFormatter
 *
 * @author oprokidnev
 */
class EmbeddedFormatter implements FormatterInterface, \Zend\ServiceManager\ServiceLocatorAwareInterface,    \DoctrineModule\Persistence\ObjectManagerAwareInterface
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
        $annotationReader = $this->getAnnotationReader();

        $reflClass    = new \ReflectionClass(get_class($targetObject));
        $reflProperty = $reflClass->getProperty($property);

        $embeddedAnnotation = $annotationReader->getPropertyAnnotation($reflProperty,
            \OSerializer\Serializer\Annotation\Embedded::class);


        if ((null !== $embeddedAnnotation)) {
            unset($commonData[$property]);
            $result   = [
                $property => $value
            ];
            $property = '_embedded';
        }else{
            $result = $value;
        }
        return $result;
    }

    protected $renderedEntities = [];
    protected static $c         = 0;
    
    protected $objectManager = null;

    /**
     * 
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function setObjectManager(\Doctrine\Common\Persistence\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * @param EntityManager $entityManager
     * @param string|object $object
     *
     * @return boolean
     */
    protected function isEntity($object)
    {
        $entityManager = $this->getObjectManager();
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
        $em       = $this->getObjectManager();
        $metadata = $em->getMetadataFactory()->getMetadataFor($this->getClass($targetEntity));
        return (is_object($value) && $this->isEntity($value))||isset($metadata->associationMappings[$property]);
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
