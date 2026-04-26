<?php

namespace App\Model\Base;

use App\Model\Map\UnitAbilitiesTableMap;
use App\Model\UnitAbilities as ChildUnitAbilities;
use App\Model\UnitAbilitiesQuery as ChildUnitAbilitiesQuery;
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
 * Base class that represents a query for the `unit_abilities` table.
 *
 * @method static orderById($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the id column
 * @method static orderByUnitId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the unit_id column
 * @method static orderByTag($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the tag column
 * @method static orderByShort($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the short column
 * @method static orderByMedium($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the medium column
 * @method static orderByLong($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the long column
 * @method static orderByValue($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the value column
 * @method static orderByRaw($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the raw column
 *
 * @method static groupById() Group by the id column
 * @method static groupByUnitId() Group by the unit_id column
 * @method static groupByTag() Group by the tag column
 * @method static groupByShort() Group by the short column
 * @method static groupByMedium() Group by the medium column
 * @method static groupByLong() Group by the long column
 * @method static groupByValue() Group by the value column
 * @method static groupByRaw() Group by the raw column
 *
 * @method static leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method static rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method static innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method static leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method static rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method static innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method static leftJoinUnits($relationAlias = null) Adds a LEFT JOIN clause to the query using the Units relation
 * @method static rightJoinUnits($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Units relation
 * @method static innerJoinUnits($relationAlias = null) Adds a INNER JOIN clause to the query using the Units relation
 *
 * @method static joinWithUnits($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Units relation
 *
 * @method static leftJoinWithUnits() Adds a LEFT JOIN clause and with to the query using the Units relation
 * @method static rightJoinWithUnits() Adds a RIGHT JOIN clause and with to the query using the Units relation
 * @method static innerJoinWithUnits() Adds a INNER JOIN clause and with to the query using the Units relation
 *
 * @method \App\Model\UnitAbilities|null findOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\UnitAbilities matching the query
 * @method \App\Model\UnitAbilities findOneOrCreate(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\UnitAbilities matching the query, or a new \App\Model\UnitAbilities object populated from the query conditions when no match is found
 *
 * @method \App\Model\UnitAbilities|null findOneById(int $id) Return the first \App\Model\UnitAbilities filtered by the id column
 * @method \App\Model\UnitAbilities|null findOneByUnitId(int $unit_id) Return the first \App\Model\UnitAbilities filtered by the unit_id column
 * @method \App\Model\UnitAbilities|null findOneByTag(string $tag) Return the first \App\Model\UnitAbilities filtered by the tag column
 * @method \App\Model\UnitAbilities|null findOneByShort(int $short) Return the first \App\Model\UnitAbilities filtered by the short column
 * @method \App\Model\UnitAbilities|null findOneByMedium(int $medium) Return the first \App\Model\UnitAbilities filtered by the medium column
 * @method \App\Model\UnitAbilities|null findOneByLong(int $long) Return the first \App\Model\UnitAbilities filtered by the long column
 * @method \App\Model\UnitAbilities|null findOneByValue(string $value) Return the first \App\Model\UnitAbilities filtered by the value column
 * @method \App\Model\UnitAbilities|null findOneByRaw(string $raw) Return the first \App\Model\UnitAbilities filtered by the raw column
 *
 * @method \App\Model\UnitAbilities requirePk($key, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the \App\Model\UnitAbilities by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\UnitAbilities matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\UnitAbilities requireOneById(int $id) Return the first \App\Model\UnitAbilities filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByUnitId(int $unit_id) Return the first \App\Model\UnitAbilities filtered by the unit_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByTag(string $tag) Return the first \App\Model\UnitAbilities filtered by the tag column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByShort(int $short) Return the first \App\Model\UnitAbilities filtered by the short column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByMedium(int $medium) Return the first \App\Model\UnitAbilities filtered by the medium column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByLong(int $long) Return the first \App\Model\UnitAbilities filtered by the long column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByValue(string $value) Return the first \App\Model\UnitAbilities filtered by the value column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\UnitAbilities requireOneByRaw(string $raw) Return the first \App\Model\UnitAbilities filtered by the raw column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Base\Collection\UnitAbilitiesCollection|array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> find(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return \App\Model\UnitAbilities objects based on current ModelCriteria
 * @method \App\Model\Base\Collection\UnitAbilitiesCollection findObjects(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Get \App\Model\UnitAbilities objects in ObjectCollection
 *
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findById(int|array<int> $id) Return \App\Model\UnitAbilities objects filtered by the id column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByUnitId(int|array<int> $unit_id) Return \App\Model\UnitAbilities objects filtered by the unit_id column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByTag(string|array<string> $tag) Return \App\Model\UnitAbilities objects filtered by the tag column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByShort(int|array<int> $short) Return \App\Model\UnitAbilities objects filtered by the short column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByMedium(int|array<int> $medium) Return \App\Model\UnitAbilities objects filtered by the medium column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByLong(int|array<int> $long) Return \App\Model\UnitAbilities objects filtered by the long column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByValue(string|array<string> $value) Return \App\Model\UnitAbilities objects filtered by the value column
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Collection\Collection<\App\Model\UnitAbilities> findByRaw(string|array<string> $raw) Return \App\Model\UnitAbilities objects filtered by the raw column
 *
 * @method array<\App\Model\UnitAbilities>|\Propel\Runtime\Util\PropelModelPager<mixed> paginate($page = 1, $maxPerPage = 10, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 * @template ParentQuery of \Propel\Runtime\ActiveQuery\ModelCriteria|null = null
 * @extends \Propel\Runtime\ActiveQuery\TypedModelCriteria<ParentQuery>
 */
abstract class UnitAbilitiesQuery extends TypedModelCriteria
{
    /**
     * @var string
     */
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of UnitAbilitiesQuery object.
     *
     * @param string $dbName The database name
     * @param string $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(
        string $dbName = 'mech_library',
        string $modelName = '\\App\\Model\\UnitAbilities',
        ?string $modelAlias = null
    ) {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUnitAbilitiesQuery object.
     *
     * @param string|null $modelAlias The alias of a model in the query
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria to build the query from
     *
     * @return \App\Model\UnitAbilitiesQuery<null>
     */
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof ChildUnitAbilitiesQuery) {
            return $criteria;
        }
        $query = new ChildUnitAbilitiesQuery();
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
     * @return \App\Model\Base\UnitAbilities|mixed|array the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UnitAbilitiesTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (!$this->isEmpty()) {
            return $this->findPkComplex($key, $con);
        }

        $poolKey = (string)$key;
        $obj = UnitAbilitiesTableMap::getInstanceFromPool($poolKey);
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
     * @return \App\Model\UnitAbilities|null A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con): ?ChildUnitAbilities
    {
        $sql = 'SELECT `id`, `unit_id`, `tag`, `short`, `medium`, `long`, `value`, `raw` FROM `unit_abilities` WHERE `id` = :p0';
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
            $obj = new ChildUnitAbilities();
            $obj->hydrate($row);
            $poolKey = (string)$key;
            UnitAbilitiesTableMap::addInstanceToPool($obj, $poolKey);
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
     * @return \App\Model\Base\UnitAbilities|mixed|array|null the result, formatted by the current formatter
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
     * @return \Propel\Runtime\Collection\Collection<\App\Model\Base\UnitAbilities>|mixed|array the list of results, formatted by the current formatter
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
     * Filter the query on the unit_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUnitId(1234); // WHERE unit_id = 1234
     * $query->filterByUnitId(array(12, 34)); // WHERE unit_id IN (12, 34)
     * $query->filterByUnitId(array('min' => 12)); // WHERE unit_id > 12
     * </code>
     *
     * @see static::filterByUnits()
     *
     * @param mixed $unitId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByUnitId($unitId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('unit_id');
        if (is_array($unitId)) {
            $useMinMax = false;
            if (isset($unitId['min'])) {
                $this->addUsingOperator($resolvedColumn, $unitId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($unitId['max'])) {
                $this->addUsingOperator($resolvedColumn, $unitId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $unitId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the tag column
     *
     * Example usage:
     * <code>
     * $query->filterByTag('fooValue'); // WHERE tag = 'fooValue'
     * $query->filterByTag('%fooValue%', Criteria::LIKE); // WHERE tag LIKE '%fooValue%'
     * $query->filterByTag(['foo', 'bar']); // WHERE tag IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $tag The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByTag($tag = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('tag');
        if ($comparison === null && is_array($tag)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $tag, $comparison);

        return $this;
    }

    /**
     * Filter the query on the short column
     *
     * Example usage:
     * <code>
     * $query->filterByShort(1234); // WHERE short = 1234
     * $query->filterByShort(array(12, 34)); // WHERE short IN (12, 34)
     * $query->filterByShort(array('min' => 12)); // WHERE short > 12
     * </code>
     *
     * @param mixed $short The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByShort($short = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('short');
        if (is_array($short)) {
            $useMinMax = false;
            if (isset($short['min'])) {
                $this->addUsingOperator($resolvedColumn, $short['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($short['max'])) {
                $this->addUsingOperator($resolvedColumn, $short['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $short, $comparison);

        return $this;
    }

    /**
     * Filter the query on the medium column
     *
     * Example usage:
     * <code>
     * $query->filterByMedium(1234); // WHERE medium = 1234
     * $query->filterByMedium(array(12, 34)); // WHERE medium IN (12, 34)
     * $query->filterByMedium(array('min' => 12)); // WHERE medium > 12
     * </code>
     *
     * @param mixed $medium The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByMedium($medium = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('medium');
        if (is_array($medium)) {
            $useMinMax = false;
            if (isset($medium['min'])) {
                $this->addUsingOperator($resolvedColumn, $medium['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($medium['max'])) {
                $this->addUsingOperator($resolvedColumn, $medium['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $medium, $comparison);

        return $this;
    }

    /**
     * Filter the query on the long column
     *
     * Example usage:
     * <code>
     * $query->filterByLong(1234); // WHERE long = 1234
     * $query->filterByLong(array(12, 34)); // WHERE long IN (12, 34)
     * $query->filterByLong(array('min' => 12)); // WHERE long > 12
     * </code>
     *
     * @param mixed $long The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByLong($long = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('long');
        if (is_array($long)) {
            $useMinMax = false;
            if (isset($long['min'])) {
                $this->addUsingOperator($resolvedColumn, $long['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($long['max'])) {
                $this->addUsingOperator($resolvedColumn, $long['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $long, $comparison);

        return $this;
    }

    /**
     * Filter the query on the value column
     *
     * Example usage:
     * <code>
     * $query->filterByValue('fooValue'); // WHERE value = 'fooValue'
     * $query->filterByValue('%fooValue%', Criteria::LIKE); // WHERE value LIKE '%fooValue%'
     * $query->filterByValue(['foo', 'bar']); // WHERE value IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $value The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByValue($value = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('value');
        if ($comparison === null && is_array($value)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $value, $comparison);

        return $this;
    }

    /**
     * Filter the query on the raw column
     *
     * Example usage:
     * <code>
     * $query->filterByRaw('fooValue'); // WHERE raw = 'fooValue'
     * $query->filterByRaw('%fooValue%', Criteria::LIKE); // WHERE raw LIKE '%fooValue%'
     * $query->filterByRaw(['foo', 'bar']); // WHERE raw IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $raw The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByRaw($raw = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('raw');
        if ($comparison === null && is_array($raw)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $raw, $comparison);

        return $this;
    }

    /**
     * Filter the query by a related Units object
     *
     * @param \App\Model\Base\Units|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Units> $units The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByUnits($units, ?string $comparison = null)
    {
        if ($units instanceof Units) {
                $this->addUsingOperator($this->resolveLocalColumnByName('unit_id'), $units->getId(), $comparison);
        } elseif ($units instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('unit_id'),
                $units->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByUnits() only accepts arguments of type Units');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Units relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinUnits(?string $relationAlias = null, ?string $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Units');

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
            $this->addJoinObject($join, 'Units');
        }

        return $this;
    }

    /**
     * Use the Units relation Units object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\UnitsQuery<static> A secondary query class using the current class as primary query
     */
    public function useUnitsQuery(?string $relationAlias = null, string $joinType = Criteria::INNER_JOIN)
    {
        /** @var \App\Model\UnitsQuery<static> $query */
        $query = $this->joinUnits($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Units', '\App\Model\UnitsQuery');

        return $query;
    }

    /**
     * Use the Units relation Units object
     *
     * @param callable(\App\Model\UnitsQuery<mixed>):\App\Model\UnitsQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withUnitsQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::INNER_JOIN
    ) {
        $relatedQuery = $this->useUnitsQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Units table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\UnitsQuery<static> The inner query object of the EXISTS statement
     */
    public function useUnitsExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\UnitsQuery<static> $q */
        $q = $this->useExistsQuery('Units', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Units table for a NOT EXISTS query.
     *
     * @see useUnitsExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitsQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useUnitsNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitsQuery<static> $q*/
        $q = $this->useExistsQuery('Units', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Units table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\UnitsQuery<static> The inner query object of the IN statement
     */
    public function useInUnitsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\UnitsQuery<static> $q */
        $q = $this->useInQuery('Units', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Units table for a NOT IN query.
     *
     * @see useUnitsInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitsQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInUnitsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitsQuery<static> $q */
        $q = $this->useInQuery('Units', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Exclude object from result
     *
     * @param \App\Model\UnitAbilities|null $unitAbilities Object to remove from the list of results
     *
     * @return $this
     */
    public function prune(?ChildUnitAbilities $unitAbilities = null)
    {
        if (!$unitAbilities) {
            return $this;
        }

        $this
            ->combineFilters(Criteria::LOGICAL_OR)
            ->addUsingOperator($this->resolveLocalColumnByName('id'), $unitAbilities->getId(), Criteria::NOT_EQUAL)
            ->endCombineFilters();

        return $this;
    }

    /**
     * Deletes all rows from the unit_abilities table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UnitAbilitiesTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0;
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UnitAbilitiesTableMap::clearInstancePool();
            UnitAbilitiesTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UnitAbilitiesTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UnitAbilitiesTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0;
            UnitAbilitiesTableMap::removeInstanceFromPool($criteria);
            $affectedRows += ModelCriteria::delete($con);
            UnitAbilitiesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }
}
