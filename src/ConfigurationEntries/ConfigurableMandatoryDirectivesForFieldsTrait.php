<?php
namespace PoP\MandatoryDirectivesByConfiguration\ConfigurationEntries;

use PoP\AccessControl\Environment;
use PoP\AccessControl\Schema\SchemaModes;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

trait ConfigurableMandatoryDirectivesForFieldsTrait
{
    /**
     * Configuration entries
     *
     * @return array
     */
    abstract protected static function getConfigurationEntries(): array;

    /**
     * Field names to remove
     *
     * @return array
     */
    protected function getFieldNames(): array
    {
        return array_map(
            function($entry) {
                // The tuple has format [typeResolverClass, fieldName] or [typeResolverClass, fieldName, $role] or [typeResolverClass, fieldName, $capability]
                // So, in position [1], will always be the $fieldName
                return $entry[1];
            },
           static::getConfigurationEntries()
        );
    }

    /**
     * Configuration entries
     *
     * @return array
     */
    final protected function getEntries(TypeResolverInterface $typeResolver, string $fieldName): array
    {
        return $this->getMatchingEntries(
            static::getConfigurationEntries(),
            $typeResolver,
            $fieldName
        );
    }

    /**
     * Filter all the entries from the list which apply to the passed typeResolver and fieldName
     *
     * @param boolean $include
     * @param array $entryList
     * @param TypeResolverInterface $typeResolver
     * @param string $fieldName
     * @return boolean
     */
    final protected function getMatchingEntries(array $entryList, TypeResolverInterface $typeResolver, string $fieldName): array
    {
        $typeResolverClass = get_class($typeResolver);
        /**
         * If enabling individual control over public/private schema modes, then we must also check
         * that this field has the required mode.
         * If the schema mode was not defined in the entry, then this field is valid if the default
         * schema mode is the same required one
         */
        $enableIndividualControl = $matchNullControlEntry = false;
        $individualControlSchemaMode = null;
        if (Environment::enableIndividualControlForPublicPrivateSchemaMode()) {
            $enableIndividualControl = true;
            $individualControlSchemaMode = $this->getSchemaMode();
            $matchNullControlEntry =
                (Environment::usePrivateSchemaMode() && $individualControlSchemaMode == SchemaModes::PRIVATE_SCHEMA_MODE) ||
                (!Environment::usePrivateSchemaMode() && $individualControlSchemaMode == SchemaModes::PUBLIC_SCHEMA_MODE);
        }
        return array_filter(
            $entryList,
            function($entry) use($typeResolverClass, $fieldName, $enableIndividualControl, $individualControlSchemaMode, $matchNullControlEntry) {
                return
                    $entry[0] == $typeResolverClass &&
                    $entry[1] == $fieldName &&
                    (
                        !$enableIndividualControl ||
                        $entry[3] == $individualControlSchemaMode ||
                        (
                            is_null($entry[3]) &&
                            $matchNullControlEntry
                        )
                    );
            }
        );
    }

    abstract protected function getSchemaMode(): string;

    public function maybeFilterFieldName(bool $include, TypeResolverInterface $typeResolver, FieldResolverInterface $fieldResolver, string $fieldName): bool
    {
        if (Environment::enableIndividualControlForPublicPrivateSchemaMode()) {
            if (empty($this->getEntries($typeResolver, $fieldName))) {
                return $include;
            }
        }

        // Check if to remove the field
        return parent::maybeFilterFieldName($include, $typeResolver, $fieldResolver, $fieldName);
    }
}
