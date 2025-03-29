<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Ambta\DoctrineEncryptBundle\Mapping\AttributeReader;
use Ambta\DoctrineEncryptBundle\Subscribers\DoctrineEncryptSubscriber;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Command\Command;

/**
 * Base command containing usefull base methods.
 *
 * @author Michael Feinbier <michael@feinbier.net>
 **/
abstract class AbstractCommand extends Command
{
    /**
     * @var EntityManagerInterface|EntityManager
     */
    protected $entityManager;

    /**
     * @var DoctrineEncryptSubscriber
     */
    protected $subscriber;

    /**
     * @var Reader|AttributeReader
     */
    protected $annotationReader;

    /** @var array<string,string> */
    protected $supportedEncryptors;

    /**
     * AbstractCommand constructor.
     *
     * @param EntityManager          $entityManager
     * @param Reader|AttributeReader $annotationReader
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        $annotationReader,
        DoctrineEncryptSubscriber $subscriber,
        array $supportedEncryptors
    ) {
        parent::__construct();
        $this->entityManager       = $entityManager;
        $this->annotationReader    = $annotationReader;
        $this->subscriber          = $subscriber;
        $this->supportedEncryptors = $supportedEncryptors;
    }

    /**
     * Get an result iterator over the whole table of an entity.
     *
     * @return iterable|array
     */
    protected function getEntityIterator(string $entityName): iterable
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT o FROM %s o', $entityName));

        return $query->toIterable();
    }

    /**
     * Get the number of rows in an entity-table.
     */
    protected function getTableCount(string $entityName): int
    {
        $query = $this->entityManager->createQuery(sprintf('SELECT COUNT(o) FROM %s o', $entityName));

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Return an array of entity-metadata for all entities
     * that have at least one encrypted property.
     */
    protected function getEncryptionableEntityMetaData(): array
    {
        $validMetaData = [];
        $metaDataArray = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metaDataArray as $entityMetaData) {
            if (($entityMetaData instanceof ClassMetadataInfo || $entityMetaData instanceof ClassMetadata) && $entityMetaData->isMappedSuperclass) {
                continue;
            }

            $properties = $this->getEncryptionableProperties($entityMetaData);
            if (count($properties) == 0) {
                continue;
            }

            $validMetaData[] = $entityMetaData;
        }

        return $validMetaData;
    }

    protected function getEncryptionableProperties($entityMetaData): array
    {
        // Create reflectionClass for each meta data object
        $reflectionClass = new \ReflectionClass($entityMetaData->name);
        $propertyArray   = $reflectionClass->getProperties();
        $properties      = [];

        foreach ($propertyArray as $property) {
            if ($this->annotationReader->getPropertyAnnotation($property, 'Ambta\DoctrineEncryptBundle\Configuration\Encrypted')) {
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
