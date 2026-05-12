<?php

namespace App\Model\Base\Collection;

use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Formatter\ObjectFormatter;

/**
 * Custom collection for Users.
 *
 * @extends \Propel\Runtime\Collection\ObjectCollection<\App\Model\Users>
 */
class UsersCollection extends ObjectCollection
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setModel('\App\Model\Users');
        $this->setFormatter(new ObjectFormatter());
    }

}
