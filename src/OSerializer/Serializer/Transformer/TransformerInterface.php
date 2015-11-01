<?php

namespace OSerializer\Serializer\Transformer;
/**
 *
 * @author oprokidnev
 */
interface TransformerInterface
{
    /**
     * 
     * @param type $property
     * @param type $value
     * @param type $targetObject
     * @param type $Serializer
     * @return mixed Transformed paramerter
     */
    public function transform($property, $value, $targetObject, $Serializer);
}
