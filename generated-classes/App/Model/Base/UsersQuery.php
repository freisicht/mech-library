<?php

namespace App\Model\Base;

use App\Model\Map\UsersTableMap;
use App\Model\Users as ChildUsers;
use App\Model\UsersQuery as ChildUsersQuery;
use Exception;
use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\TypedModelCriteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use function is_array;
use function is_bool;
use function sprintf;

/**
 * Base class that represents a query for the `users` table.
 *
 * @method static orderById($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the id column
 * @method static orderByMmUserId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the mm_user_id column
 * @method static orderByUsername($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the username column
 * @method static orderByEmail($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the email column
 * @method static orderByChannelsJson($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the channels_json column
 * @method static orderByChannelsRefreshedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the channels_refreshed_at column
 * @method static orderByLastLoginAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the last_login_at column
 * @method static orderByCreatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the created_at column
 *
 * @method static groupById() Group by the id column
 * @method static groupByMmUserId() Group by the mm_user_id column
 * @method static groupByUsername() Group by the username column
 * @method static groupByEmail() Group by the email column
 * @method static groupByChannelsJson() Group by the channels_json column
 * @method static groupByChannelsRefreshedAt() Group by the channels_refreshed_at column
 * @method static groupByLastLoginAt() Group by the last_login_at column
 * @method static groupByCreatedAt() Group by the created_at column
 *
 * @method static leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method static rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method static innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method static leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method static rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method static innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method \App\Model\Users|null findOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Users matching the query
 * @method \App\Model\Users findOneOrCreate(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Users matching the query, or a new \App\Model\Users object populated from the query conditions when no match is found
 *
 * @method \App\Model\Users|null findOneById(int $id) Return the first \App\Model\Users filtered by the id column
 * @method \App\Model\Users|null findOneByMmUserId(string $mm_user_id) Return the first \App\Model\Users filtered by the mm_user_id column
 * @method \App\Model\Users|null findOneByUsername(string $username) Return the first \App\Model\Users filtered by the username column
 * @method \App\Model\Users|null findOneByEmail(string $email) Return the first \App\Model\Users filtered by the email column
 * @method \App\Model\Users|null findOneByChannelsJson(string $channels_json) Return the first \App\Model\Users filtered by the channels_json column
 * @method \App\Model\Users|null findOneByChannelsRefreshedAt(string $channels_refreshed_at) Return the first \App\Model\Users filtered by the channels_refreshed_at column
 * @method \App\Model\Users|null findOneByLastLoginAt(string $last_login_at) Return the first \App\Model\Users filtered by the last_login_at column
 * @method \App\Model\Users|null findOneByCreatedAt(string $created_at) Return the first \App\Model\Users filtered by the created_at column
 *
 * @method \App\Model\Users requirePk($key, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the \App\Model\Users by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Users matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Users requireOneById(int $id) Return the first \App\Model\Users filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByMmUserId(string $mm_user_id) Return the first \App\Model\Users filtered by the mm_user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByUsername(string $username) Return the first \App\Model\Users filtered by the username column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByEmail(string $email) Return the first \App\Model\Users filtered by the email column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByChannelsJson(string $channels_json) Return the first \App\Model\Users filtered by the channels_json column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByChannelsRefreshedAt(string $channels_refreshed_at) Return the first \App\Model\Users filtered by the channels_refreshed_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByLastLoginAt(string $last_login_at) Return the first \App\Model\Users filtered by the last_login_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Users requireOneByCreatedAt(string $created_at) Return the first \App\Model\Users filtered by the created_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Base\Collection\UsersCollection|array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> find(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return \App\Model\Users objects based on current ModelCriteria
 * @method \App\Model\Base\Collection\UsersCollection findObjects(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Get \App\Model\Users objects in ObjectCollection
 *
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findById(int|array<int> $id) Return \App\Model\Users objects filtered by the id column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByMmUserId(string|array<string> $mm_user_id) Return \App\Model\Users objects filtered by the mm_user_id column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByUsername(string|array<string> $username) Return \App\Model\Users objects filtered by the username column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByEmail(string|array<string> $email) Return \App\Model\Users objects filtered by the email column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByChannelsJson(string|array<string> $channels_json) Return \App\Model\Users objects filtered by the channels_json column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByChannelsRefreshedAt(string|array<string> $channels_refreshed_at) Return \App\Model\Users objects filtered by the channels_refreshed_at column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByLastLoginAt(string|array<string> $last_login_at) Return \App\Model\Users objects filtered by the last_login_at column
 * @method array<\App\Model\Users>|\Propel\Runtime\Collection\Collection<\App\Model\Users> findByCreatedAt(string|array<string> $created_at) Return \App\Model\Users objects filtered by the created_at column
 *
 * @method array<\App\Model\Users>|\Propel\Runtime\Util\PropelModelPager<mixed> paginate($page = 1, $maxPerPage = 10, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 * @template ParentQuery of \Propel\Runtime\ActiveQuery\ModelCriteria|null = null
 * @extends \Propel\Runtime\ActiveQuery\TypedModelCriteria<ParentQuery>
 */
abstract class UsersQuery extends TypedModelCriteria
{
    /**
     * @var string
     */
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of UsersQuery object.
     *
     * @param string $dbName The database name
     * @param string $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(
        string $dbName = 'mech_library',
        string $modelName = '\\App\\Model\\Users',
        ?string $modelAlias = null
    ) {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUsersQuery object.
     *
     * @param string|null $modelAlias The alias of a model in the query
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria to build the query from
     *
     * @return \App\Model\UsersQuery<null>
     */
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof ChildUsersQuery) {
            return $criteria;
        }
        $query = new ChildUsersQuery();
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
     * @return \App\Model\Base\Users|mixed|array the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UsersTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (!$this->isEmpty()) {
            return $this->findPkComplex($key, $con);
        }

