<?php

namespace App\Utils;

class CircularReferenceHandler
{
    public function __invoke($object, string $format = null, array $context = [])
    {
        if (method_exists($object, 'getUuid')) {
            return $object->getUuid();
        }
        return $object->getId();
    }
}
