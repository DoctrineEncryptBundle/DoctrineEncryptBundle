<?php

namespace DoctrineEncryptBundle\Command;

use DoctrineEncryptBundle\DoctrineEncryptBundle;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get status of doctrine encrypt bundle and the database.
 */
class DoctrineEncryptStatusCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('doctrine:encrypt:status')
            ->setDescription('Get status of doctrine encrypt bundle and the database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $encryptTypes = array_keys(DoctrineEncryptBundle::ENCRYPT_TYPES);

        $totalCount = 0;
        $encryptDetails = [];
        // Get entity manager metadata
        $metaDataArray = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metaDataArray as $metaData) {
            if ($metaData instanceof ClassMetadata && $metaData->isMappedSuperclass) {
                continue;
            }

            $count = 0;
            foreach ($metaData->fieldMappings as $fieldMapping) {
                if (in_array ($fieldMapping['type'], $encryptTypes)) {
                    if (! array_key_exists($metaData->name, $encryptDetails)) {
                        $encryptDetails[$metaData->name] = true;
                    }
                    $count++;
                    $totalCount++;
                }
            }

            if ($count > 0) {
                $output->writeln(sprintf('<info>%s</info> has <info>%d</info> properties which are encrypted.', $metaData->name, $count));
            } else {
                $output->writeln(sprintf('<info>%s</info> has no properties which are encrypted.', $metaData->name));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>%d</info> entities found which contain <info>%d</info> encrypted properties.', count($encryptDetails), $totalCount));

        return defined('AbstractCommand::SUCCESS') ? AbstractCommand::SUCCESS : 0;
    }
}
