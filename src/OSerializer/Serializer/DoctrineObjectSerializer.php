<?php

namespace OSerializer\Serializer;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;
use OSerializer\Mapping\LazyProperty;

/**
 * Hydration groups
 * {"item", "list"}
 */
class DoctrineObjectSerializer extends \DoctrineModule\Stdlib\Hydrator\DoctrineObject implements ServiceLocatorAwareInterface, HydratorInterface
{

    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     *
     * @var Formatter\FormatterInterface[]
     */
    protected $formatters = [];

    /**
     *
     * @var Strategy\NamingStrategyInterface
     */
    protected $namingStrategy = null;

    /**
     *
     * @var Filter\FilterInterface[]
     */
    protected $filters = [];

    /**
     * 
     * @param Strategy\NamingStrategyInterface $namingStrategy
     * @param Formatter\FormatterInterface[] $formatters
     * @param Filter\FilterInterface[] $filters
     * @param Transformer\TransformerInterface[] $transformers
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param boolean $byValue
     * @return DoctrineObject
     */
    public function __construct($namingStrategy, $formatters, $filters, $transformers, \Doctrine\Common\Persistence\ObjectManager $objectManager, $byValue = true)
    {
        $this->namingStrategy = $namingStrategy;
        $this->filters        = $filters;
        $this->formatters     = $formatters;
        $this->transformers   = $transformers;

        return parent::__construct($objectManager, $byValue);
    }

    protected $groups = [];

