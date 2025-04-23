<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use Ambta\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Ambta\DoctrineEncryptBundle\Service\EncryptService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Decrypt whole database on tables which are encrypted.
 *
 * @author Marcel van Nuil <marcel@ambta.com>
 * @author Michael Feinbier <michael@feinbier.net>
 */
final class DoctrineDecryptDatabaseCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:decrypt:database')
            ->setDescription('Decrypt whole database on tables which are encrypted')
            ->addArgument('encryptor', InputArgument::OPTIONAL, 'The encryptor you want to decrypt the database with')
            ->addArgument('batchSize', InputArgument::OPTIONAL, 'The update/flush batch size', 20)
            ->addOption('answer', null, InputOption::VALUE_OPTIONAL, 'The answer for the interactive question. When specified the question is skipped and the supplied answer given. Anything except y/yes will be seen as no');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get entity manager, question helper and service
        $question  = $this->getHelper('question');
        $batchSize = $input->getArgument('batchSize');

        \assert($question instanceof QuestionHelper);

        // Get list of supported encryptors
        $supportedExtensions = DoctrineEncryptExtension::SupportedEncryptorClasses;

        // If encryptor has been set use that encryptor else use default
        if ($input->getArgument('encryptor')) {
            if (isset($supportedExtensions[$input->getArgument('encryptor')])) {
                $reflection = new \ReflectionClass($supportedExtensions[$input->getArgument('encryptor')]);
                $encryptor  = $reflection->newInstance();
                $this->encryptService->setEncryptor($encryptor);
            } else {
                if (class_exists($input->getArgument('encryptor'))) {
                    $this->encryptService->setEncryptor($input->getArgument('encryptor'));
                } else {
                    $output->writeln('Given encryptor does not exists');

                    $output->writeln('Supported encryptors: '.implode(', ', array_keys($supportedExtensions)));

                    return defined('AbstractCommand::INVALID') ? AbstractCommand::INVALID : 2;
                }
            }
        }

        $encryptTypes = array_keys(EncryptService::ENCRYPT_TYPES);

        $encryptionableEntityDetails = $this->getEncryptionableEntityDetails();

        $defaultAnswer = false;
        $answer        = $input->getOption('answer');
        if ($answer) {
            $input->setInteractive(false);
            if ($answer === 'y' || $answer === 'yes') {
                $defaultAnswer = true;
            }
        }

        $confirmationQuestion = new ConfirmationQuestion(
            '<question>'.count($encryptionableEntityDetails['metaData']).' entities found which are containing properties with the encryption types.'.PHP_EOL.''.
            'Which are going to be decrypted with ['.get_class($this->encryptService->getEncryptor()).']. '.PHP_EOL.''.
            'Wrong settings can mess up your data and it will be unrecoverable. '.PHP_EOL.''.
            'I advise you to make <bg=yellow;options=bold>a backup</bg=yellow;options=bold>. '.PHP_EOL.''.
            'Continue with this action? (y/yes)</question>', $defaultAnswer
        );

        if (!$question->ask($input, $output, $confirmationQuestion)) {
            return defined('AbstractCommand::FAILURE') ? AbstractCommand::FAILURE : 1;
        }

        // Start decrypting database
        $this->encryptService->skipEncryptionOnTypes();
        $output->writeln(''.PHP_EOL.'Decrypting all fields. This can take up to several minutes depending on the database size.');

        $platform   = $this->entityManager->getConnection()->getDatabasePlatform();
        $pac        = PropertyAccess::createPropertyAccessor();
        $unitOfWork = $this->entityManager->getUnitOfWork();
        foreach ($encryptionableEntityDetails['metaData'] as $entityName => $classMeta) {
            $ctp = $classMeta->changeTrackingPolicy;
            // Get the current encryptor used
            $encryptorUsed = $this->subscriber->getEncryptor();

            // Tell the table class to not automatically calculate changed values but just
            // mark those fields as dirty that get passed to propertyChanged function
            $classMeta->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_NOTIFY);
            $this->subscriber->setEncryptor(null);

            $i            = 0;
            $valueCounter = 0;
            $iterator     = $this->getEntityIterator($entityName);
            $totalCount   = $this->getTableCount($entityName);

            $output->writeln(sprintf('Processing <comment>%s</comment>\'s records', $entityName));
            $progressBar = new ProgressBar($output, $totalCount);
            foreach ($iterator as $row) {
                $entity = (is_array($row) ? $row[0] : $row);

                // tell the unit of work that an value has changed no matter if the value
                // is actually different from the value already persistent
                // need all the values checked for the count
                foreach ($classMeta->fieldMappings as $fieldMapping) {
                    if (in_array($fieldMapping['type'], $encryptTypes)) {
                        $value = $pac->getValue($entity, $fieldMapping['fieldName']);
                        if (!is_null($value)) {
                            ++$valueCounter;
                            $unitOfWork->propertyChanged($entity, $fieldMapping['fieldName'], $value, $value);
                        }
                    }
                }

                // Loop through the property's in the entity
                foreach ($this->getEncryptionableProperties($classMeta) as $property) {
                    $value = $pac->getValue($entity, $property->getName());
                    if (!is_null($value)) {
                        ++$valueCounter;

                        $annotation      = $this->annotationReader->getPropertyAnnotation($property, Encrypted::class);
                        $newValue        = $this->encryptService->decrypt($annotation->type, $value);
                        $encryptDbalType = Type::getType($annotation->type);
                        $usedValue       = $encryptDbalType->convertToDatabaseValue($newValue, $platform);
                        $unitOfWork->propertyChanged($entity, $property->getName(), $value, $usedValue);
                    }
                }

                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $progressBar->advance(1);
                ++$i;
            }

            $progressBar->finish();
            $output->writeln('');
            $this->entityManager->flush();
            $this->entityManager->clear();

            $classMeta->setChangeTrackingPolicy($ctp);
            $this->subscriber->setEncryptor($encryptorUsed);
        }

        $output->writeln(''.PHP_EOL.'Decryption finished. Estimated values decrypted: <info>'.$valueCounter.'</info>.'.PHP_EOL.'All values are now decrypted.');

        return defined('AbstractCommand::SUCCESS') ? AbstractCommand::SUCCESS : 0;
    }
}
