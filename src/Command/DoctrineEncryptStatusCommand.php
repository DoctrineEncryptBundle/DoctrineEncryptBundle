<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get status of doctrine encrypt bundle and the database.
 *
 * @author Marcel van Nuil <marcel@ambta.com>
 * @author Michael Feinbier <michael@feinbier.net>
 */
final class DoctrineEncryptStatusCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:encrypt:status')
            ->setDescription('Get status of doctrine encrypt bundle and the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $encryptionableEntityDetails = $this->getEncryptionableEntityDetails();

        foreach ($encryptionableEntityDetails['propertyCountPerEntity'] as $entityName => $count) {
            if ($count > 0) {
                $output->writeln(sprintf('<info>%s</info> has <info>%d</info> properties which are encrypted.', $entityName, $count));
            } else {
                $output->writeln(sprintf('<info>%s</info> has no properties which are encrypted.', $entityName));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>%d</info> entities found which contain <info>%d</info> encrypted properties.', count($encryptionableEntityDetails['metaData']), $encryptionableEntityDetails['totalPropertyCount']));

        return defined('AbstractCommand::SUCCESS') ? AbstractCommand::SUCCESS : 0;
    }
}
