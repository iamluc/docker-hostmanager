<?php

namespace Test\Utils;

class PropertyAccessor
{
    public static function getProperty($object, $property)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(
                sprintf('The first parameter must be an object: "%s" given.', gettype($object))
            );
        }

        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
