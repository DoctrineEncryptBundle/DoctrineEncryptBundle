<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Mapping;

use DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration\Annotation;

/**
 * @author Flavien Bucheton <leflav45@gmail.com>
 *
 * @internal
 */
final class AttributeReader
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
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getClassAnnotation(\ReflectionClass $class, string $annotationName)
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
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, string $annotationName)
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
