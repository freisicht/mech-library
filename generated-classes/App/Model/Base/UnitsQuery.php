<?php

namespace App\Model\Base;

use App\Model\Map\UnitsTableMap;
use App\Model\Units as ChildUnits;
use App\Model\UnitsQuery as ChildUnitsQuery;
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
 * Base class that represents a query for the `units` table.
 *
 * @method static orderById($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the id column
 * @method static orderByMulId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the mul_id column
 * @method static orderByName($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the name column
 * @method static orderByClass($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the class column
 * @method static orderByVariant($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the variant column
 * @method static orderByTonnage($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the tonnage column
 * @method static orderByBattleValue($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the battle_value column
 * @method static orderByCost($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the cost column
 * @method static orderByRules($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the rules column
 * @method static orderByDateIntroduced($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the date_introduced column
 * @method static orderByEraId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the era_id column
 * @method static orderByEraStart($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the era_start column
 * @method static orderByImageUrl($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the image_url column
 * @method static orderByIsPublished($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the is_published column
 * @method static orderByMulRelease($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the mul_release column
 * @method static orderByTypeId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the type_id column
 * @method static orderByTechnologyId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the technology_id column
 * @method static orderByRoleId($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the role_id column
 * @method static orderByBfType($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_type column
 * @method static orderByBfSize($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_size column
 * @method static orderByBfMove($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_move column
 * @method static orderByBfTmm($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_tmm column
 * @method static orderByBfArmor($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_armor column
 * @method static orderByBfStructure($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_structure column
 * @method static orderByBfThreshold($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_threshold column
 * @method static orderByBfDamageShort($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_short column
 * @method static orderByBfDamageShortMin($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_short_min column
 * @method static orderByBfDamageMedium($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_medium column
 * @method static orderByBfDamageMediumMin($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_medium_min column
 * @method static orderByBfDamageLong($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_long column
 * @method static orderByBfDamageLongMin($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_long_min column
 * @method static orderByBfDamageExtreme($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_extreme column
 * @method static orderByBfDamageExtremeMin($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_damage_extreme_min column
 * @method static orderByBfOverheat($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_overheat column
 * @method static orderByBfPointValue($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_point_value column
 * @method static orderByBfAbilities($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the bf_abilities column
 * @method static orderByImagePath($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the image_path column
 * @method static orderByImageHash($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the image_hash column
 * @method static orderByCreatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the created_at column
 * @method static orderByUpdatedAt($order = \Propel\Runtime\ActiveQuery\Criteria::ASC) Order by the updated_at column
 *
 * @method static groupById() Group by the id column
 * @method static groupByMulId() Group by the mul_id column
 * @method static groupByName() Group by the name column
 * @method static groupByClass() Group by the class column
 * @method static groupByVariant() Group by the variant column
 * @method static groupByTonnage() Group by the tonnage column
 * @method static groupByBattleValue() Group by the battle_value column
 * @method static groupByCost() Group by the cost column
 * @method static groupByRules() Group by the rules column
 * @method static groupByDateIntroduced() Group by the date_introduced column
 * @method static groupByEraId() Group by the era_id column
 * @method static groupByEraStart() Group by the era_start column
 * @method static groupByImageUrl() Group by the image_url column
 * @method static groupByIsPublished() Group by the is_published column
 * @method static groupByMulRelease() Group by the mul_release column
 * @method static groupByTypeId() Group by the type_id column
 * @method static groupByTechnologyId() Group by the technology_id column
 * @method static groupByRoleId() Group by the role_id column
 * @method static groupByBfType() Group by the bf_type column
 * @method static groupByBfSize() Group by the bf_size column
 * @method static groupByBfMove() Group by the bf_move column
 * @method static groupByBfTmm() Group by the bf_tmm column
 * @method static groupByBfArmor() Group by the bf_armor column
 * @method static groupByBfStructure() Group by the bf_structure column
 * @method static groupByBfThreshold() Group by the bf_threshold column
 * @method static groupByBfDamageShort() Group by the bf_damage_short column
 * @method static groupByBfDamageShortMin() Group by the bf_damage_short_min column
 * @method static groupByBfDamageMedium() Group by the bf_damage_medium column
 * @method static groupByBfDamageMediumMin() Group by the bf_damage_medium_min column
 * @method static groupByBfDamageLong() Group by the bf_damage_long column
 * @method static groupByBfDamageLongMin() Group by the bf_damage_long_min column
 * @method static groupByBfDamageExtreme() Group by the bf_damage_extreme column
 * @method static groupByBfDamageExtremeMin() Group by the bf_damage_extreme_min column
 * @method static groupByBfOverheat() Group by the bf_overheat column
 * @method static groupByBfPointValue() Group by the bf_point_value column
 * @method static groupByBfAbilities() Group by the bf_abilities column
 * @method static groupByImagePath() Group by the image_path column
 * @method static groupByImageHash() Group by the image_hash column
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
 * @method static leftJoinEras($relationAlias = null) Adds a LEFT JOIN clause to the query using the Eras relation
 * @method static rightJoinEras($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Eras relation
 * @method static innerJoinEras($relationAlias = null) Adds a INNER JOIN clause to the query using the Eras relation
 *
 * @method static joinWithEras($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Eras relation
 *
 * @method static leftJoinWithEras() Adds a LEFT JOIN clause and with to the query using the Eras relation
 * @method static rightJoinWithEras() Adds a RIGHT JOIN clause and with to the query using the Eras relation
 * @method static innerJoinWithEras() Adds a INNER JOIN clause and with to the query using the Eras relation
 *
 * @method static leftJoinRoles($relationAlias = null) Adds a LEFT JOIN clause to the query using the Roles relation
 * @method static rightJoinRoles($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Roles relation
 * @method static innerJoinRoles($relationAlias = null) Adds a INNER JOIN clause to the query using the Roles relation
 *
 * @method static joinWithRoles($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Roles relation
 *
 * @method static leftJoinWithRoles() Adds a LEFT JOIN clause and with to the query using the Roles relation
 * @method static rightJoinWithRoles() Adds a RIGHT JOIN clause and with to the query using the Roles relation
 * @method static innerJoinWithRoles() Adds a INNER JOIN clause and with to the query using the Roles relation
 *
 * @method static leftJoinTechnologies($relationAlias = null) Adds a LEFT JOIN clause to the query using the Technologies relation
 * @method static rightJoinTechnologies($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Technologies relation
 * @method static innerJoinTechnologies($relationAlias = null) Adds a INNER JOIN clause to the query using the Technologies relation
 *
 * @method static joinWithTechnologies($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the Technologies relation
 *
 * @method static leftJoinWithTechnologies() Adds a LEFT JOIN clause and with to the query using the Technologies relation
 * @method static rightJoinWithTechnologies() Adds a RIGHT JOIN clause and with to the query using the Technologies relation
 * @method static innerJoinWithTechnologies() Adds a INNER JOIN clause and with to the query using the Technologies relation
 *
 * @method static leftJoinUnitTypes($relationAlias = null) Adds a LEFT JOIN clause to the query using the UnitTypes relation
 * @method static rightJoinUnitTypes($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UnitTypes relation
 * @method static innerJoinUnitTypes($relationAlias = null) Adds a INNER JOIN clause to the query using the UnitTypes relation
 *
 * @method static joinWithUnitTypes($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the UnitTypes relation
 *
 * @method static leftJoinWithUnitTypes() Adds a LEFT JOIN clause and with to the query using the UnitTypes relation
 * @method static rightJoinWithUnitTypes() Adds a RIGHT JOIN clause and with to the query using the UnitTypes relation
 * @method static innerJoinWithUnitTypes() Adds a INNER JOIN clause and with to the query using the UnitTypes relation
 *
 * @method static leftJoinUnitAbilities($relationAlias = null) Adds a LEFT JOIN clause to the query using the UnitAbilities relation
 * @method static rightJoinUnitAbilities($relationAlias = null) Adds a RIGHT JOIN clause to the query using the UnitAbilities relation
 * @method static innerJoinUnitAbilities($relationAlias = null) Adds a INNER JOIN clause to the query using the UnitAbilities relation
 *
 * @method static joinWithUnitAbilities($joinType = \Propel\Runtime\ActiveQuery\Criteria::INNER_JOIN) Adds a join clause and with to the query using the UnitAbilities relation
 *
 * @method static leftJoinWithUnitAbilities() Adds a LEFT JOIN clause and with to the query using the UnitAbilities relation
 * @method static rightJoinWithUnitAbilities() Adds a RIGHT JOIN clause and with to the query using the UnitAbilities relation
 * @method static innerJoinWithUnitAbilities() Adds a INNER JOIN clause and with to the query using the UnitAbilities relation
 *
 * @method \App\Model\Units|null findOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Units matching the query
 * @method \App\Model\Units findOneOrCreate(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Units matching the query, or a new \App\Model\Units object populated from the query conditions when no match is found
 *
 * @method \App\Model\Units|null findOneById(int $id) Return the first \App\Model\Units filtered by the id column
 * @method \App\Model\Units|null findOneByMulId(int $mul_id) Return the first \App\Model\Units filtered by the mul_id column
 * @method \App\Model\Units|null findOneByName(string $name) Return the first \App\Model\Units filtered by the name column
 * @method \App\Model\Units|null findOneByClass(string $class) Return the first \App\Model\Units filtered by the class column
 * @method \App\Model\Units|null findOneByVariant(string $variant) Return the first \App\Model\Units filtered by the variant column
 * @method \App\Model\Units|null findOneByTonnage(int $tonnage) Return the first \App\Model\Units filtered by the tonnage column
 * @method \App\Model\Units|null findOneByBattleValue(int $battle_value) Return the first \App\Model\Units filtered by the battle_value column
 * @method \App\Model\Units|null findOneByCost(string $cost) Return the first \App\Model\Units filtered by the cost column
 * @method \App\Model\Units|null findOneByRules(string $rules) Return the first \App\Model\Units filtered by the rules column
 * @method \App\Model\Units|null findOneByDateIntroduced(string $date_introduced) Return the first \App\Model\Units filtered by the date_introduced column
 * @method \App\Model\Units|null findOneByEraId(int $era_id) Return the first \App\Model\Units filtered by the era_id column
 * @method \App\Model\Units|null findOneByEraStart(int $era_start) Return the first \App\Model\Units filtered by the era_start column
 * @method \App\Model\Units|null findOneByImageUrl(string $image_url) Return the first \App\Model\Units filtered by the image_url column
 * @method \App\Model\Units|null findOneByIsPublished(bool $is_published) Return the first \App\Model\Units filtered by the is_published column
 * @method \App\Model\Units|null findOneByMulRelease(string $mul_release) Return the first \App\Model\Units filtered by the mul_release column
 * @method \App\Model\Units|null findOneByTypeId(int $type_id) Return the first \App\Model\Units filtered by the type_id column
 * @method \App\Model\Units|null findOneByTechnologyId(int $technology_id) Return the first \App\Model\Units filtered by the technology_id column
 * @method \App\Model\Units|null findOneByRoleId(int $role_id) Return the first \App\Model\Units filtered by the role_id column
 * @method \App\Model\Units|null findOneByBfType(string $bf_type) Return the first \App\Model\Units filtered by the bf_type column
 * @method \App\Model\Units|null findOneByBfSize(int $bf_size) Return the first \App\Model\Units filtered by the bf_size column
 * @method \App\Model\Units|null findOneByBfMove(string $bf_move) Return the first \App\Model\Units filtered by the bf_move column
 * @method \App\Model\Units|null findOneByBfTmm(int $bf_tmm) Return the first \App\Model\Units filtered by the bf_tmm column
 * @method \App\Model\Units|null findOneByBfArmor(int $bf_armor) Return the first \App\Model\Units filtered by the bf_armor column
 * @method \App\Model\Units|null findOneByBfStructure(int $bf_structure) Return the first \App\Model\Units filtered by the bf_structure column
 * @method \App\Model\Units|null findOneByBfThreshold(int $bf_threshold) Return the first \App\Model\Units filtered by the bf_threshold column
 * @method \App\Model\Units|null findOneByBfDamageShort(int $bf_damage_short) Return the first \App\Model\Units filtered by the bf_damage_short column
 * @method \App\Model\Units|null findOneByBfDamageShortMin(bool $bf_damage_short_min) Return the first \App\Model\Units filtered by the bf_damage_short_min column
 * @method \App\Model\Units|null findOneByBfDamageMedium(int $bf_damage_medium) Return the first \App\Model\Units filtered by the bf_damage_medium column
 * @method \App\Model\Units|null findOneByBfDamageMediumMin(bool $bf_damage_medium_min) Return the first \App\Model\Units filtered by the bf_damage_medium_min column
 * @method \App\Model\Units|null findOneByBfDamageLong(int $bf_damage_long) Return the first \App\Model\Units filtered by the bf_damage_long column
 * @method \App\Model\Units|null findOneByBfDamageLongMin(bool $bf_damage_long_min) Return the first \App\Model\Units filtered by the bf_damage_long_min column
 * @method \App\Model\Units|null findOneByBfDamageExtreme(int $bf_damage_extreme) Return the first \App\Model\Units filtered by the bf_damage_extreme column
 * @method \App\Model\Units|null findOneByBfDamageExtremeMin(bool $bf_damage_extreme_min) Return the first \App\Model\Units filtered by the bf_damage_extreme_min column
 * @method \App\Model\Units|null findOneByBfOverheat(int $bf_overheat) Return the first \App\Model\Units filtered by the bf_overheat column
 * @method \App\Model\Units|null findOneByBfPointValue(int $bf_point_value) Return the first \App\Model\Units filtered by the bf_point_value column
 * @method \App\Model\Units|null findOneByBfAbilities(string $bf_abilities) Return the first \App\Model\Units filtered by the bf_abilities column
 * @method \App\Model\Units|null findOneByImagePath(string $image_path) Return the first \App\Model\Units filtered by the image_path column
 * @method \App\Model\Units|null findOneByImageHash(string $image_hash) Return the first \App\Model\Units filtered by the image_hash column
 * @method \App\Model\Units|null findOneByCreatedAt(string $created_at) Return the first \App\Model\Units filtered by the created_at column
 * @method \App\Model\Units|null findOneByUpdatedAt(string $updated_at) Return the first \App\Model\Units filtered by the updated_at column
 *
 * @method \App\Model\Units requirePk($key, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the \App\Model\Units by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOne(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return the first \App\Model\Units matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Units requireOneById(int $id) Return the first \App\Model\Units filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByMulId(int $mul_id) Return the first \App\Model\Units filtered by the mul_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByName(string $name) Return the first \App\Model\Units filtered by the name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByClass(string $class) Return the first \App\Model\Units filtered by the class column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByVariant(string $variant) Return the first \App\Model\Units filtered by the variant column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByTonnage(int $tonnage) Return the first \App\Model\Units filtered by the tonnage column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBattleValue(int $battle_value) Return the first \App\Model\Units filtered by the battle_value column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByCost(string $cost) Return the first \App\Model\Units filtered by the cost column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByRules(string $rules) Return the first \App\Model\Units filtered by the rules column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByDateIntroduced(string $date_introduced) Return the first \App\Model\Units filtered by the date_introduced column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByEraId(int $era_id) Return the first \App\Model\Units filtered by the era_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByEraStart(int $era_start) Return the first \App\Model\Units filtered by the era_start column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByImageUrl(string $image_url) Return the first \App\Model\Units filtered by the image_url column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByIsPublished(bool $is_published) Return the first \App\Model\Units filtered by the is_published column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByMulRelease(string $mul_release) Return the first \App\Model\Units filtered by the mul_release column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByTypeId(int $type_id) Return the first \App\Model\Units filtered by the type_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByTechnologyId(int $technology_id) Return the first \App\Model\Units filtered by the technology_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByRoleId(int $role_id) Return the first \App\Model\Units filtered by the role_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfType(string $bf_type) Return the first \App\Model\Units filtered by the bf_type column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfSize(int $bf_size) Return the first \App\Model\Units filtered by the bf_size column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfMove(string $bf_move) Return the first \App\Model\Units filtered by the bf_move column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfTmm(int $bf_tmm) Return the first \App\Model\Units filtered by the bf_tmm column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfArmor(int $bf_armor) Return the first \App\Model\Units filtered by the bf_armor column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfStructure(int $bf_structure) Return the first \App\Model\Units filtered by the bf_structure column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfThreshold(int $bf_threshold) Return the first \App\Model\Units filtered by the bf_threshold column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageShort(int $bf_damage_short) Return the first \App\Model\Units filtered by the bf_damage_short column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageShortMin(bool $bf_damage_short_min) Return the first \App\Model\Units filtered by the bf_damage_short_min column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageMedium(int $bf_damage_medium) Return the first \App\Model\Units filtered by the bf_damage_medium column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageMediumMin(bool $bf_damage_medium_min) Return the first \App\Model\Units filtered by the bf_damage_medium_min column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageLong(int $bf_damage_long) Return the first \App\Model\Units filtered by the bf_damage_long column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageLongMin(bool $bf_damage_long_min) Return the first \App\Model\Units filtered by the bf_damage_long_min column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageExtreme(int $bf_damage_extreme) Return the first \App\Model\Units filtered by the bf_damage_extreme column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfDamageExtremeMin(bool $bf_damage_extreme_min) Return the first \App\Model\Units filtered by the bf_damage_extreme_min column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfOverheat(int $bf_overheat) Return the first \App\Model\Units filtered by the bf_overheat column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfPointValue(int $bf_point_value) Return the first \App\Model\Units filtered by the bf_point_value column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByBfAbilities(string $bf_abilities) Return the first \App\Model\Units filtered by the bf_abilities column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByImagePath(string $image_path) Return the first \App\Model\Units filtered by the image_path column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByImageHash(string $image_hash) Return the first \App\Model\Units filtered by the image_hash column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByCreatedAt(string $created_at) Return the first \App\Model\Units filtered by the created_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method \App\Model\Units requireOneByUpdatedAt(string $updated_at) Return the first \App\Model\Units filtered by the updated_at column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method \App\Model\Base\Collection\UnitsCollection|array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> find(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Return \App\Model\Units objects based on current ModelCriteria
 * @method \App\Model\Base\Collection\UnitsCollection findObjects(\Propel\Runtime\Connection\ConnectionInterface|null $con = null) Get \App\Model\Units objects in ObjectCollection
 *
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findById(int|array<int> $id) Return \App\Model\Units objects filtered by the id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByMulId(int|array<int> $mul_id) Return \App\Model\Units objects filtered by the mul_id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByName(string|array<string> $name) Return \App\Model\Units objects filtered by the name column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByClass(string|array<string> $class) Return \App\Model\Units objects filtered by the class column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByVariant(string|array<string> $variant) Return \App\Model\Units objects filtered by the variant column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByTonnage(int|array<int> $tonnage) Return \App\Model\Units objects filtered by the tonnage column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBattleValue(int|array<int> $battle_value) Return \App\Model\Units objects filtered by the battle_value column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByCost(string|array<string> $cost) Return \App\Model\Units objects filtered by the cost column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByRules(string|array<string> $rules) Return \App\Model\Units objects filtered by the rules column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByDateIntroduced(string|array<string> $date_introduced) Return \App\Model\Units objects filtered by the date_introduced column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByEraId(int|array<int> $era_id) Return \App\Model\Units objects filtered by the era_id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByEraStart(int|array<int> $era_start) Return \App\Model\Units objects filtered by the era_start column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByImageUrl(string|array<string> $image_url) Return \App\Model\Units objects filtered by the image_url column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByIsPublished(bool|array<bool> $is_published) Return \App\Model\Units objects filtered by the is_published column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByMulRelease(string|array<string> $mul_release) Return \App\Model\Units objects filtered by the mul_release column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByTypeId(int|array<int> $type_id) Return \App\Model\Units objects filtered by the type_id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByTechnologyId(int|array<int> $technology_id) Return \App\Model\Units objects filtered by the technology_id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByRoleId(int|array<int> $role_id) Return \App\Model\Units objects filtered by the role_id column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfType(string|array<string> $bf_type) Return \App\Model\Units objects filtered by the bf_type column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfSize(int|array<int> $bf_size) Return \App\Model\Units objects filtered by the bf_size column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfMove(string|array<string> $bf_move) Return \App\Model\Units objects filtered by the bf_move column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfTmm(int|array<int> $bf_tmm) Return \App\Model\Units objects filtered by the bf_tmm column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfArmor(int|array<int> $bf_armor) Return \App\Model\Units objects filtered by the bf_armor column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfStructure(int|array<int> $bf_structure) Return \App\Model\Units objects filtered by the bf_structure column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfThreshold(int|array<int> $bf_threshold) Return \App\Model\Units objects filtered by the bf_threshold column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageShort(int|array<int> $bf_damage_short) Return \App\Model\Units objects filtered by the bf_damage_short column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageShortMin(bool|array<bool> $bf_damage_short_min) Return \App\Model\Units objects filtered by the bf_damage_short_min column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageMedium(int|array<int> $bf_damage_medium) Return \App\Model\Units objects filtered by the bf_damage_medium column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageMediumMin(bool|array<bool> $bf_damage_medium_min) Return \App\Model\Units objects filtered by the bf_damage_medium_min column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageLong(int|array<int> $bf_damage_long) Return \App\Model\Units objects filtered by the bf_damage_long column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageLongMin(bool|array<bool> $bf_damage_long_min) Return \App\Model\Units objects filtered by the bf_damage_long_min column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageExtreme(int|array<int> $bf_damage_extreme) Return \App\Model\Units objects filtered by the bf_damage_extreme column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfDamageExtremeMin(bool|array<bool> $bf_damage_extreme_min) Return \App\Model\Units objects filtered by the bf_damage_extreme_min column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfOverheat(int|array<int> $bf_overheat) Return \App\Model\Units objects filtered by the bf_overheat column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfPointValue(int|array<int> $bf_point_value) Return \App\Model\Units objects filtered by the bf_point_value column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByBfAbilities(string|array<string> $bf_abilities) Return \App\Model\Units objects filtered by the bf_abilities column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByImagePath(string|array<string> $image_path) Return \App\Model\Units objects filtered by the image_path column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByImageHash(string|array<string> $image_hash) Return \App\Model\Units objects filtered by the image_hash column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByCreatedAt(string|array<string> $created_at) Return \App\Model\Units objects filtered by the created_at column
 * @method array<\App\Model\Units>|\Propel\Runtime\Collection\Collection<\App\Model\Units> findByUpdatedAt(string|array<string> $updated_at) Return \App\Model\Units objects filtered by the updated_at column
 *
 * @method array<\App\Model\Units>|\Propel\Runtime\Util\PropelModelPager<mixed> paginate($page = 1, $maxPerPage = 10, \Propel\Runtime\Connection\ConnectionInterface|null $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 * @template ParentQuery of \Propel\Runtime\ActiveQuery\ModelCriteria|null = null
 * @extends \Propel\Runtime\ActiveQuery\TypedModelCriteria<ParentQuery>
 */
abstract class UnitsQuery extends TypedModelCriteria
{
    /**
     * @var string
     */
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of UnitsQuery object.
     *
     * @param string $dbName The database name
     * @param string $modelName The phpName of a model, e.g. 'Book'
     * @param string|null $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct(
        string $dbName = 'mech_library',
        string $modelName = '\\App\\Model\\Units',
        ?string $modelAlias = null
    ) {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildUnitsQuery object.
     *
     * @param string|null $modelAlias The alias of a model in the query
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria Optional Criteria to build the query from
     *
     * @return \App\Model\UnitsQuery<null>
     */
    public static function create(?string $modelAlias = null, ?Criteria $criteria = null): Criteria
    {
        if ($criteria instanceof ChildUnitsQuery) {
            return $criteria;
        }
        $query = new ChildUnitsQuery();
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
     * @return \App\Model\Base\Units|mixed|array the result, formatted by the current formatter
     */
    public function findPk($key, ?ConnectionInterface $con = null)
    {
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UnitsTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (!$this->isEmpty()) {
            return $this->findPkComplex($key, $con);
        }

        $poolKey = (string)$key;
        $obj = UnitsTableMap::getInstanceFromPool($poolKey);
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
     * @return \App\Model\Units|null A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con): ?ChildUnits
    {
        $sql = 'SELECT id, mul_id, name, class, variant, tonnage, battle_value, cost, rules, date_introduced, era_id, era_start, image_url, is_published, mul_release, type_id, technology_id, role_id, bf_type, bf_size, bf_move, bf_tmm, bf_armor, bf_structure, bf_threshold, bf_damage_short, bf_damage_short_min, bf_damage_medium, bf_damage_medium_min, bf_damage_long, bf_damage_long_min, bf_damage_extreme, bf_damage_extreme_min, bf_overheat, bf_point_value, bf_abilities, image_path, image_hash, created_at, updated_at FROM units WHERE id = :p0';
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
            $obj = new ChildUnits();
            $obj->hydrate($row);
            $poolKey = (string)$key;
            UnitsTableMap::addInstanceToPool($obj, $poolKey);
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
     * @return \App\Model\Base\Units|mixed|array|null the result, formatted by the current formatter
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
     * @return \Propel\Runtime\Collection\Collection<\App\Model\Base\Units>|mixed|array the list of results, formatted by the current formatter
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
     * Filter the query on the mul_id column
     *
     * Example usage:
     * <code>
     * $query->filterByMulId(1234); // WHERE mul_id = 1234
     * $query->filterByMulId(array(12, 34)); // WHERE mul_id IN (12, 34)
     * $query->filterByMulId(array('min' => 12)); // WHERE mul_id > 12
     * </code>
     *
     * @param mixed $mulId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByMulId($mulId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('mul_id');
        if (is_array($mulId)) {
            $useMinMax = false;
            if (isset($mulId['min'])) {
                $this->addUsingOperator($resolvedColumn, $mulId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($mulId['max'])) {
                $this->addUsingOperator($resolvedColumn, $mulId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $mulId, $comparison);

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
     * Filter the query on the class column
     *
     * Example usage:
     * <code>
     * $query->filterByClass('fooValue'); // WHERE class = 'fooValue'
     * $query->filterByClass('%fooValue%', Criteria::LIKE); // WHERE class LIKE '%fooValue%'
     * $query->filterByClass(['foo', 'bar']); // WHERE class IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $class The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByClass($class = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('class');
        if ($comparison === null && is_array($class)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $class, $comparison);

        return $this;
    }

    /**
     * Filter the query on the variant column
     *
     * Example usage:
     * <code>
     * $query->filterByVariant('fooValue'); // WHERE variant = 'fooValue'
     * $query->filterByVariant('%fooValue%', Criteria::LIKE); // WHERE variant LIKE '%fooValue%'
     * $query->filterByVariant(['foo', 'bar']); // WHERE variant IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $variant The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByVariant($variant = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('variant');
        if ($comparison === null && is_array($variant)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $variant, $comparison);

        return $this;
    }

    /**
     * Filter the query on the tonnage column
     *
     * Example usage:
     * <code>
     * $query->filterByTonnage(1234); // WHERE tonnage = 1234
     * $query->filterByTonnage(array(12, 34)); // WHERE tonnage IN (12, 34)
     * $query->filterByTonnage(array('min' => 12)); // WHERE tonnage > 12
     * </code>
     *
     * @param mixed $tonnage The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByTonnage($tonnage = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('tonnage');
        if (is_array($tonnage)) {
            $useMinMax = false;
            if (isset($tonnage['min'])) {
                $this->addUsingOperator($resolvedColumn, $tonnage['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($tonnage['max'])) {
                $this->addUsingOperator($resolvedColumn, $tonnage['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $tonnage, $comparison);

        return $this;
    }

    /**
     * Filter the query on the battle_value column
     *
     * Example usage:
     * <code>
     * $query->filterByBattleValue(1234); // WHERE battle_value = 1234
     * $query->filterByBattleValue(array(12, 34)); // WHERE battle_value IN (12, 34)
     * $query->filterByBattleValue(array('min' => 12)); // WHERE battle_value > 12
     * </code>
     *
     * @param mixed $battleValue The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBattleValue($battleValue = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('battle_value');
        if (is_array($battleValue)) {
            $useMinMax = false;
            if (isset($battleValue['min'])) {
                $this->addUsingOperator($resolvedColumn, $battleValue['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($battleValue['max'])) {
                $this->addUsingOperator($resolvedColumn, $battleValue['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $battleValue, $comparison);

        return $this;
    }

    /**
     * Filter the query on the cost column
     *
     * Example usage:
     * <code>
     * $query->filterByCost(1234); // WHERE cost = 1234
     * $query->filterByCost(array(12, 34)); // WHERE cost IN (12, 34)
     * $query->filterByCost(array('min' => 12)); // WHERE cost > 12
     * </code>
     *
     * @param mixed $cost The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByCost($cost = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('cost');
        if (is_array($cost)) {
            $useMinMax = false;
            if (isset($cost['min'])) {
                $this->addUsingOperator($resolvedColumn, $cost['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($cost['max'])) {
                $this->addUsingOperator($resolvedColumn, $cost['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $cost, $comparison);

        return $this;
    }

    /**
     * Filter the query on the rules column
     *
     * Example usage:
     * <code>
     * $query->filterByRules('fooValue'); // WHERE rules = 'fooValue'
     * $query->filterByRules('%fooValue%', Criteria::LIKE); // WHERE rules LIKE '%fooValue%'
     * $query->filterByRules(['foo', 'bar']); // WHERE rules IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $rules The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByRules($rules = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('rules');
        if ($comparison === null && is_array($rules)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $rules, $comparison);

        return $this;
    }

    /**
     * Filter the query on the date_introduced column
     *
     * Example usage:
     * <code>
     * $query->filterByDateIntroduced('fooValue'); // WHERE date_introduced = 'fooValue'
     * $query->filterByDateIntroduced('%fooValue%', Criteria::LIKE); // WHERE date_introduced LIKE '%fooValue%'
     * $query->filterByDateIntroduced(['foo', 'bar']); // WHERE date_introduced IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $dateIntroduced The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByDateIntroduced($dateIntroduced = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('date_introduced');
        if ($comparison === null && is_array($dateIntroduced)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $dateIntroduced, $comparison);

        return $this;
    }

    /**
     * Filter the query on the era_id column
     *
     * Example usage:
     * <code>
     * $query->filterByEraId(1234); // WHERE era_id = 1234
     * $query->filterByEraId(array(12, 34)); // WHERE era_id IN (12, 34)
     * $query->filterByEraId(array('min' => 12)); // WHERE era_id > 12
     * </code>
     *
     * @see static::filterByEras()
     *
     * @param mixed $eraId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByEraId($eraId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('era_id');
        if (is_array($eraId)) {
            $useMinMax = false;
            if (isset($eraId['min'])) {
                $this->addUsingOperator($resolvedColumn, $eraId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($eraId['max'])) {
                $this->addUsingOperator($resolvedColumn, $eraId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $eraId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the era_start column
     *
     * Example usage:
     * <code>
     * $query->filterByEraStart(1234); // WHERE era_start = 1234
     * $query->filterByEraStart(array(12, 34)); // WHERE era_start IN (12, 34)
     * $query->filterByEraStart(array('min' => 12)); // WHERE era_start > 12
     * </code>
     *
     * @param mixed $eraStart The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByEraStart($eraStart = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('era_start');
        if (is_array($eraStart)) {
            $useMinMax = false;
            if (isset($eraStart['min'])) {
                $this->addUsingOperator($resolvedColumn, $eraStart['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($eraStart['max'])) {
                $this->addUsingOperator($resolvedColumn, $eraStart['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $eraStart, $comparison);

        return $this;
    }

    /**
     * Filter the query on the image_url column
     *
     * Example usage:
     * <code>
     * $query->filterByImageUrl('fooValue'); // WHERE image_url = 'fooValue'
     * $query->filterByImageUrl('%fooValue%', Criteria::LIKE); // WHERE image_url LIKE '%fooValue%'
     * $query->filterByImageUrl(['foo', 'bar']); // WHERE image_url IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $imageUrl The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByImageUrl($imageUrl = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('image_url');
        if ($comparison === null && is_array($imageUrl)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $imageUrl, $comparison);

        return $this;
    }

    /**
     * Filter the query on the is_published column
     *
     * Example usage:
     * <code>
     * $query->filterByIsPublished(true); // WHERE is_published = true
     * $query->filterByIsPublished('yes'); // WHERE is_published = true
     * </code>
     *
     * @param string|bool|null $isPublished The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByIsPublished($isPublished = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('is_published');
        if (is_string($isPublished)) {
            $isPublished = in_array(strtolower($isPublished), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $isPublished, $comparison);

        return $this;
    }

    /**
     * Filter the query on the mul_release column
     *
     * Example usage:
     * <code>
     * $query->filterByMulRelease(1234); // WHERE mul_release = 1234
     * $query->filterByMulRelease(array(12, 34)); // WHERE mul_release IN (12, 34)
     * $query->filterByMulRelease(array('min' => 12)); // WHERE mul_release > 12
     * </code>
     *
     * @param mixed $mulRelease The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByMulRelease($mulRelease = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('mul_release');
        if (is_array($mulRelease)) {
            $useMinMax = false;
            if (isset($mulRelease['min'])) {
                $this->addUsingOperator($resolvedColumn, $mulRelease['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($mulRelease['max'])) {
                $this->addUsingOperator($resolvedColumn, $mulRelease['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $mulRelease, $comparison);

        return $this;
    }

    /**
     * Filter the query on the type_id column
     *
     * Example usage:
     * <code>
     * $query->filterByTypeId(1234); // WHERE type_id = 1234
     * $query->filterByTypeId(array(12, 34)); // WHERE type_id IN (12, 34)
     * $query->filterByTypeId(array('min' => 12)); // WHERE type_id > 12
     * </code>
     *
     * @see static::filterByUnitTypes()
     *
     * @param mixed $typeId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByTypeId($typeId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('type_id');
        if (is_array($typeId)) {
            $useMinMax = false;
            if (isset($typeId['min'])) {
                $this->addUsingOperator($resolvedColumn, $typeId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($typeId['max'])) {
                $this->addUsingOperator($resolvedColumn, $typeId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $typeId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the technology_id column
     *
     * Example usage:
     * <code>
     * $query->filterByTechnologyId(1234); // WHERE technology_id = 1234
     * $query->filterByTechnologyId(array(12, 34)); // WHERE technology_id IN (12, 34)
     * $query->filterByTechnologyId(array('min' => 12)); // WHERE technology_id > 12
     * </code>
     *
     * @see static::filterByTechnologies()
     *
     * @param mixed $technologyId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByTechnologyId($technologyId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('technology_id');
        if (is_array($technologyId)) {
            $useMinMax = false;
            if (isset($technologyId['min'])) {
                $this->addUsingOperator($resolvedColumn, $technologyId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($technologyId['max'])) {
                $this->addUsingOperator($resolvedColumn, $technologyId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $technologyId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the role_id column
     *
     * Example usage:
     * <code>
     * $query->filterByRoleId(1234); // WHERE role_id = 1234
     * $query->filterByRoleId(array(12, 34)); // WHERE role_id IN (12, 34)
     * $query->filterByRoleId(array('min' => 12)); // WHERE role_id > 12
     * </code>
     *
     * @see static::filterByRoles()
     *
     * @param mixed $roleId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByRoleId($roleId = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('role_id');
        if (is_array($roleId)) {
            $useMinMax = false;
            if (isset($roleId['min'])) {
                $this->addUsingOperator($resolvedColumn, $roleId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($roleId['max'])) {
                $this->addUsingOperator($resolvedColumn, $roleId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $roleId, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_type column
     *
     * Example usage:
     * <code>
     * $query->filterByBfType('fooValue'); // WHERE bf_type = 'fooValue'
     * $query->filterByBfType('%fooValue%', Criteria::LIKE); // WHERE bf_type LIKE '%fooValue%'
     * $query->filterByBfType(['foo', 'bar']); // WHERE bf_type IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $bfType The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfType($bfType = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_type');
        if ($comparison === null && is_array($bfType)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $bfType, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_size column
     *
     * Example usage:
     * <code>
     * $query->filterByBfSize(1234); // WHERE bf_size = 1234
     * $query->filterByBfSize(array(12, 34)); // WHERE bf_size IN (12, 34)
     * $query->filterByBfSize(array('min' => 12)); // WHERE bf_size > 12
     * </code>
     *
     * @param mixed $bfSize The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfSize($bfSize = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_size');
        if (is_array($bfSize)) {
            $useMinMax = false;
            if (isset($bfSize['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfSize['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfSize['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfSize['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfSize, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_move column
     *
     * Example usage:
     * <code>
     * $query->filterByBfMove('fooValue'); // WHERE bf_move = 'fooValue'
     * $query->filterByBfMove('%fooValue%', Criteria::LIKE); // WHERE bf_move LIKE '%fooValue%'
     * $query->filterByBfMove(['foo', 'bar']); // WHERE bf_move IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $bfMove The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfMove($bfMove = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_move');
        if ($comparison === null && is_array($bfMove)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $bfMove, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_tmm column
     *
     * Example usage:
     * <code>
     * $query->filterByBfTmm(1234); // WHERE bf_tmm = 1234
     * $query->filterByBfTmm(array(12, 34)); // WHERE bf_tmm IN (12, 34)
     * $query->filterByBfTmm(array('min' => 12)); // WHERE bf_tmm > 12
     * </code>
     *
     * @param mixed $bfTmm The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfTmm($bfTmm = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_tmm');
        if (is_array($bfTmm)) {
            $useMinMax = false;
            if (isset($bfTmm['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfTmm['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfTmm['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfTmm['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfTmm, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_armor column
     *
     * Example usage:
     * <code>
     * $query->filterByBfArmor(1234); // WHERE bf_armor = 1234
     * $query->filterByBfArmor(array(12, 34)); // WHERE bf_armor IN (12, 34)
     * $query->filterByBfArmor(array('min' => 12)); // WHERE bf_armor > 12
     * </code>
     *
     * @param mixed $bfArmor The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfArmor($bfArmor = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_armor');
        if (is_array($bfArmor)) {
            $useMinMax = false;
            if (isset($bfArmor['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfArmor['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfArmor['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfArmor['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfArmor, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_structure column
     *
     * Example usage:
     * <code>
     * $query->filterByBfStructure(1234); // WHERE bf_structure = 1234
     * $query->filterByBfStructure(array(12, 34)); // WHERE bf_structure IN (12, 34)
     * $query->filterByBfStructure(array('min' => 12)); // WHERE bf_structure > 12
     * </code>
     *
     * @param mixed $bfStructure The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfStructure($bfStructure = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_structure');
        if (is_array($bfStructure)) {
            $useMinMax = false;
            if (isset($bfStructure['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfStructure['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfStructure['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfStructure['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfStructure, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_threshold column
     *
     * Example usage:
     * <code>
     * $query->filterByBfThreshold(1234); // WHERE bf_threshold = 1234
     * $query->filterByBfThreshold(array(12, 34)); // WHERE bf_threshold IN (12, 34)
     * $query->filterByBfThreshold(array('min' => 12)); // WHERE bf_threshold > 12
     * </code>
     *
     * @param mixed $bfThreshold The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfThreshold($bfThreshold = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_threshold');
        if (is_array($bfThreshold)) {
            $useMinMax = false;
            if (isset($bfThreshold['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfThreshold['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfThreshold['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfThreshold['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfThreshold, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_short column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageShort(1234); // WHERE bf_damage_short = 1234
     * $query->filterByBfDamageShort(array(12, 34)); // WHERE bf_damage_short IN (12, 34)
     * $query->filterByBfDamageShort(array('min' => 12)); // WHERE bf_damage_short > 12
     * </code>
     *
     * @param mixed $bfDamageShort The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageShort($bfDamageShort = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_short');
        if (is_array($bfDamageShort)) {
            $useMinMax = false;
            if (isset($bfDamageShort['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageShort['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfDamageShort['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageShort['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageShort, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_short_min column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageShortMin(true); // WHERE bf_damage_short_min = true
     * $query->filterByBfDamageShortMin('yes'); // WHERE bf_damage_short_min = true
     * </code>
     *
     * @param string|bool|null $bfDamageShortMin The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageShortMin($bfDamageShortMin = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_short_min');
        if (is_string($bfDamageShortMin)) {
            $bfDamageShortMin = in_array(strtolower($bfDamageShortMin), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageShortMin, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_medium column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageMedium(1234); // WHERE bf_damage_medium = 1234
     * $query->filterByBfDamageMedium(array(12, 34)); // WHERE bf_damage_medium IN (12, 34)
     * $query->filterByBfDamageMedium(array('min' => 12)); // WHERE bf_damage_medium > 12
     * </code>
     *
     * @param mixed $bfDamageMedium The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageMedium($bfDamageMedium = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_medium');
        if (is_array($bfDamageMedium)) {
            $useMinMax = false;
            if (isset($bfDamageMedium['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageMedium['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfDamageMedium['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageMedium['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageMedium, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_medium_min column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageMediumMin(true); // WHERE bf_damage_medium_min = true
     * $query->filterByBfDamageMediumMin('yes'); // WHERE bf_damage_medium_min = true
     * </code>
     *
     * @param string|bool|null $bfDamageMediumMin The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageMediumMin($bfDamageMediumMin = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_medium_min');
        if (is_string($bfDamageMediumMin)) {
            $bfDamageMediumMin = in_array(strtolower($bfDamageMediumMin), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageMediumMin, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_long column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageLong(1234); // WHERE bf_damage_long = 1234
     * $query->filterByBfDamageLong(array(12, 34)); // WHERE bf_damage_long IN (12, 34)
     * $query->filterByBfDamageLong(array('min' => 12)); // WHERE bf_damage_long > 12
     * </code>
     *
     * @param mixed $bfDamageLong The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageLong($bfDamageLong = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_long');
        if (is_array($bfDamageLong)) {
            $useMinMax = false;
            if (isset($bfDamageLong['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageLong['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfDamageLong['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageLong['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageLong, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_long_min column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageLongMin(true); // WHERE bf_damage_long_min = true
     * $query->filterByBfDamageLongMin('yes'); // WHERE bf_damage_long_min = true
     * </code>
     *
     * @param string|bool|null $bfDamageLongMin The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageLongMin($bfDamageLongMin = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_long_min');
        if (is_string($bfDamageLongMin)) {
            $bfDamageLongMin = in_array(strtolower($bfDamageLongMin), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageLongMin, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_extreme column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageExtreme(1234); // WHERE bf_damage_extreme = 1234
     * $query->filterByBfDamageExtreme(array(12, 34)); // WHERE bf_damage_extreme IN (12, 34)
     * $query->filterByBfDamageExtreme(array('min' => 12)); // WHERE bf_damage_extreme > 12
     * </code>
     *
     * @param mixed $bfDamageExtreme The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageExtreme($bfDamageExtreme = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_extreme');
        if (is_array($bfDamageExtreme)) {
            $useMinMax = false;
            if (isset($bfDamageExtreme['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageExtreme['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfDamageExtreme['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfDamageExtreme['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageExtreme, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_damage_extreme_min column
     *
     * Example usage:
     * <code>
     * $query->filterByBfDamageExtremeMin(true); // WHERE bf_damage_extreme_min = true
     * $query->filterByBfDamageExtremeMin('yes'); // WHERE bf_damage_extreme_min = true
     * </code>
     *
     * @param string|bool|null $bfDamageExtremeMin The value to use as filter.
     *      Non-boolean arguments are converted using the following rules:
     *          - 1, '1', 'true', 'on', and 'yes' are converted to boolean true
     *          - 0, '0', 'false', 'off', and 'no' are converted to boolean false
     *      Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfDamageExtremeMin($bfDamageExtremeMin = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_damage_extreme_min');
        if (is_string($bfDamageExtremeMin)) {
            $bfDamageExtremeMin = in_array(strtolower($bfDamageExtremeMin), ['false', 'off', '-', 'no', 'n', '0', ''], true) ? false : true;
        }
        $this->addUsingOperator($resolvedColumn, $bfDamageExtremeMin, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_overheat column
     *
     * Example usage:
     * <code>
     * $query->filterByBfOverheat(1234); // WHERE bf_overheat = 1234
     * $query->filterByBfOverheat(array(12, 34)); // WHERE bf_overheat IN (12, 34)
     * $query->filterByBfOverheat(array('min' => 12)); // WHERE bf_overheat > 12
     * </code>
     *
     * @param mixed $bfOverheat The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfOverheat($bfOverheat = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_overheat');
        if (is_array($bfOverheat)) {
            $useMinMax = false;
            if (isset($bfOverheat['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfOverheat['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfOverheat['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfOverheat['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfOverheat, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_point_value column
     *
     * Example usage:
     * <code>
     * $query->filterByBfPointValue(1234); // WHERE bf_point_value = 1234
     * $query->filterByBfPointValue(array(12, 34)); // WHERE bf_point_value IN (12, 34)
     * $query->filterByBfPointValue(array('min' => 12)); // WHERE bf_point_value > 12
     * </code>
     *
     * @param mixed $bfPointValue The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfPointValue($bfPointValue = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_point_value');
        if (is_array($bfPointValue)) {
            $useMinMax = false;
            if (isset($bfPointValue['min'])) {
                $this->addUsingOperator($resolvedColumn, $bfPointValue['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($bfPointValue['max'])) {
                $this->addUsingOperator($resolvedColumn, $bfPointValue['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if ($comparison === null) {
                $comparison = Criteria::IN;
            }
        }
        $this->addUsingOperator($resolvedColumn, $bfPointValue, $comparison);

        return $this;
    }

    /**
     * Filter the query on the bf_abilities column
     *
     * Example usage:
     * <code>
     * $query->filterByBfAbilities('fooValue'); // WHERE bf_abilities = 'fooValue'
     * $query->filterByBfAbilities('%fooValue%', Criteria::LIKE); // WHERE bf_abilities LIKE '%fooValue%'
     * $query->filterByBfAbilities(['foo', 'bar']); // WHERE bf_abilities IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $bfAbilities The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByBfAbilities($bfAbilities = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('bf_abilities');
        if ($comparison === null && is_array($bfAbilities)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $bfAbilities, $comparison);

        return $this;
    }

    /**
     * Filter the query on the image_path column
     *
     * Example usage:
     * <code>
     * $query->filterByImagePath('fooValue'); // WHERE image_path = 'fooValue'
     * $query->filterByImagePath('%fooValue%', Criteria::LIKE); // WHERE image_path LIKE '%fooValue%'
     * $query->filterByImagePath(['foo', 'bar']); // WHERE image_path IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $imagePath The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByImagePath($imagePath = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('image_path');
        if ($comparison === null && is_array($imagePath)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $imagePath, $comparison);

        return $this;
    }

    /**
     * Filter the query on the image_hash column
     *
     * Example usage:
     * <code>
     * $query->filterByImageHash('fooValue'); // WHERE image_hash = 'fooValue'
     * $query->filterByImageHash('%fooValue%', Criteria::LIKE); // WHERE image_hash LIKE '%fooValue%'
     * $query->filterByImageHash(['foo', 'bar']); // WHERE image_hash IN ('foo', 'bar')
     * </code>
     *
     * @param array<string>|string|null $imageHash The value to use as filter.
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByImageHash($imageHash = null, ?string $comparison = null)
    {
        $resolvedColumn = $this->resolveLocalColumnByName('image_hash');
        if ($comparison === null && is_array($imageHash)) {
            $comparison = Criteria::IN;
        }
        $this->addUsingOperator($resolvedColumn, $imageHash, $comparison);

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
     * Filter the query by a related Eras object
     *
     * @param \App\Model\Base\Eras|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Eras> $eras The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByEras($eras, ?string $comparison = null)
    {
        if ($eras instanceof Eras) {
                $this->addUsingOperator($this->resolveLocalColumnByName('era_id'), $eras->getId(), $comparison);
        } elseif ($eras instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('era_id'),
                $eras->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByEras() only accepts arguments of type Eras');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Eras relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinEras(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Eras');

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
            $this->addJoinObject($join, 'Eras');
        }

        return $this;
    }

    /**
     * Use the Eras relation Eras object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\ErasQuery<static> A secondary query class using the current class as primary query
     */
    public function useErasQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\ErasQuery<static> $query */
        $query = $this->joinEras($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Eras', '\App\Model\ErasQuery');

        return $query;
    }

    /**
     * Use the Eras relation Eras object
     *
     * @param callable(\App\Model\ErasQuery<mixed>):\App\Model\ErasQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withErasQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useErasQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Eras table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\ErasQuery<static> The inner query object of the EXISTS statement
     */
    public function useErasExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\ErasQuery<static> $q */
        $q = $this->useExistsQuery('Eras', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Eras table for a NOT EXISTS query.
     *
     * @see useErasExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\ErasQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useErasNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\ErasQuery<static> $q*/
        $q = $this->useExistsQuery('Eras', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Eras table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\ErasQuery<static> The inner query object of the IN statement
     */
    public function useInErasQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\ErasQuery<static> $q */
        $q = $this->useInQuery('Eras', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Eras table for a NOT IN query.
     *
     * @see useErasInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\ErasQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInErasQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\ErasQuery<static> $q */
        $q = $this->useInQuery('Eras', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Filter the query by a related Roles object
     *
     * @param \App\Model\Base\Roles|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Roles> $roles The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByRoles($roles, ?string $comparison = null)
    {
        if ($roles instanceof Roles) {
                $this->addUsingOperator($this->resolveLocalColumnByName('role_id'), $roles->getId(), $comparison);
        } elseif ($roles instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('role_id'),
                $roles->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByRoles() only accepts arguments of type Roles');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Roles relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinRoles(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Roles');

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
            $this->addJoinObject($join, 'Roles');
        }

        return $this;
    }

    /**
     * Use the Roles relation Roles object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\RolesQuery<static> A secondary query class using the current class as primary query
     */
    public function useRolesQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\RolesQuery<static> $query */
        $query = $this->joinRoles($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Roles', '\App\Model\RolesQuery');

        return $query;
    }

    /**
     * Use the Roles relation Roles object
     *
     * @param callable(\App\Model\RolesQuery<mixed>):\App\Model\RolesQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withRolesQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useRolesQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Roles table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\RolesQuery<static> The inner query object of the EXISTS statement
     */
    public function useRolesExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\RolesQuery<static> $q */
        $q = $this->useExistsQuery('Roles', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Roles table for a NOT EXISTS query.
     *
     * @see useRolesExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\RolesQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useRolesNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\RolesQuery<static> $q*/
        $q = $this->useExistsQuery('Roles', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Roles table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\RolesQuery<static> The inner query object of the IN statement
     */
    public function useInRolesQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\RolesQuery<static> $q */
        $q = $this->useInQuery('Roles', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Roles table for a NOT IN query.
     *
     * @see useRolesInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\RolesQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInRolesQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\RolesQuery<static> $q */
        $q = $this->useInQuery('Roles', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Filter the query by a related Technologies object
     *
     * @param \App\Model\Base\Technologies|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\Technologies> $technologies The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByTechnologies($technologies, ?string $comparison = null)
    {
        if ($technologies instanceof Technologies) {
                $this->addUsingOperator($this->resolveLocalColumnByName('technology_id'), $technologies->getId(), $comparison);
        } elseif ($technologies instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('technology_id'),
                $technologies->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByTechnologies() only accepts arguments of type Technologies');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the Technologies relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinTechnologies(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Technologies');

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
            $this->addJoinObject($join, 'Technologies');
        }

        return $this;
    }

    /**
     * Use the Technologies relation Technologies object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\TechnologiesQuery<static> A secondary query class using the current class as primary query
     */
    public function useTechnologiesQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\TechnologiesQuery<static> $query */
        $query = $this->joinTechnologies($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'Technologies', '\App\Model\TechnologiesQuery');

        return $query;
    }

    /**
     * Use the Technologies relation Technologies object
     *
     * @param callable(\App\Model\TechnologiesQuery<mixed>):\App\Model\TechnologiesQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withTechnologiesQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useTechnologiesQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to Technologies table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\TechnologiesQuery<static> The inner query object of the EXISTS statement
     */
    public function useTechnologiesExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\TechnologiesQuery<static> $q */
        $q = $this->useExistsQuery('Technologies', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to Technologies table for a NOT EXISTS query.
     *
     * @see useTechnologiesExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\TechnologiesQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useTechnologiesNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\TechnologiesQuery<static> $q*/
        $q = $this->useExistsQuery('Technologies', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to Technologies table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\TechnologiesQuery<static> The inner query object of the IN statement
     */
    public function useInTechnologiesQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\TechnologiesQuery<static> $q */
        $q = $this->useInQuery('Technologies', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to Technologies table for a NOT IN query.
     *
     * @see useTechnologiesInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\TechnologiesQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInTechnologiesQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\TechnologiesQuery<static> $q */
        $q = $this->useInQuery('Technologies', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Filter the query by a related UnitTypes object
     *
     * @param \App\Model\Base\UnitTypes|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\UnitTypes> $unitTypes The related object(s) to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return static
     */
    public function filterByUnitTypes($unitTypes, ?string $comparison = null)
    {
        if ($unitTypes instanceof UnitTypes) {
                $this->addUsingOperator($this->resolveLocalColumnByName('type_id'), $unitTypes->getId(), $comparison);
        } elseif ($unitTypes instanceof ObjectCollection) {
            $this->addUsingOperator(
                $this->resolveLocalColumnByName('type_id'),
                $unitTypes->toKeyValue('PrimaryKey', 'Id'),
                $comparison ??= Criteria::IN,
            );
        } else {
            throw new PropelException('filterByUnitTypes() only accepts arguments of type UnitTypes');
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the UnitTypes relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinUnitTypes(?string $relationAlias = null, ?string $joinType = Criteria::LEFT_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UnitTypes');

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
            $this->addJoinObject($join, 'UnitTypes');
        }

        return $this;
    }

    /**
     * Use the UnitTypes relation UnitTypes object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\UnitTypesQuery<static> A secondary query class using the current class as primary query
     */
    public function useUnitTypesQuery(?string $relationAlias = null, string $joinType = Criteria::LEFT_JOIN)
    {
        /** @var \App\Model\UnitTypesQuery<static> $query */
        $query = $this->joinUnitTypes($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'UnitTypes', '\App\Model\UnitTypesQuery');

        return $query;
    }

    /**
     * Use the UnitTypes relation UnitTypes object
     *
     * @param callable(\App\Model\UnitTypesQuery<mixed>):\App\Model\UnitTypesQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withUnitTypesQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::LEFT_JOIN
    ) {
        $relatedQuery = $this->useUnitTypesQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to UnitTypes table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\UnitTypesQuery<static> The inner query object of the EXISTS statement
     */
    public function useUnitTypesExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\UnitTypesQuery<static> $q */
        $q = $this->useExistsQuery('UnitTypes', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to UnitTypes table for a NOT EXISTS query.
     *
     * @see useUnitTypesExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitTypesQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useUnitTypesNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitTypesQuery<static> $q*/
        $q = $this->useExistsQuery('UnitTypes', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to UnitTypes table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\UnitTypesQuery<static> The inner query object of the IN statement
     */
    public function useInUnitTypesQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\UnitTypesQuery<static> $q */
        $q = $this->useInQuery('UnitTypes', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to UnitTypes table for a NOT IN query.
     *
     * @see useUnitTypesInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitTypesQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInUnitTypesQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitTypesQuery<static> $q */
        $q = $this->useInQuery('UnitTypes', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Filter the query by a related UnitAbilities object
     *
     * @param \App\Model\Base\UnitAbilities|\Propel\Runtime\Collection\ObjectCollection<\App\Model\Base\UnitAbilities> $unitAbilities the related object to use as filter
     * @param string|null $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this
     */
    public function filterByUnitAbilities(UnitAbilities|ObjectCollection $unitAbilities, ?string $comparison = null)
    {
        if ($unitAbilities instanceof UnitAbilities) {
                $this->addUsingOperator($this->resolveLocalColumnByName('id'), $unitAbilities->getUnitId(), $comparison);
        } elseif ($unitAbilities instanceof ObjectCollection) {
            $this
                ->useUnitAbilitiesQuery()
                ->filterByPrimaryKeys($unitAbilities->getPrimaryKeys())
                ->endUse();
        }

        return $this;
    }

    /**
     * Adds a JOIN clause to the query using the UnitAbilities relation
     *
     * @param string|null $relationAlias Optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function joinUnitAbilities(?string $relationAlias = null, ?string $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('UnitAbilities');

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
            $this->addJoinObject($join, 'UnitAbilities');
        }

        return $this;
    }

    /**
     * Use the UnitAbilities relation UnitAbilities object
     *
     * @see useQuery()
     *
     * @param string|null $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \App\Model\UnitAbilitiesQuery<static> A secondary query class using the current class as primary query
     */
    public function useUnitAbilitiesQuery(?string $relationAlias = null, string $joinType = Criteria::INNER_JOIN)
    {
        /** @var \App\Model\UnitAbilitiesQuery<static> $query */
        $query = $this->joinUnitAbilities($relationAlias, $joinType)
            ->useQuery($relationAlias ?: 'UnitAbilities', '\App\Model\UnitAbilitiesQuery');

        return $query;
    }

    /**
     * Use the UnitAbilities relation UnitAbilities object
     *
     * @param callable(\App\Model\UnitAbilitiesQuery<mixed>):\App\Model\UnitAbilitiesQuery<mixed> $callable A function working on the related query
     * @param string|null $relationAlias optional alias for the relation
     * @param string|null $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this
     */
    public function withUnitAbilitiesQuery(
        callable $callable,
        ?string $relationAlias = null,
        ?string $joinType = Criteria::INNER_JOIN
    ) {
        $relatedQuery = $this->useUnitAbilitiesQuery(
            $relationAlias,
            $joinType,
        );
        $callable($relatedQuery);
        $relatedQuery->endUse();

        return $this;
    }

    /**
     * Use the relation to UnitAbilities table for an EXISTS query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\FilterExpression\ExistsFilter::TYPE_* $typeOfExists
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfExists Either ExistsQueryCriterion::TYPE_EXISTS or ExistsQueryCriterion::TYPE_NOT_EXISTS
     *
     * @return \App\Model\UnitAbilitiesQuery<static> The inner query object of the EXISTS statement
     */
    public function useUnitAbilitiesExistsQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfExists = 'EXISTS')
    {
        /** @var \App\Model\UnitAbilitiesQuery<static> $q */
        $q = $this->useExistsQuery('UnitAbilities', $modelAlias, $queryClass, $typeOfExists);

        return $q;
    }

    /**
     * Use the relation to UnitAbilities table for a NOT EXISTS query.
     *
     * @see useUnitAbilitiesExistsQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitAbilitiesQuery<static> The inner query object of the NOT EXISTS statement
     */
    public function useUnitAbilitiesNotExistsQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitAbilitiesQuery<static> $q*/
        $q = $this->useExistsQuery('UnitAbilities', $modelAlias, $queryClass, 'NOT EXISTS');

        return $q;
    }

    /**
     * Use the relation to UnitAbilities table for an IN query.
     *
     * @phpstan-param \Propel\Runtime\ActiveQuery\Criteria::*IN $typeOfIn
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::useInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     * @param string $typeOfIn Criteria::IN or Criteria::NOT_IN
     *
     * @return \App\Model\UnitAbilitiesQuery<static> The inner query object of the IN statement
     */
    public function useInUnitAbilitiesQuery(?string $modelAlias = null, ?string $queryClass = null, string $typeOfIn = Criteria::IN)
    {
        /** @var \App\Model\UnitAbilitiesQuery<static> $q */
        $q = $this->useInQuery('UnitAbilities', $modelAlias, $queryClass, $typeOfIn);

        return $q;
    }

    /**
     * Use the relation to UnitAbilities table for a NOT IN query.
     *
     * @see useUnitAbilitiesInQuery()
     *
     * @param string|null $modelAlias sets an alias for the nested query
     * @param class-string<\Propel\Runtime\ActiveQuery\ModelCriteria>|null $queryClass Allows to use a custom query class for the exists query, like ExtendedBookQuery::class
     *
     * @return \App\Model\UnitAbilitiesQuery<static> The inner query object of the NOT IN statement
     */
    public function useNotInUnitAbilitiesQuery(?string $modelAlias = null, ?string $queryClass = null)
    {
        /** @var \App\Model\UnitAbilitiesQuery<static> $q */
        $q = $this->useInQuery('UnitAbilities', $modelAlias, $queryClass, Criteria::NOT_IN);

        return $q;
    }

    /**
     * Exclude object from result
     *
     * @param \App\Model\Units|null $units Object to remove from the list of results
     *
     * @return $this
     */
    public function prune(?ChildUnits $units = null)
    {
        if (!$units) {
            return $this;
        }

        $this
            ->combineFilters(Criteria::LOGICAL_OR)
            ->addUsingOperator($this->resolveLocalColumnByName('id'), $units->getId(), Criteria::NOT_EQUAL)
            ->endCombineFilters();

        return $this;
    }

    /**
     * Deletes all rows from the units table.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con the connection to use
     *
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(?ConnectionInterface $con = null): int
    {
        if (!$con) {
            $con = Propel::getServiceContainer()->getWriteConnection(UnitsTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0;
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            UnitsTableMap::clearInstancePool();
            UnitsTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(UnitsTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(UnitsTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0;
            UnitsTableMap::removeInstanceFromPool($criteria);
            $affectedRows += ModelCriteria::delete($con);
            UnitsTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }
}
