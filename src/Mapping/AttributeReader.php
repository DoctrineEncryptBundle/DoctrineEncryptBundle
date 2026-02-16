<?php

namespace Ambta\DoctrineEncryptBundle\Mapping;

use Ambta\DoctrineEncryptBundle\Configuration\Annotation;

/**
 * @author Flavien Bucheton <leflav45@gmail.com>
 *
 * @internal
 */
final class AttributeReader implements MappingReader
{
    /** @var array */
    private $isRepeatableAttribute = [];

    /**
     * @return Annotation[]
     */
    public function getClassAnnotations(\ReflectionClass $class): array
    {
        return (method_exists($class, 'getAttributes')) ? $this->convertToAttributeInstances($class->getAttributes()) : [];
    }

    /**
     * @param class-string<T> $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? null;
    }

    /**
     * @return Annotation[]
     */
    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        return (method_exists($property, 'getAttributes')) ? $this->convertToAttributeInstances($property->getAttributes()) : [];
    }

    /**
     * @param class-string<T> $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? null;
    }

    /**
     * @param array<\ReflectionAttribute> $attributes
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Gedmo Annotations
            if (!is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);

            if ($this->isRepeatable($attributeName)) {
                if (!isset($instances[$attributeName])) {
                    $instances[$attributeName] = [];
                }

                $instances[$attributeName][] = $instance;
            } else {
                $instances[$attributeName] = $instance;
            }
        }

        return $instances;
    }

    private function isRepeatable(string $attributeClassName): bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new \ReflectionClass($attributeClassName);
        $attribute       = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = ($attribute->flags & \Attribute::IS_REPEATABLE) > 0;
    }
}
