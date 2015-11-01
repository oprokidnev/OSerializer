<?php

namespace OSerializer\Serializer\Transformer;

/**
 *
 * @author oprokidnev
 */
class AnnotationTransformer implements TransformerInterface, \Zend\ServiceManager\ServiceLocatorAwareInterface
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

    public function transform($property, $value, $targetObject, $serializer)
    {
        if (count($serializer->getGroups())) {
            $annotationReader    = $this->getAnnotationReader();
            $transformAnnotation = null;
            $reflClass           = new \ReflectionClass(\Doctrine\Common\Util\ClassUtils::getClass($targetObject));
            try {
                if ($reflClass->hasProperty($property)) {
                    $reflProperty = $reflClass->getProperty($property);
                    if ($reflProperty !== null) {
                        $transformAnnotation = $annotationReader->getPropertyAnnotation($reflProperty, \OSerializer\Mapping\Transform::class);
                    }
                } else {
                    $getter = 'get' . ucfirst($property);
                    if ($reflClass->hasMethod($getter)) {
                        $reflMethod = $reflClass->getMethod($getter);
                        if ($reflMethod !== null) {
                            $transformAnnotation = $annotationReader->getMethodAnnotation($reflMethod, \OSerializer\Mapping\Transform::class);
                        }
                    }
                }

                /* @var $transformAnnotation \OSerializer\Mapping\Transform */
                if ((null !== $transformAnnotation)) {
                    $transforms = $transformAnnotation->getTransforms();
                    $groups     = array_keys($transforms);
                } else {
                    return $value;
                }
                $interselection = array_intersect($serializer->getGroups(), $groups);
                if (count($interselection)) {
                    foreach ($interselection as $group) {
                        if (!isset($transforms[$group])) {
                            continue;
                        }
                        if ($value instanceof \Doctrine\Common\Collections\Collection) {
                            if ($transforms[$group] === \OSerializer\Mapping\Transform::TRANSFORM_COLLECTION_IDS) {
                                return $this->transformCollectionIds($value);
                            } elseif ($transforms[$group] === \OSerializer\Mapping\Transform::INVOKE) {
                                return $this->transformCollectionInvoke($value);
                            }
                        } else {
                            if ($transforms[$group] === \OSerializer\Mapping\Transform::TRANSFORM_ID) {
                                return $this->transformId($value);
                            } elseif ($transforms[$group] === \OSerializer\Mapping\Transform::INVOKE) {
                                return $this->transformInvoke($value);
                            } elseif ($transforms[$group] === \OSerializer\Mapping\Transform::TO_ARRAY) {
                                return $this->transformToArray($value);
                            } elseif ($transforms[$group] === \OSerializer\Mapping\Transform::METHOD) {
                                $method = @$transformAnnotation->getMethods()[$group];
                                if ($method !== null) {
                                    return $this->transformMethod($value, $method);
                                }
                            }
                        }
                        return $value;
                    }
                }
            } catch (\Exception $ex) {
                return $value;
            }
        }
        return $value;
    }

    protected function transformCollectionIds(\Doctrine\Common\Collections\Collection $collection)
    {
        return array_values(array_map(function($element) {
                return $element->getId();
            }, $collection->toArray()));
    }

    protected function transformCollectionInvoke(\Doctrine\Common\Collections\Collection $collection)
    {
        $self = $this;
        return array_values(array_map(function($element) use($self) {
                if ($element !== null) {
                    return $self->transformInvoke($element);
                }
                return null;
            }, $collection->toArray()));
    }

    protected function transformMethod($value, $methodName)
    {
        if ($value !== null && is_object($value) && method_exists($value, $methodName)) {
            return $value->{$methodName}();
        }
    }

    protected function transformToArray($value)
    {
        if ($value !== null && is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
    }

    protected function transformInvoke($value)
    {
        if ($value !== null && is_object($value) && method_exists($value, '__invoke')) {
            return $value();
        }
    }

    protected function transformId($value)
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId();
        } else {
            return $value;
        }
    }

}
