<?php

namespace OSerializer\Serializer\Filter;

/**
 *
 * @author oprokidnev
 */
class AnnotationFilter implements \Zend\ServiceManager\ServiceLocatorAwareInterface
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
    
    public function filter($property, $value, $targetObject, $Serializer)
    {
        if (count($Serializer->getGroups())) {
            $annotationReader  = $this->getAnnotationReader();
            $excludeAnnotation = null;
            $reflClass         = new \ReflectionClass(get_class($targetObject));
            try {
                if ($reflClass->hasProperty($property)) {
                    $reflProperty = $reflClass->getProperty($property);
                    if ($reflProperty !== null) {
                        $excludeAnnotation = $annotationReader->getPropertyAnnotation($reflProperty, \OSerializer\Mapping\Exclude::class);
                    }
                } else {
                    $getter = 'get' . ucfirst($property);
                    if ($reflClass->hasMethod($getter)) {
                        $reflMethod = $reflClass->getMethod($getter);
                        if ($reflMethod !== null) {
                            $excludeAnnotation = $annotationReader->getMethodAnnotation($reflMethod, \OSerializer\Mapping\Exclude::class);
                        }
                    }
                }


                /* @var $excludeAnnotation \OSerializer\Mapping\Exclude */
                if ((null !== $excludeAnnotation)) {
                    $groups = $excludeAnnotation->getGroups();
                } else {
                    $groups = [];
                }
                if (count(array_intersect($Serializer->getGroups(), $groups))) {
                    return false;
                }
            } catch (\Exception $ex) {
                return true;
            }
        }
        return true;
    }

}
