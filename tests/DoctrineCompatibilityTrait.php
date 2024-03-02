<?php

namespace DoctrineEncryptBundle\Tests;

trait DoctrineCompatibilityTrait
{
    /**
     * Execute statement and fetch all results
     *
     * Helper-method since methods changed in different supported versions of Doctrine
     */
    public function executeStatementFetchAll(\Doctrine\DBAL\Statement $statement)
    {
        if (method_exists($statement,'executeQuery')) {
            return $statement->executeQuery()->fetchAllAssociative();
        } else {
            $statement->execute();
            return $statement->fetchAll();
        }
    }

    /**
     * Execute statement and fetch singe row
     *
     * Helper-method since methods changed in different supported versions of Doctrine
     */
    public function executeStatementFetch(\Doctrine\DBAL\Statement $statement)
    {
        if (method_exists($statement,'executeQuery')) {
            return $statement->executeQuery()->fetchAssociative();
        } else {
            $statement->execute();
            return $statement->fetch();
        }
    }
}