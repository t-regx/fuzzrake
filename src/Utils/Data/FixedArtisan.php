<?php

declare(strict_types=1);

namespace App\Utils\Data;

use App\Entity\Artisan;
use App\Utils\Artisan\Field;
use Doctrine\Common\Persistence\ObjectManager;

class FixedArtisan
{
    /**
     * @var Artisan
     */
    private $original;

    /**
     * @var Artisan
     */
    private $fixed;

    /**
     * @var ObjectManager
     */
    private $objectMgr;

    public function __construct(Artisan $original, Artisan $fixed, ObjectManager $objectMgr)
    {
        $this->original = $original;
        $this->fixed = $fixed;
        $this->objectMgr = $objectMgr;
    }

    public function getOriginal(): Artisan
    {
        return $this->original;
    }

    public function getFixed(): Artisan
    {
        return $this->fixed;
    }

    public function reset(Field $field = null): void
    {
        if (null === $field) {
            $this->objectMgr->refresh($this->fixed);
        } else {
            $this->fixed->set($field, $this->original->get($field));
        }
    }
}
