<?php

namespace App\Model\Base;

use App\Model\Formations as ChildFormations;
use App\Model\FormationsQuery as ChildFormationsQuery;
use App\Model\Map\FormationsTableMap;
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
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * Base class that represents a query for the `formations` table.
 *
 * @method static orderById($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the id column
 * @method static orderByArmyId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the army_id column
 * @method static orderByName($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the name column
 * @method static orderByFormationType($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the formation_type column
 * @method static orderByPvBudget($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the pv_budget column
 * @method static orderByIsActive($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the is_active column
 * @method static orderByCreatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the created_at column
 * @method static orderByUpdatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the updated_at column
 *
 * @method static groupById() Group by the id column
 * @method static groupByArmyId() Group by the army_id column
 * @method static groupByName() Group by the name column
 * @method static groupByFormationType() Group by the formation_type column
 * @method static groupByPvBudget() Group by the pv_budget column
 * @method static groupByIsActive() Group by the is_active column
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
 * @method static leftJoinArmies($relationAlias = null) Adds a LEFT JOIN clause to the query using the Armies relation
 * @method static rightJoinArmies($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Armies relation
 * @method static innerJoinArmies($relationAlias = null) Adds a INNER JOIN clause to the query using the Armies relation
 *
 * @method static joinWithArmies($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Armies relation
 *
 * @method static leftJoinWithArmies() Adds a LEFT JOIN clause and with to the query using the Armies relation
 * @method static rightJoinWithArmies() Adds a RIGHT JOIN clause and with to the query using the Armies relation
 * @method static innerJoinWithArmies() Adds a INNER JOIN clause and with to the query using the Armies relation
 *
 * @method static leftJoinFormationUnits($relationAlias = null) Adds a LEFT JOIN clause to the query using the FormationUnits relation
 * @method static rightJoinFormationUnits($relationAlias = null) Adds a RIGHT JOIN clause to the query using the FormationUnits relation
 * @method static innerJoinFormationUnits($relationAlias = null) Adds a INNER JOIN clause to the query using the FormationUnits relation
 *
 * @method static joinWithFormationUnits($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the FormationUnits relation
 *
 * @method static leftJoinWithFormationUnits() Adds a LEFT JOIN clause and with to the query using the FormationUnits relation
 * @method static rightJoinWithFormationUnits() Adds a RIGHT JOIN clause and with to the query using the FormationUnits relation
 * @method static innerJoinWithFormationUnits() Adds a INNER JOIN clause and with to the query using the FormationUnits relation
 *
 * @method \App\Model\Formations|null findOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Formations matching the query
 * @method \App\Model\Formations findOneOrCreate(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Formations matching the query, or a new \App\Model\Formations object populated from the query conditions when no match is found
 *
 * @method \App\Model\Formations|null findOneById(int $id) Return the first \App\Model\Formations filtered by the id column
 * @method \App\Model\Formations|null findOneByArmyId(int $army_id) Return the first \App\Model\Formations filtered by the army_id column
 * @method \App\Model\Formations|null findOneByName(string $name) Return the first \App\Model\Formations filtered by the name column
 * @method \App\Model\Formations|null findOneByFormationType(string $formation_type) Return the first \App\Model\Formations filtered by the formation_type column
 * @method \App\Model\Formations|null findOneByPvBudget(int $pv_budget) Return the first \App\Model\Formations filtered by the pv_budget column
 * @method \App\Model\Formations|null findOneByIsActive(bool $is_active) Return the first \App\Model\Formations filtered by the is_active column
 * @method \App\Model\Formations|null findOneByCreatedAt(string $created_at) Return the first \App\Model\Formations filtered by the created_at column
 * @method \App\Model\Formations|null findOneByUpdatedAt(string $updated_at) Return the first \App\Model\Formations filtered by the updated_at column
 *
 * @method \App\Model\Formations requirePk($key, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the \App\Model\Formations by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Formations matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Formations requireOneById(int $id) Return the first \App\Model\Formations filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByArmyId(int $army_id) Return the first \App\Model\Formations filtered by the army_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByName(string $name) Return the first \App\Model\Formations filtered by the name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByFormationType(string $formation_type) Return the first \App\Model\Formations filtered by the formation_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByPvBudget(int $pv_budget) Return the first \App\Model\Formations filtered by the pv_budget column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByIsActive(bool $is_active) Return the first \App\Model\Formations filtered by the is_active column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByCreatedAt(string $created_at) Return the first \App\Model\Formations filtered by the created_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Formations requireOneByUpdatedAt(string $updated_at) Return the first \App\Model\Formations filtered by the updated_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Base\Collection\FormationsCollection|array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> find(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return \App\Model\Formations objects based on current ModelCriteria
 * @method \App\Model\Base\Collection\FormationsCollection findObjects(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Get \App\Model\Formations objects in ObjectCollection
 *
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findById(int|array<int> $id) Return \App\Model\Formations objects filtered by the id column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByArmyId(int|array<int> $army_id) Return \App\Model\Formations objects filtered by the army_id column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByName(string|array<string> $name) Return \App\Model\Formations objects filtered by the name column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByFormationType(string|array<string> $formation_type) Return \App\Model\Formations objects filtered by the formation_type column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByPvBudget(int|array<int> $pv_budget) Return \App\Model\Formations objects filtered by the pv_budget column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByIsActive(bool|array<bool> $is_active) Return \App\Model\Formations objects filtered by the is_active column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByCreatedAt(string|array<string> $created_at) Return \App\Model\Formations objects filtered by the created_at column
 * @method array<\App\Model\Formations>|\Propel\Runtime\Collection\Collection<\App\Model\Formations> findByUpdatedAt(string|array<string> $updated_at) Return \App\Model\Formations objects filtered by the updated_at column
 *
 * @method array<\App\Model\Formations>|\Propel\Runtime\Util\PropelModelPager<mixed> paginate($page = 1, $maxPerPage = 10, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 * @template ParentQuery of \Propel\Runtime\ActiveQuery\ModelCriteria|null = null
 * @extends \Propel\Runtime\ActiveQuery\TypedModelCriteria<ParentQuery>
 */
abstract class FormationsQuery extends TypedModelCriteria
{
    /**
     * @var string
     */
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of FormationsQuery object.
     *
     * @param string $dbName The database name
     * @param string $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(
        string $dbName = 'mech_library',
        string $modelName = '\\App\\Model\\Formations',
        ?string $modelAlias = null
    ) {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildFormationsQuery object.
     *
     * @param string|null $modelAlias The alias of a model in the query
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria to build the query from
     *
     * @return \App\Model\FormationsQuery<null>
     */
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof ChildFormationsQuery) {
            return $criteria;
        }
        $query = new ChildFormationsQuery();
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
     * @return \App\Model\Base\Formations|mixed|array the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(FormationsTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (!$this->isEmpty()) {
            return $this->findPkComplex($key, $con);
        }

        $poolKey = (string)$key;
        $obj = FormationsTableMap::getInstanceFromPool($poolKey);
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
     * @return \App\Model\Formations|null A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con): ?ChildFormations
    {
        $sql = 'SELECT `id`, `army_id`, `name`, `formation_type`, `pv_budget`, `is_active`, `created_at`, `updated_at` FROM `formations` WHERE `id` = :p0';
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
            $obj = new ChildFormations();
            $obj->hydrate($row);
            $poolKey = (string)$key;
            FormationsTableMap::addInstanceToPool($obj, $poolKey);
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
     * @return \App\Model\Base\Formations|mixed|array|null the result, formatted by the current formatter
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
     * @return \Propel\Runtime\Collection\Collection<\App\Model\Base\Formations>|mixed|array the list of results, formatted by the current formatter
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
     * Filter the query on the army_id column
     *
     * Example usage:
     * <code>
     * $query->filterByArmyId(1234); // WHERE army_id = 1234
     * $query->filterByArmyId(array(12, 34)); // WHERE army_id IN (12, 34)
     * $query->filterByArmyId(array('min' => 12)); // WHERE army_id > 12
     * </code>
     *
     * @see static::filterByArmies()
     *
     * @param mixed $armyId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByArmyId($armyId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('army_id');
        if (is_array($armyId)) {
            $useMinMax = false;
            if (isset($armyId['min'])) {
                $this->addUsingOperator($resolvedColumn, $armyId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($armyId['max'])) {
                $this->addUsingOperator($resolvedColumn, $armyId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $armyId, $comparison);

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
     * Filter the query on the formation_type column
     *
     * Example usage:
     * <code>
     * $query->filterByFormationType('fooValue'); // WHERE formation_type = 'fooValue'
     * $query->filterByFormationType('%fooValue%', Criteria::LIKE); // WHERE formation_type LIKE '%fooValue%'
     * $query->filterByFormationType(['foo', 'bar']); // WHERE formation_type IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $formationType The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByFormationType($formationType = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('formation_type');
        if ($comparison === null && is_array($formationType)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $formationType, $comparison);

        return $this;
    }

    /**
     * Filter the query on the pv_budget column
     *
     * Example usage:
     * <code>
     * $query->filterByPvBudget(1234); // WHERE pv_budget = 1234
     * $query->filterByPvBudget(array(12, 34)); // WHERE pv_budget IN (12, 34)
     * $query->filterByPvBudget(array('min' => 12)); // WHERE pv_budget > 12
     * </code>
     *
     * @param mixed $pvBudget The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByPvBudget($pvBudget = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('pv_budget');
        if (is_array($pvBudget)) {
            $useMinMax = false;
            if (isset($pvBudget['min'])) {
                $this->addUsingOperator($resolvedColumn, $pvBudget['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($pvBudget['max'])) {
                $this->addUsingOperator($resolvedColumn, $pvBudget['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $pvBudget, $comparison);

        return $this;
    }

    /**
     * Filter the query on the is_active column
     *
     * Example usage:
     * <code>
     * $query->filterByIsActive(true); // WHERE is_active = true
     * $query->filterByIsActive('yes'); // WHERE is_active = true
     * </code>
     *
     * @param string|bool|null $isActive The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByIsActive($isActive = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('is_active');
        if (is_string($isActive)) {
            $isActive = in_array(strtolower($isActive), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $isActive, $comparison);

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
     * Filter the query by a related Armies object
     *
     * @param \App\Model\Base\Armies|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Armies> $armies The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByArmies($armies, ?string $comparison = null)
    {
        if ($armies instanceof Armies) {
                $this->addUsingOperator($this->resolveLocalColumnByName('army_id'), $armies->getId(), $comparison);
        } elseif ($armies instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('army_id'),
                $armies->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByArmies() only accepts arguments of type Armies');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Armies relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinArmies(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Armies');

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
            $this->addJoinObject($join, 'Armies');
        }

        return $this;
    }

    /**
     * Use the Armies relation Armies object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\ArmiesQuery<static> A secondary query class using the current class as primary query
     */
    public function useArmiesQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\ArmiesQuery<static> $query */
        $query = $this->joinArmies($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Armies', '\App\Model\ArmiesQuery');

        return $query;
    }

    /**
     * Use the Armies relation Armies object
     *
     * @param callable(\App\Model\ArmiesQuery<mixed>):\App\Model\ArmiesQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withArmiesQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useArmiesQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Armies table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\ArmiesQuery<static> The inner query object of the EXISTS statement
     */
    public function useArmiesExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\ArmiesQuery<static> $q */
        $q = $this->useExistsQuery('Armies', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Armies table for a NOT EXISTS query.
     *
     * @see useArmiesExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\ArmiesQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useArmiesNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\ArmiesQuery<static> $q*/
        $q = $this->useExistsQuery('Armies', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Armies table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\ArmiesQuery<static> The inner query object of the IN statement
     */
    public function useInArmiesQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\ArmiesQuery<static> $q */
        $q = $this->useInQuery('Armies', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Armies table for a NOT IN query.
     *
     * @see useArmiesInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\ArmiesQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInArmiesQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\ArmiesQuery<static> $q */
        $q = $this->useInQuery('Armies', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Filter the query by a related FormationUnits object
     *
     * @param \App\Model\Base\FormationUnits|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\FormationUnits> $formationUnits the related object to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByFormationUnits(FormationUnits|ObjectCollection $formationUnits, ?string $comparison = null)
    {
        if ($formationUnits instanceof FormationUnits) {
                $this->addUsingOperator($this->resolveLocalColumnByName('id'), $formationUnits->getFormationId(), $comparison);
        } elseif ($formationUnits instanceof ObjectCollection) {
            $this
                ->useFormationUnitsQuery()
                ->filterByPrimaryKeys($formationUnits->getPrimaryKeys())
                ->endUse();
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the FormationUnits relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinFormationUnits(?string $relationAlias = null, ?string $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('FormationUnits');

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
            $this->addJoinObject($join, 'FormationUnits');
        }

        return $this;
    }

    /**
     * Use the FormationUnits relation FormationUnits object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\FormationUnitsQuery<static> A secondary query class using the current class as primary query
     */
    public function useFormationUnitsQuery(?string $relationAlias = null, string $joinType = Criteria::INNER_JOIN)
    {
        /** @var \App\Model\FormationUnitsQuery<static> $query */
        $query = $this->joinFormationUnits($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'FormationUnits', '\App\Model\FormationUnitsQuery');

        return $query;
    }

    /**
     * Use the FormationUnits relation FormationUnits object
     *
     * @param callable(\App\Model\FormationUnitsQuery<mixed>):\App\Model\FormationUnitsQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withFormationUnitsQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::INNER_JOIN
    ) {
        $relatedQuery = $this->useFormationUnitsQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to FormationUnits table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\FormationUnitsQuery<static> The inner query object of the EXISTS statement
     */
    public function useFormationUnitsExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\FormationUnitsQuery<static> $q */
        $q = $this->useExistsQuery('FormationUnits', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to FormationUnits table for a NOT EXISTS query.
     *
     * @see useFormationUnitsExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\FormationUnitsQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useFormationUnitsNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\FormationUnitsQuery<static> $q*/
        $q = $this->useExistsQuery('FormationUnits', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to FormationUnits table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\FormationUnitsQuery<static> The inner query object of the IN statement
     */
    public function useInFormationUnitsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\FormationUnitsQuery<static> $q */
        $q = $this->useInQuery('FormationUnits', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to FormationUnits table for a NOT IN query.
     *
     * @see useFormationUnitsInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\FormationUnitsQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInFormationUnitsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\FormationUnitsQuery<static> $q */
        $q = $this->useInQuery('FormationUnits', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Exclude object from result
     *
     * @param \App\Model\Formations|null $formations Object to remove from the list of results
     *
     * @return $this
     */
    public function prune(?ChildFormations $formations = null)
    {
        if (!$formations) {
            return $this;
        }

        $this
            ->combineFilters(Criteria::LOGICAL_OR)
            ->addUsingOperator($this->resolveLocalColumnByName('id'), $formations->getId(), Criteria::NOT_EQUAL)
            ->endCombineFilters();

        return $this;
    }

    /**
     * Deletes all rows from the formations table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(FormationsTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0;
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            FormationsTableMap::clearInstancePool();
            FormationsTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(FormationsTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(FormationsTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0;
            FormationsTableMap::removeInstanceFromPool($criteria);
            $affectedRows += ModelCriteria::delete($con);
            FormationsTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }
}
