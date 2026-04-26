<?php
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->initDatabaseMapFromDumps(array (
  'mech_library' => 
  array (
    'tablesByName' => 
    array (
      'eras' => '\\App\\Model\\Map\\ErasTableMap',
      'factions' => '\\App\\Model\\Map\\FactionsTableMap',
      'roles' => '\\App\\Model\\Map\\RolesTableMap',
      'technologies' => '\\App\\Model\\Map\\TechnologiesTableMap',
      'unit_abilities' => '\\App\\Model\\Map\\UnitAbilitiesTableMap',
      'unit_types' => '\\App\\Model\\Map\\UnitTypesTableMap',
      'units' => '\\App\\Model\\Map\\UnitsTableMap',
    ),
    'tablesByPhpName' => 
    array (
      '\\Eras' => '\\App\\Model\\Map\\ErasTableMap',
      '\\Factions' => '\\App\\Model\\Map\\FactionsTableMap',
      '\\Roles' => '\\App\\Model\\Map\\RolesTableMap',
      '\\Technologies' => '\\App\\Model\\Map\\TechnologiesTableMap',
      '\\UnitAbilities' => '\\App\\Model\\Map\\UnitAbilitiesTableMap',
      '\\UnitTypes' => '\\App\\Model\\Map\\UnitTypesTableMap',
      '\\Units' => '\\App\\Model\\Map\\UnitsTableMap',
    ),
  ),
));