        $poolKey = (string)$key;
        $obj = UsersTableMap::getInstanceFromPool($poolKey);
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
     * @return \App\Model\Users|null A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con): ?ChildUsers
    {
        $sql = 'SELECT `id`, `mm_user_id`, `username`, `email`, `channels_json`, `channels_refreshed_at`, `last_login_at`, `created_at` FROM `users` WHERE `id` = :p0';
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
            $obj = new ChildUsers();
            $obj->hydrate($row);
            $poolKey = (string)$key;
            UsersTableMap::addInstanceToPool($obj, $poolKey);
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
     * @return \App\Model\Base\Users|mixed|array|null the result, formatted by the current formatter
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
     * @return \Propel\Runtime\Collection\Collection<\App\Model\Base\Users>|mixed|array the list of results, formatted by the current formatter
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
     * Filter the query on the mm_user_id column
     *
     * Example usage:
     * <code>
     * $query->filterByMmUserId('fooValue'); // WHERE mm_user_id = 'fooValue'
     * $query->filterByMmUserId('%fooValue%', Criteria::LIKE); // WHERE mm_user_id LIKE '%fooValue%'
     * $query->filterByMmUserId(['foo', 'bar']); // WHERE mm_user_id IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $mmUserId The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByMmUserId($mmUserId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('mm_user_id');
        if ($comparison === null && is_array($mmUserId)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $mmUserId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the username column
     *
     * Example usage:
     * <code>
     * $query->filterByUsername('fooValue'); // WHERE username = 'fooValue'
     * $query->filterByUsername('%fooValue%', Criteria::LIKE); // WHERE username LIKE '%fooValue%'
     * $query->filterByUsername(['foo', 'bar']); // WHERE username IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $username The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByUsername($username = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('username');
        if ($comparison === null && is_array($username)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $username, $comparison);

        return $this;
    }

    /**
     * Filter the query on the email column
     *
     * Example usage:
     * <code>
     * $query->filterByEmail('fooValue'); // WHERE email = 'fooValue'
     * $query->filterByEmail('%fooValue%', Criteria::LIKE); // WHERE email LIKE '%fooValue%'
     * $query->filterByEmail(['foo', 'bar']); // WHERE email IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $email The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByEmail($email = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('email');
        if ($comparison === null && is_array($email)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $email, $comparison);

        return $this;
    }

    /**
     * Filter the query on the channels_json column
     *
     * Example usage:
     * <code>
     * $query->filterByChannelsJson('fooValue'); // WHERE channels_json = 'fooValue'
     * $query->filterByChannelsJson('%fooValue%', Criteria::LIKE); // WHERE channels_json LIKE '%fooValue%'
     * $query->filterByChannelsJson(['foo', 'bar']); // WHERE channels_json IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $channelsJson The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByChannelsJson($channelsJson = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('channels_json');
        if ($comparison === null && is_array($channelsJson)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $channelsJson, $comparison);

        return $this;
    }

    /**
     * Filter the query on the channels_refreshed_at column
     *
     * Example usage:
     * <code>
     * $query->filterByChannelsRefreshedAt('2011-03-14'); // WHERE channels_refreshed_at = '2011-03-14'
     * $query->filterByChannelsRefreshedAt('now'); // WHERE channels_refreshed_at = '2011-03-14'
     * $query->filterByChannelsRefreshedAt(array('max' => 'yesterday')); // WHERE channels_refreshed_at > '2011-03-13'
     * </code>
     *
     * @param mixed $channelsRefreshedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByChannelsRefreshedAt($channelsRefreshedAt = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('channels_refreshed_at');
        if (is_array($channelsRefreshedAt)) {
            $useMinMax = false;
            if (isset($channelsRefreshedAt['min'])) {
                $this->addUsingOperator($resolvedColumn, $channelsRefreshedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($channelsRefreshedAt['max'])) {
                $this->addUsingOperator($resolvedColumn, $channelsRefreshedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $channelsRefreshedAt, $comparison);

        return $this;
    }

    /**
     * Filter the query on the last_login_at column
     *
     * Example usage:
     * <code>
     * $query->filterByLastLoginAt('2011-03-14'); // WHERE last_login_at = '2011-03-14'
     * $query->filterByLastLoginAt('now'); // WHERE last_login_at = '2011-03-14'
     * $query->filterByLastLoginAt(array('max' => 'yesterday')); // WHERE last_login_at > '2011-03-13'
     * </code>
     *
     * @param mixed $lastLoginAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByLastLoginAt($lastLoginAt = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('last_login_at');
        if (is_array($lastLoginAt)) {
            $useMinMax = false;
            if (isset($lastLoginAt['min'])) {
                $this->addUsingOperator($resolvedColumn, $lastLoginAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($lastLoginAt['max'])) {
                $this->addUsingOperator($resolvedColumn, $lastLoginAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $lastLoginAt, $comparison);

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
     * Exclude object from result
     *
     * @param \App\Model\Users|null $users Object to remove from the list of results
     *
     * @return $this
     */
    public function prune(?ChildUsers $users = null)
    {
        if (!$users) {
            return $this;
        }

        $this
            ->combineFilters(Criteria::LOGICAL_OR)
            ->addUsingOperator($this->resolveLocalColumnByName('id'), $users->getId(), Criteria::NOT_EQUAL)
            ->endCombineFilters();

        return $this;
    }

    /**
     * Deletes all rows from the users table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UsersTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0;
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UsersTableMap::clearInstancePool();
            UsersTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UsersTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UsersTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0;
            UsersTableMap::removeInstanceFromPool($criteria);
            $affectedRows += ModelCriteria::delete($con);
            UsersTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }
}
