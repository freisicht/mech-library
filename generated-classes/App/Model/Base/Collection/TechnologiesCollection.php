<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Technologies.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Technologies>
 */
class TechnologiesCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Technologies');
        $this->setFormatter(new ObjectFormatter());
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \App\Model\Base\Collection\UnitsCollection
     */
    public function populateUnitss(?Criteria $criteria = null, ?ConnectionInterface $con = null): UnitsCollection
    {
        /** @var \App\Model\Base\Collection\UnitsCollection $collection */
        $collection = $this->populateRelation('Units', $criteria, $con);

        return $collection;
    }

}
