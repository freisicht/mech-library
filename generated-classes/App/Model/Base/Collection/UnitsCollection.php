<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Units.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Units>
 */
class UnitsCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Units');
        $this->setFormatter(new ObjectFormatter());
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\ErasCollection
     */
    public function populateEras(?Criteria $criteria = null, ?ConnectionInterface $con = null): ErasCollection
    {
        /** @var \App\Model\Base\Collection\ErasCollection $collection */
        $collection = $this->populateRelation('Eras', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\RolesCollection
     */
    public function populateRoles(?Criteria $criteria = null, ?ConnectionInterface $con = null): RolesCollection
    {
        /** @var \App\Model\Base\Collection\RolesCollection $collection */
        $collection = $this->populateRelation('Roles', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\TechnologiesCollection
     */
    public function populateTechnologies(?Criteria $criteria = null, ?ConnectionInterface $con = null): TechnologiesCollection
    {
        /** @var \App\Model\Base\Collection\TechnologiesCollection $collection */
        $collection = $this->populateRelation('Technologies', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\UnitTypesCollection
     */
    public function populateUnitTypes(?Criteria $criteria = null, ?ConnectionInterface $con = null): UnitTypesCollection
    {
        /** @var \App\Model\Base\Collection\UnitTypesCollection $collection */
        $collection = $this->populateRelation('UnitTypes', $criteria, $con);

        return $collection;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\UnitAbilitiesCollection
     */
    public function populateUnitAbilitiess(?Criteria $criteria = null, ?ConnectionInterface $con = null): UnitAbilitiesCollection
    {
        /** @var \App\Model\Base\Collection\UnitAbilitiesCollection $collection */
        $collection = $this->populateRelation('UnitAbilities', $criteria, $con);

        return $collection;
    }

}