    /**
     * Extract values from an object using a by-value logic (this means that it uses the entity
     * API, in this case, getters)
     *
     * @param  object $object
     * @throws RuntimeException
     * @return array
     */
    protected function extractByValue($object)
    {

        $fieldNames = array_merge($this->metadata->getFieldNames(), $this->metadata->getAssociationNames());
        if (\Doctrine\ORM\Version::compare('2.5.0') >= 0) {
            $embeddedFieldNames = array_keys($this->metadata->embeddedClasses);
            $fieldNames         = array_merge($fieldNames, $embeddedFieldNames);
        } else {
            $embeddedFieldNames = [];
        }

        $methods = get_class_methods($object);
        $filter  = $object instanceof FilterProviderInterface ? $object->getFilter() : $this->filterComposite;

        $data = array();
        foreach ($fieldNames as $fieldName) {

            if ($filter && !$filter->filter($fieldName)) {
                continue;
            }
            $getter = 'get' . ucfirst($fieldName);
            $isser  = 'is' . ucfirst($fieldName);

            $dataFieldName = $this->computeExtractFieldName($fieldName);
            if (in_array($fieldName, $embeddedFieldNames)) {
                if ($object->$getter() !== null) {
                    $data[$fieldName] = $object->$getter();
                }
            } else {
                if (in_array($getter, $methods)) {
                    $data[$fieldName] = $this->extractValue($fieldName, $object->$getter(), $object);
                } elseif (in_array($isser, $methods)) {
                    $data[$fieldName] = $this->extractValue($fieldName, $object->$isser(), $object);
                } elseif (substr($fieldName, 0, 2) === 'is' && ctype_upper(substr($fieldName, 2, 1)) && in_array($fieldName, $methods)) {
                    $data[$fieldName] = $this->extractValue($fieldName, $object->$fieldName(), $object);
                }
            }

            // Unknown fields are ignored
        }

        if ($object !== null) {
            $reader = $this->getAnnotationReader();
            $class  = new \ReflectionClass(\Doctrine\Common\Util\ClassUtils::getClass($object));
            if ($class) {
                $reflMethods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($reflMethods as $reflMethod) {
                    $lazyPropertyAnnotation = $reader->getMethodAnnotation($reflMethod, LazyProperty::class);
                    if ($lazyPropertyAnnotation !== null && $lazyPropertyAnnotation instanceof LazyProperty) {
                        $fieldName        = $lazyPropertyAnnotation->getPropertyName();
                        $fieldData        = $reflMethod->invokeArgs($object, $lazyPropertyAnnotation->getArguments());
                        $data[$fieldName] = $fieldData;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Extract values from an object using a by-reference logic (this means that values are
     * directly fetched without using the public API of the entity, in this case, getters)
     *
     * @param  object $object
     * @return array
     */
    protected function extractByReference($object)
    {
        $fieldNames = array_merge($this->metadata->getFieldNames(), $this->metadata->getAssociationNames());
        $refl       = $this->metadata->getReflectionClass();
        $filter     = $object instanceof FilterProviderInterface ? $object->getFilter() : $this->filterComposite;

        $data = array();
        foreach ($fieldNames as $fieldName) {
            if ($filter && !$filter->filter($fieldName)) {
                continue;
            }
            $reflProperty     = $refl->getProperty($fieldName);
            $reflProperty->setAccessible(true);
            $data[$fieldName] = $this->extractValue($fieldName, $reflProperty->getValue($object), $object);
        }

        if ($object !== null) {
            $reader = $this->getAnnotationReader();
            $class  = new \ReflectionClass(\Doctrine\Common\Util\ClassUtils::getClass($object));
            if ($class) {
                $reflMethods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($reflMethods as $reflMethod) {
                    $lazyPropertyAnnotation = $reader->getMethodAnnotation($reflMethod, LazyProperty::class);
                    if ($lazyPropertyAnnotation !== null && $lazyPropertyAnnotation instanceof LazyProperty) {
                        $fieldName = $lazyPropertyAnnotation->getPropertyName();

                        $fieldData        = $reflMethod->invokeArgs($object, $lazyPropertyAnnotation->getArguments());
                        $data[$fieldName] = $fieldData;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public static function objectHash($object, $objectManager)
    {
        if (is_object($object)) {
            $objectClass = \Doctrine\Common\Util\ClassUtils::getClass($object);
            if (!method_exists($object, 'getId')) {
                $ids              = [];
                $objectMetadata   = $objectManager->getClassMetadata($objectClass);
                $identifierFields = $objectMetadata->getIdentifierFieldNames();
                foreach ($identifierFields as $identifierField) {
                    $getter = 'get' . ucfirst($identifierField);
                    $id     = $object->{$getter}();
                    if (gettype($id) === 'object') {
                        $id = static::objectHash($id, $objectManager);
                    }
                    $ids[] = $id;
                }
                $id = implode('_', $ids);
            } else {
                $id = $object->getId();
            }
            return $objectClass . ':' . $id;
        }

        return null;
    }

    protected $annotationReader = null;

    /**
     * 
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    protected function getAnnotationReader()
    {
        if ($this->annotationReader === null) {
            $serviceLocator = $this->getServiceLocator();
            if ($serviceLocator instanceof \Zend\Stdlib\Hydrator\HydratorPluginManager) {
                $serviceLocator = $serviceLocator->getServiceLocator();
            }
            $this->annotationReader = $serviceLocator->get('AnnotationReader');
        }
        return $this->annotationReader;
    }

    /**
     * 
     * @param object $entity
     */
    protected function preventCircular($value, $targetObject, $property, $serializer)
    {
        $contexts = $serializer->getContexts();
        if (in_array($serializer::objectHash($value,$serializer->getObjectManager()), $contexts)) {
            throw new \Exception(sprintf('Circular serialization found for entity "%s::%s": trying to render entity "%s" in context [%s]', $serializer::objectHash($targetObject, $this->objectManager), $property, $serializer::objectHash($value, $this->objectManager), implode(',', $contexts)));
        }
    }
    
    public function getObjectManager(){
        return $this->objectManager;
    }

    /**
     * 
     * @param \Traversable $items
     * @return array
     * @throws Exception
     */
    public function extractAll($items)
    {
        if (!is_array($items) || (is_object($items) && $items instanceof \Traversable)) {
            throw new Exception('Items should be array or traversable');
        }
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->extract($item);
        }
        return $result;
    }

    /**
     * 
     * @param mixed $object
     * @param array $groups
     * @return array
     */
    public function serialize($object, $groups = ['item'])
    {
        $this->setGroups($groups);
        return $this->extract($object);
    }

    /**
     * {@inheritDoc}
     */
    public function extract($object)
    {
        $this->prepare($object);
        $objectHash = self::objectHash($object, $this->objectManager);
        $this->addContext($objectHash);

        if ($this->byValue) {
            $doctrineData = $this->extractByValue($object);
        } else {
            $doctrineData = $this->extractByReference($object);
        }

        /**
         * Filtering data
         */
        $filteredProperties = [];

        foreach ($this->getFilters() as $filter) {
            foreach ($doctrineData as $property => $value) {
                if (!isset($filteredProperties[$property])) {
                    $filteredProperties[$property] = true;
                }
                $filteredProperties[$property] = $filteredProperties[$property] && $filter->filter($property, $value, $object, $this);
            }
        }
        foreach ($doctrineData as $property => $value) {
            if (!@$filteredProperties[$property]) {
                unset($doctrineData[$property]);
            }
        }

        /**
         * Transforming data
         */
        foreach ($this->getTransformers() as $transformer) {
            foreach ($doctrineData as $property => $value) {
                $doctrineData[$property] = $transformer->transform($property, $value, $object, $this);
            }
        }
        /**
         * Inner collections
         */
        foreach ($doctrineData as $property => $collection) {
            if ($collection instanceof \Doctrine\Common\Collections\Collection) {
                $collection = array_values($collection->toArray());
                foreach ($collection as $key => $value) {
                    $oldGroups = $this->getGroups();
                    $groups    = ['inner', 'list', 'level-' . count($this->getContexts()),
                        'parent-entity-' . get_class($object),
                        'parent-entity-id-' . $object->getId()];
                    $this->addGroups($groups);
                    try {
                        $this->preventCircular($value, $object, $property, $this);
                        $collection[$key] = $this->extract($value);
                    } catch (\Exception $ex) {
                        array_splice($collection, $key, 1);
                    }
                    $this->setGroups($oldGroups);
                }
                $doctrineData[$property] = $collection;
            }
        }

        $formattedData = [];

        /**
         * FormattingData
         */
        foreach ($this->getFormatters() as $formatter) {
            if ($formatter instanceof \DoctrineModule\Persistence\ObjectManagerAwareInterface) {
                $formatter->setObjectManager($this->objectManager);
            }
            foreach ($doctrineData as $property => &$collection) {
                $transformable = $property;
                if ($formatter->isFormattable($object, $property, $collection)) {
                    try {
                        $collection = $formatter->format($collection, $transformable, $object, $formattedData);
                        if (is_array($collection) && array_key_exists($property, $collection)) {
                            if (!isset($formattedData[$transformable])) {
                                $formattedData[$transformable] = [];
                            }
                            $formattedData[$transformable] = array_merge_recursive($formattedData[$transformable], $collection);
                        } else {
                            $formattedData[$transformable] = $collection;
                        }
                    } catch (\Exception $ex) {
                        unset($doctrineData[$property]);
                    }
                } else {
                    $formattedData[$transformable] = $collection;
                }
            }
        }

        /**
         * NamingData
         */
        $data = [];
        foreach ($formattedData as $fieldName => $collection) {
            $dataFieldName        = $this->computeExtractFieldName($fieldName);
            $data[$dataFieldName] = $collection;
        }

        $this->removeContext($objectHash);
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, $object)
    {
        $object           = parent::hydrate($data, $object);
        $associationNames = $this->metadata->getAssociationNames();
        /**
         * @todo inject embedded
         */
        foreach ($associationNames as $assocName) {
            if (isset($data['_embedded'][$assocName])) {
                if (!$this->metadata->isCollectionValuedAssociation($assocName)) {
                    if (is_scalar($entity = $data['_embedded'][$assocName])) {
                        $targetEntityClass = $this->metadata->getAssociationTargetClass($assocName);
                        $targetEntity      = $this->toOne($targetEntityClass, $entity);

                        if ($targetEntity !== null) {
                            $setter = 'set' . ucfirst($assocName);
                            if (method_exists($object, $setter)) {
                                $object->$setter($targetEntity);
                            }
                        }
                    }
                } else {
                    $targetEntityClass = $this->metadata->getAssociationTargetClass($assocName);
                    if (is_array($entities          = $data['_embedded'][$assocName])) {
                        $this->toMany($object, $assocName, $targetEntityClass, $entities);
                    }
                }
            }
        }
        return $object;
    }

    /**
     * @para, bool $filter Фильтровать абстрактные классы?
     * @return array
     */
    public function getDisciminatorMap($targetClass, $filter = true)
    {
        /**
         * Search for appropriate metadata
         */
        $om = $this->objectManager;
        do {
            if ($targetClass) {
                $metadata = $om->getClassMetadata($targetClass);
                if (count($metadata->discriminatorMap)) {
                    return $filter ? $this->filterMap($metadata->discriminatorMap) : $metadata->discriminatorMap;
                }
                $targetClass = get_parent_class($targetClass);
            }
        } while ($targetClass && $this->isEntity($targetClass));
    }

    /**
     * 
     * @param array $discriminatorMap
     * @return array
     */
    protected function filterMap($discriminatorMap)
    {
        foreach ($discriminatorMap as $key => $value) {
            if (!$this->isEntity($value)) {
                unset($discriminatorMap[$key]);
            }
        }
        return $discriminatorMap;
    }

    /**
     * @param EntityManager $entityManager
     * @param string|object $class
     *
     * @return boolean
     */
    protected function isEntity($class)
    {
        $entityManager = $this->objectManager;
        if (is_object($class)) {
            $class = ($class instanceof Proxy) ? get_parent_class($class) : get_class($class);
        }
        $reflClass = new \ReflectionClass($class);

        return !$entityManager->getMetadataFactory()->isTransient($class) && !$reflClass->isAbstract();
    }

    /**
     * Handle ToMany associations. In proper Doctrine design, Collections should not be swapped, so
     * collections are always handled by reference. Internally, every collection is handled using specials
     * strategies that inherit from AbstractCollectionStrategy class, and that add or remove elements but without
     * changing the collection of the object
     *
     * @param  object $object
     * @param  mixed  $collectionName
     * @param  string $target
     * @param  mixed  $values
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function toMany($object, $collectionName, $target, $values)
    {
        if (!is_array($values) && !$values instanceof Traversable) {
            $values = (array) $values;
        }

        $collection = array();

        // If the collection contains identifiers, fetch the objects from database
        foreach ($values as $value) {
            $innderTarget = $target;
            if (is_array($value)) {
                $classMetadata = $this->objectManager->getClassMetadata($target);
                if ($classMetadata->getReflectionClass()->isAbstract()) {
                    $discriminatorMap = $this->getDisciminatorMap($target);
                    $innderTarget     = $this->resolveClassByAbstract($value, $discriminatorMap);
                }
                if (!isset($value['id']) || $value['id'] === null) {
                    $value = $this->hydrate($value, new $innderTarget());
                } else {
                    $value = $this->hydrate($value, $this->find($value['id'], $innderTarget));
                }
                $collection[] = $value;
            } else {
                $collection[] = $this->find($value, $innderTarget);
            }
        }

        $collection = array_filter(
                $collection, function ($item) {
            return null !== $item;
        }
        );


        // Set the object so that the strategy can extract the Collection from it

        /** @var \DoctrineModule\Stdlib\Serializer\Strategy\AbstractCollectionStrategy $collectionStrategy */
        $collectionStrategy = $this->getStrategy($collectionName);
        $collectionStrategy->setObject($object);

        // We could directly call hydrate method from the strategy, but if people want to override
        // hydrateValue function, they can do it and do their own stuff
        $this->hydrateValue($collectionName, $collection, $values);
    }

    protected function resolveClassByAbstract($data, $map)
    {
        if (isset($data['type'])) {
            return $map[$data['type']];
        } else {
            return array_pop($map);
        }
    }

    /**
     * 
     * @return Formatter\FormatterInterface[]
     */
    public function getFormatters()
    {
        return $this->formatters;
    }

    /**
     * 
     * @return Strategy\NamingStrategyInterface|null
     */
    public function getNamingStrategy()
    {
        return $this->namingStrategy;
    }

    /**
     * 
     * @return Filter\FilterInterface[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * 
     * @param \OSerializer\Serializer\Formatter\FormatterInterface[] $formatters
     * @return \OSerializer\Serializer\DoctrineObject
     */
    public function setFormatters(Formatter\FormatterInterface $formatters)
    {
        $this->formatters = $formatters;
        return $this;
    }

    /**
     * 
     * @param \OSerializer\Serializer\Strategy\NamingStrategyInterface $namingStrategy
     * @return \OSerializer\Serializer\DoctrineObject
     */
    public function setNamingStrategy(\Zend\Stdlib\Hydrator\NamingStrategy\NamingStrategyInterface $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
        return $this;
    }

    /**
     * 
     * @param array $filters
     * @return \OSerializer\Serializer\DoctrineObject
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * 
     * @param array $groups
     * @return \OSerializer\Serializer\DoctrineObject
     */
    public function setGroups($groups)
    {
        $this->groups = array_values($groups);
        return $this;
    }

    /**
     * 
     * @param array $groups
     */
    public function addGroups($groups)
    {
        $this->groups = array_unique(array_merge($this->groups, $groups));
    }

    /**
     * 
     * @param type $groups
     */
    public function removeGroups($groups)
    {

        $this->groups = array_values(array_diff($this->groups, $groups));
    }

    protected $contexts = [];

    public function addContext($context)
    {
        $this->contexts[] = $context;
    }

    public function removeContext($context)
    {
        foreach ($this->contexts as $key => $contextItem) {
            if ($contextItem == $context) {
                unset($this->contexts[$key]);
            }
        }
    }

    public function getContexts()
    {
        return $this->contexts;
    }

    var $transformers = [];

    /**
     * 
     * @return Transformer\TransformerInterface
     */
    public function getTransformers()
    {
        return $this->transformers;
    }

    public function setTransformers($transformers)
    {
        $this->transformers = $transformers;
        return $this;
    }

}
