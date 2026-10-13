<?php

namespace App\Model\Base;

use App\Model\Armies as ChildArmies;
use App\Model\ArmiesQuery as ChildArmiesQuery;
use App\Model\Map\ArmiesTableMap;
use Exception;
use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\ActiveQuery\TypedModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use function is_array;
use function is_bool;
use function sprintf;

/**
 * Base class that represents a query for the `armies` table.
 *
 * @method static orderById($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the id column
 * @method static orderByName($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the name column
 * @method static orderByRequiredGroupId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the required_group_id column
 * @method static orderByCreatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the created_at column
 * @method static orderByUpdatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the updated_at column
 *
 * @method static groupById() Group by the id column
 * @method static groupByName() Group by the name column
 * @method static groupByRequiredGroupId() Group by the required_group_id column
 * @method static groupByCreatedAt() Group by the created_at column
 * @method static groupByUpdatedAt() Group by the updated_at column
 *
 * @method static leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method static rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method static innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method static leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method static rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method static innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method static leftJoinFormations($relationAlias = null) Adds a LEFT JOIN clause to the query using the Formations relation
 * @method static rightJoinFormations($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Formations relation
 * @method static innerJoinFormations($relationAlias = null) Adds a INNER JOIN clause to the query using the Formations relation
 *
 * @method static joinWithFormations($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Formations relation
 *
 * @method static leftJoinWithFormations() Adds a LEFT JOIN clause and with to the query using the Formations relation
 * @method static rightJoinWithFormations() Adds a RIGHT JOIN clause and with to the query using the Formations relation
 * @method static innerJoinWithFormations() Adds a INNER JOIN clause and with to the query using the Formations relation
 *
 * @method \App\Model\Armies|null findOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Armies matching the query
 * @method \App\Model\Armies findOneOrCreate(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Armies matching the query, or a new \App\Model\Armies object populated from the query conditions when no match is found
 *
 * @method \App\Model\Armies|null findOneById(int $id) Return the first \App\Model\Armies filtered by the id column
 * @method \App\Model\Armies|null findOneByName(string $name) Return the first \App\Model\Armies filtered by the name column
 * @method \App\Model\Armies|null findOneByRequiredGroupId(string $required_group_id) Return the first \App\Model\Armies filtered by the required_group_id column
 * @method \App\Model\Armies|null findOneByCreatedAt(string $created_at) Return the first \App\Model\Armies filtered by the created_at column
 * @method \App\Model\Armies|null findOneByUpdatedAt(string $updated_at) Return the first \App\Model\Armies filtered by the updated_at column
 *
 * @method \App\Model\Armies requirePk($key, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the \App\Model\Armies by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Armies requireOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Armies matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Armies requireOneById(int $id) Return the first \App\Model\Armies filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Armies requireOneByName(string $name) Return the first \App\Model\Armies filtered by the name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Armies requireOneByRequiredGroupId(string $required_group_id) Return the first \App\Model\Armies filtered by the required_group_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Armies requireOneByCreatedAt(string $created_at) Return the first \App\Model\Armies filtered by the created_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Armies requireOneByUpdatedAt(string $updated_at) Return the first \App\Model\Armies filtered by the updated_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Base\Collection\ArmiesCollection|array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> find(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return \App\Model\Armies objects based on current ModelCriteria
 * @method \App\Model\Base\Collection\ArmiesCollection findObjects(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Get \App\Model\Armies objects in ObjectCollection
 *
 * @method array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> findById(int|array<int> $id) Return \App\Model\Armies objects filtered by the id column
 * @method array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> findByName(string|array<string> $name) Return \App\Model\Armies objects filtered by the name column
 * @method array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> findByRequiredGroupId(string|array<string> $required_group_id) Return \App\Model\Armies objects filtered by the required_group_id column
 * @method array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> findByCreatedAt(string|array<string> $created_at) Return \App\Model\Armies objects filtered by the created_at column
 * @method array<\App\Model\Armies>|\Propel\Runtime\Collection\Collection<\App\Model\Armies> findByUpdatedAt(string|array<string> $updated_at) Return \App\Model\Armies objects filtered by the updated_at column
 *
 * @method array<\App\Model\Armies>|\Propel\Runtime\Util\PropelModelPager<mixed> paginate($page = 1, $maxPerPage = 10, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 * @template ParentQuery of \Propel\Runtime\ActiveQuery\ModelCriteria|null = null
 * @extends \Propel\Runtime\ActiveQuery\TypedModelCriteria<ParentQuery>
 */
