<?php

namespace OSerializer\Serializer\Filter;

/**
 *
 * @author oprokidnev
 */
class MetadataFilter
    implements \Zend\ServiceManager\ServiceLocatorAwareInterface
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
        } else {
            return false;
        }

        return !$entityManager->getMetadataFactory()->isTransient($object);
    }

    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        $serviceLocator = $this->getServiceLocator();
        return $serviceLocator->get(\Doctrine\ORM\EntityManager::class);
    }

    public function filter($property, $value, $targetObject, $Serializer)
    {
        if ($this->isEntity($value)) {
            $annotationReader = $this->getAnnotationReader();

            $reflClass    = new \ReflectionClass(get_class($targetObject));
            $reflProperty = $reflClass->getProperty($property);

            $embeddedAnnotation = $annotationReader->getPropertyAnnotation($reflProperty, \OSerializer\Serializer\Annotation\Embedded::class);

            /* @var $excludeAnnotation \OSerializer\Mapping\Exclude */
            if ((null !== $embeddedAnnotation)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

}
