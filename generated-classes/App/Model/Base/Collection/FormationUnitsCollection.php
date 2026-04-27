<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for FormationUnits.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\FormationUnits>
 */
class FormationUnitsCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\FormationUnits');
        $this->setFormatter(new ObjectFormatter());
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\FormationsCollection
     */
    public function populateFormations(?Criteria $criteria = null, ?ConnectionInterface $con = null): FormationsCollection
    {
        /** @var \App\Model\Base\Collection\FormationsCollection $collection */
        $collection = $this->populateRelation('Formations', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\UnitsCollection
     */
    public function populateUnits(?Criteria $criteria = null, ?ConnectionInterface $con = null): UnitsCollection
    {
        /** @var \App\Model\Base\Collection\UnitsCollection $collection */
        $collection = $this->populateRelation('Units', $criteria, $con);

        return $collection;
    }

}