abstract class ArmiesQuery extends TypedModelCriteria
{
    /**
     * @var string
     */
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of ArmiesQuery object.
     *
     * @param string $dbName The database name
     * @param string $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(
        string $dbName = 'mech_library',
        string $modelName = '\\App\\Model\\Armies',
        ?string $modelAlias = null
    ) {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildArmiesQuery object.
     *
     * @param string|null $modelAlias The alias of a model in the query
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria to build the query from
     *
     * @return \App\Model\ArmiesQuery<null>
     */
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof ChildArmiesQuery) {
            return $criteria;
        }
        $query = new ChildArmiesQuery();
        if ($modelAlias !== null) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj = $c->findPk(12, $con);
     * </code>
     *
     * @param int $key Primary key to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return \App\Model\Base\Armies|mixed|array the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ArmiesTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (!$this->isEmpty()) {
            return $this->findPkComplex($key, $con);
        }

        $poolKey = (string)$key;
        $obj = ArmiesTableMap::getInstanceFromPool($poolKey);
        if ($obj !== null) {
            return $obj;
        }

        return $this->findPkSimple($key, $con);
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param mixed $key Primary key to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface $con A connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \App\Model\Armies|null A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con): ?ChildArmies
    {
        $sql = 'SELECT `id`, `name`, `required_group_id`, `created_at`, `updated_at` FROM `armies` WHERE `id` = :p0';
        $stmt = $con->prepare($sql);
        if (is_bool($stmt)) {
            throw new PropelException('Failed to initialize statement');
        }
        $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);

            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row) {
            $obj = new ChildArmies();
            $obj->hydrate($row);
            $poolKey = (string)$key;
            ArmiesTableMap::addInstanceToPool($obj, $poolKey);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param mixed $key Primary key to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface $con A connection object
     *
     * @return \App\Model\Base\Armies|mixed|array|null the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     *
     * <code>$c->findPks(array(12, 56, 832), $con);</code>
     *
     * @param array $keys Primary keys to use for the query
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con an optional connection object
     *
     * @return \Propel\Runtime\Collection\Collection<\App\Model\Base\Armies>|mixed|array the list of results, formatted by the current formatter
     */
    public function findPks($keys, ?ConnectionInterface $con = null)
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param mixed $key Primary key to use for the query
     *
     * @return $this
     */
    public function filterByPrimaryKey($key)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('id');
        $this->addUsingOperator($resolvedColumn, $key, Criteria::EQUAL);

        return $this;
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param array $keys The list of primary key values to use for the query
     *
     * @return static
     */
    public function filterByPrimaryKeys(array $keys)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('id');
        $this->addUsingOperator($resolvedColumn, $keys, Criteria::IN);

        return $this;
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterById($id = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('id');
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingOperator($resolvedColumn, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingOperator($resolvedColumn, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $id, $comparison);

        return $this;
    }

    /**
     * Filter the query on the name column
     *
     * Example usage:
     * <code>
     * $query->filterByName('fooValue'); // WHERE name = 'fooValue'
     * $query->filterByName('%fooValue%', Criteria::LIKE); // WHERE name LIKE '%fooValue%'
     * $query->filterByName(['foo', 'bar']); // WHERE name IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $name The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByName($name = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('name');
        if ($comparison === null && is_array($name)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $name, $comparison);

        return $this;
    }

    /**
     * Filter the query on the required_group_id column
     *
     * Example usage:
     * <code>
     * $query->filterByRequiredGroupId('fooValue'); // WHERE required_group_id = 'fooValue'
     * $query->filterByRequiredGroupId('%fooValue%', Criteria::LIKE); // WHERE required_group_id LIKE '%fooValue%'
     * $query->filterByRequiredGroupId(['foo', 'bar']); // WHERE required_group_id IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $requiredGroupId The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByRequiredGroupId($requiredGroupId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('required_group_id');
        if ($comparison === null && is_array($requiredGroupId)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $requiredGroupId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the created_at column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedAt('2011-03-14'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt('now'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE created_at > '2011-03-13'
     * </code>
     *
     * @param mixed $createdAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByCreatedAt($createdAt = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('created_at');
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingOperator($resolvedColumn, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingOperator($resolvedColumn, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $createdAt, $comparison);

        return $this;
    }

    /**
     * Filter the query on the updated_at column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedAt('2011-03-14'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt('now'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE updated_at > '2011-03-13'
     * </code>
     *
     * @param mixed $updatedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByUpdatedAt($updatedAt = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('updated_at');
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingOperator($resolvedColumn, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingOperator($resolvedColumn, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $updatedAt, $comparison);

        return $this;
    }

    /**
     * Filter the query by a related Formations object
     *
     * @param \App\Model\Base\Formations|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Formations> $formations the related object to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByFormations(Formations|ObjectCollection $formations, ?string $comparison = null)
    {
        if ($formations instanceof Formations) {
                $this->addUsingOperator($this->resolveLocalColumnByName('id'), $formations->getArmyId(), $comparison);
        } elseif ($formations instanceof ObjectCollection) {
            $this
                ->useFormationsQuery()
                ->filterByPrimaryKeys($formations->getPrimaryKeys())
                ->endUse();
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Formations relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinFormations(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Formations');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $leftAlias = $this->useAliasInSQL ? $this->getModelAlias() : null;
        $join->setupJoinCondition($this, $relationMap, $leftAlias, $relationAlias);
        $previousJoin = $this->getPreviousJoin();
        if ($previousJoin instanceof ModelJoin) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Formations');
        }

        return $this;
    }

    /**
     * Use the Formations relation Formations object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\FormationsQuery<static> A secondary query class using the current class as primary query
     */
    public function useFormationsQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\FormationsQuery<static> $query */
        $query = $this->joinFormations($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Formations', '\App\Model\FormationsQuery');

        return $query;
    }

    /**
     * Use the Formations relation Formations object
     *
     * @param callable(\App\Model\FormationsQuery<mixed>):\App\Model\FormationsQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withFormationsQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useFormationsQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Formations table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\FormationsQuery<static> The inner query object of the EXISTS statement
     */
    public function useFormationsExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\FormationsQuery<static> $q */
        $q = $this->useExistsQuery('Formations', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Formations table for a NOT EXISTS query.
     *
     * @see useFormationsExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\FormationsQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useFormationsNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\FormationsQuery<static> $q*/
        $q = $this->useExistsQuery('Formations', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Formations table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\FormationsQuery<static> The inner query object of the IN statement
     */
    public function useInFormationsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\FormationsQuery<static> $q */
        $q = $this->useInQuery('Formations', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Formations table for a NOT IN query.
     *
     * @see useFormationsInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\FormationsQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInFormationsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\FormationsQuery<static> $q */
        $q = $this->useInQuery('Formations', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Exclude object from result
     *
     * @param \App\Model\Armies|null $armies Object to remove from the list of results
     *
     * @return $this
     */
    public function prune(?ChildArmies $armies = null)
    {
        if (!$armies) {
            return $this;
        }

        $this
            ->combineFilters(Criteria::LOGICAL_OR)
            ->addUsingOperator($this->resolveLocalColumnByName('id'), $armies->getId(), Criteria::NOT_EQUAL)
            ->endCombineFilters();

        return $this;
    }

    /**
     * Deletes all rows from the armies table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ArmiesTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0;
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ArmiesTableMap::clearInstancePool();
            ArmiesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver). This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     */
    public function delete(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ArmiesTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ArmiesTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0;
            ArmiesTableMap::removeInstanceFromPool($criteria);
            $affectedRows += ModelCriteria::delete($con);
            ArmiesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }
}
