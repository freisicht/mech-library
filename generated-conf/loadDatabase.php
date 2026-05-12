<?php
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->initDatabaseMapFromDumps(array (
  'mech_library' => 
  array (
    'tablesByName' => 
    array (
      'armies' => '\\App\\Model\\Map\\ArmiesTableMap',
      'eras' => '\\App\\Model\\Map\\ErasTableMap',
      'factions' => '\\App\\Model\\Map\\FactionsTableMap',
      'formation_units' => '\\App\\Model\\Map\\FormationUnitsTableMap',
      'formations' => '\\App\\Model\\Map\\FormationsTableMap',
      'roles' => '\\App\\Model\\Map\\RolesTableMap',
      'technologies' => '\\App\\Model\\Map\\TechnologiesTableMap',
      'unit_abilities' => '\\App\\Model\\Map\\UnitAbilitiesTableMap',
      'unit_types' => '\\App\\Model\\Map\\UnitTypesTableMap',
      'units' => '\\App\\Model\\Map\\UnitsTableMap',
      'users' => '\\App\\Model\\Map\\UsersTableMap',
    ),
    'tablesByPhpName' => 
    array (
      '\\Armies' => '\\App\\Model\\Map\\ArmiesTableMap',
      '\\Eras' => '\\App\\Model\\Map\\ErasTableMap',
      '\\Factions' => '\\App\\Model\\Map\\FactionsTableMap',
      '\\FormationUnits' => '\\App\\Model\\Map\\FormationUnitsTableMap',
      '\\Formations' => '\\App\\Model\\Map\\FormationsTableMap',
      '\\Roles' => '\\App\\Model\\Map\\RolesTableMap',
      '\\Technologies' => '\\App\\Model\\Map\\TechnologiesTableMap',
      '\\UnitAbilities' => '\\App\\Model\\Map\\UnitAbilitiesTableMap',
      '\\UnitTypes' => '\\App\\Model\\Map\\UnitTypesTableMap',
      '\\Units' => '\\App\\Model\\Map\\UnitsTableMap',
      '\\Users' => '\\App\\Model\\Map\\UsersTableMap',
    ),
  ),
));
