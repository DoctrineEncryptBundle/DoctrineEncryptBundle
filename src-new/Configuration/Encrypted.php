<?php

namespace DoctrineEncryptBundle\DoctrineEncryptBundle\Configuration;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * The Encrypted class handles the @Encrypted annotation.
 *
 * @author Victor Melnik <melnikvictorl@gmail.com>
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Encrypted implements Annotation
{
    private const ALLOWED_TYPES = [
        'string',
        'datetime',
        'json',
        'array'
    ];

    /**
     * @var string
     */
    public $type;

    /**
     * @param 'string'|'datetime'|'json'|'array' $type
     */
    public function __construct(string $type = 'string')
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('%s is not a supported type for %s', $type, self::class));
        }
        $this->type = $type;
    }
}
