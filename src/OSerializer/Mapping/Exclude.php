<?php

namespace OSerializer\Mapping;

/**
 * Exclude this attribute
 * Filters item
 * @Annotation
 * @Target({"PROPERTY","METHOD"})
 */
class Exclude
{

    /**
     *
     * @var array
     */
    protected $groups = ['default'];

    public function __construct($values)
    {
        if (isset($values['value'])) {
            $this->setGroups((array)$values['value']);
        }
    }

    /**
     * 
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * 
     * @param array $groups
     * @return \OSerializer\Hydrator\Annotation\Group
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
        return $this;
    }

}
