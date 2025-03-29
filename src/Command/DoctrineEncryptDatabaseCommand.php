<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Batch encryption for the database.
 *
 * @author Marcel van Nuil <marcel@ambta.com>
 * @author Michael Feinbier <michael@feinbier.net>
 */
class DoctrineEncryptDatabaseCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:encrypt:database')
            ->setDescription('Encrypt whole database on tables which are not encrypted yet')
            ->addArgument('encryptor', InputArgument::OPTIONAL, 'The encryptor you want to decrypt the database with')
            ->addArgument('batchSize', InputArgument::OPTIONAL, 'The update/flush batch size', 20)
            ->addOption('answer', null, InputOption::VALUE_OPTIONAL, 'The answer for the interactive question. When specified the question is skipped and the supplied answer given. Anything except y/yes will be seen as no');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get entity manager, question helper, subscriber service and annotation reader
        $question  = $this->getHelper('question');
        $batchSize = $input->getArgument('batchSize');

        // If encryptor has been set use that encryptor else use default
        if ($input->getArgument('encryptor')) {
            if (isset($this->supportedEncryptors[$input->getArgument('encryptor')])) {
                $reflection = new \ReflectionClass($this->supportedEncryptors[$input->getArgument('encryptor')]);
                $encryptor  = $reflection->newInstance();
                $this->subscriber->setEncryptor($encryptor);
            } else {
                if (class_exists($input->getArgument('encryptor'))) {
                    $this->subscriber->setEncryptor($input->getArgument('encryptor'));
                } else {
                    $output->writeln('Given encryptor does not exists');

                    $output->writeln('Supported encryptors: '.implode(', ', array_keys($this->supportedEncryptors)));

                    return defined('AbstractCommand::INVALID') ? AbstractCommand::INVALID : 2;
                }
            }
        }

        $defaultAnswer = false;
        $answer        = $input->getOption('answer');
        if ($answer) {
            $input->setInteractive(false);
            if ($answer === 'y' || $answer === 'yes') {
                $defaultAnswer = true;
            }
        }

        // Get entity manager metadata
        $metaDataArray        = $this->getEncryptionableEntityMetaData();
        $confirmationQuestion = new ConfirmationQuestion(
            '<question>'.count($metaDataArray).' entities found which are containing properties with the encryption tag.'.PHP_EOL.''.
            'Which are going to be encrypted with ['.get_class($this->subscriber->getEncryptor()).']. '.PHP_EOL.''.
            'Wrong settings can mess up your data and it will be unrecoverable. '.PHP_EOL.''.
            'I advise you to make <bg=yellow;options=bold>a backup</bg=yellow;options=bold>. '.PHP_EOL.''.
            'Continue with this action? (y/yes)</question>', $defaultAnswer
        );

        if (!$question->ask($input, $output, $confirmationQuestion)) {
            return defined('AbstractCommand::FAILURE') ? AbstractCommand::FAILURE : 1;
        }

        // Start decrypting database
        $output->writeln(''.PHP_EOL.'Encrypting all fields can take up to several minutes depending on the database size.');

        // Loop through entity manager meta data
        foreach ($metaDataArray as $metaData) {
            $i          = 0;
            $iterator   = $this->getEntityIterator($metaData->name);
            $totalCount = $this->getTableCount($metaData->name);

            $output->writeln(sprintf('Processing <comment>%s</comment>', $metaData->name));
            $progressBar = new ProgressBar($output, $totalCount);
            foreach ($iterator as $row) {
                $entity = (is_array($row) ? $row[0] : $row);
                $this->subscriber->processFields($entity, $this->entityManager);
                $this->entityManager->persist($entity);

                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $progressBar->advance($batchSize);
                }
                ++$i;
            }

            $progressBar->finish();
            $output->writeln('');
            $this->entityManager->flush();
        }

        // Say it is finished
        $output->writeln('Encryption finished. Values encrypted: <info>'.$this->subscriber->encryptCounter.' values</info>.'.PHP_EOL.'All values are now encrypted.');

        return defined('AbstractCommand::SUCCESS') ? AbstractCommand::SUCCESS : 0;
    }
}
