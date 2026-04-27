<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Formations.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Formations>
 */
class FormationsCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Formations');
        $this->setFormatter(new ObjectFormatter());
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\ArmiesCollection
     */
    public function populateArmies(?Criteria $criteria = null, ?ConnectionInterface $con = null): ArmiesCollection
    {
        /** @var \App\Model\Base\Collection\ArmiesCollection $collection */
        $collection = $this->populateRelation('Armies', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\FormationUnitsCollection
     */
    public function populateFormationUnitss(?Criteria $criteria = null, ?ConnectionInterface $con = null): FormationUnitsCollection
    {
        /** @var \App\Model\Base\Collection\FormationUnitsCollection $collection */
        $collection = $this->populateRelation('FormationUnits', $criteria, $con);

        return $collection;
    }

}
