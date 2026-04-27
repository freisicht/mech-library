<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Armies.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Armies>
 */
class ArmiesCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Armies');
        $this->setFormatter(new ObjectFormatter());
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\FormationsCollection
     */
    public function populateFormationss(?Criteria $criteria = null, ?ConnectionInterface $con = null): FormationsCollection
    {
        /** @var \App\Model\Base\Collection\FormationsCollection $collection */
        $collection = $this->populateRelation('Formations', $criteria, $con);

        return $collection;
    }

}
