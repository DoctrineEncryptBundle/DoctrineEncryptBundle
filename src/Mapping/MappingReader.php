<?php

declare(strict_types=1);

namespace Ambta\DoctrineEncryptBundle\Mapping;

/**
 * This is a duplicate of \Doctrine\Common\Annotations\Reader to be have a mutual contract between both readers.
 */
interface MappingReader
{
    /**
     * Gets the annotations applied to a class.
     *
     * @param \ReflectionClass $class the ReflectionClass of the class from which
     *                                the class annotations should be read
     *
     * @return array<object> an array of Annotations
     */
    public function getClassAnnotations(\ReflectionClass $class);

    /**
     * Gets a class annotation.
     *
     * @param \ReflectionClass $class          the ReflectionClass of the class from which
     *                                         the class annotations should be read
     * @param class-string<T>  $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName);

    /**
     * Gets the annotations applied to a property.
     *
     * @param \ReflectionProperty $property the ReflectionProperty of the property
     *                                      from which the annotations should be read
     *
     * @return array<object> an array of Annotations
     */
    public function getPropertyAnnotations(\ReflectionProperty $property);

    /**
     * Gets a property annotation.
     *
     * @param \ReflectionProperty $property       the ReflectionProperty to read the annotations from
     * @param class-string<T>     $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName);
}
