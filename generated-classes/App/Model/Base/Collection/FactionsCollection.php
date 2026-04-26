<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Factions.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Factions>
 */
class FactionsCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Factions');
        $this->setFormatter(new ObjectFormatter());
    }

}
