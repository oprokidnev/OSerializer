<?php

namespace OSerializer\Mapping;

/**
 * Transforms 
 * e.g. Transform({
 *  "GroupName":Transform::TRANSFORM_COLLECTION_IDS
 * })
 * @Annotation
 * @Target({"PROPERTY","METHOD"})
 */
class Transform
{

    /**
     * return full collection
     */
    const TRANSFORM_COLLECTION_FULL = 'col_full';

    /**
     * return full collection
     */
    const COLLECTION_FULL = 'col_full';

    /**
     * return collection as a set of identifiers
     */
    const TRANSFORM_COLLECTION_IDS = 'col_ids';

    /**
     * return collection as a set of identifiers
     */
    const COLLECTION_IDS = 'col_ids';

    /**
     * return entity as an identifier
     */
    const TRANSFORM_ID = 'id';

    /**
     * return entity as an identifier
     */
    const ID = 'id';

    /**
     * return entity as an identifier
     */
    const TRANSFORM_INVOKE   = 'invoke';

    /**
     * return entity as an identifier
     */
    const INVOKE             = 'invoke';

    /**
     * return entity as an identifier
     */
    const TRANSFORM_TO_ARRAY = 'toArray';

    /**
     * return entity as an identifier
     */
    const METHOD             = 'method';

    /**
     * return entity as an identifier
     */
    const TO_ARRAY           = 'toArray';

    /**
     *
     * @var array
     */
    protected $transforms = [];

    /**
     *
     * @var array
     */
    protected $methods = [];

    public function __construct($values)
    {
        if (isset($values['value'])) {
            $this->setTransforms((array) $values['value']);
        }
        if (isset($values['methods'])) {
            $this->setMethods((array) $values['methods']);
        }
    }

    /**
     * 
     * @return array
     */
    public function getTransforms()
    {
        return $this->transforms;
    }

    /**
     * 
     * @param array $transforms
     * @return \OSerializer\Hydrator\Annotation\Format
     */
    public function setTransforms($transforms)
    {
        $this->transforms = $transforms;
        return $this;
    }

    /**
     * 
     * @return array
     */
    function getMethods()
    {
        return $this->methods;
    }

    /**
     * 
     * @param array $methods
     * @return \OSerializer\Hydrator\Annotation\Transform
     */
    function setMethods($methods)
    {
        $this->methods = $methods;
        return $this;
    }

}
