<?php
namespace PoP\MandatoryDirectivesByConfiguration\ConfigurationEntries;

use PoP\AccessControl\Environment;
use PoP\AccessControl\Schema\SchemaModes;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\DirectiveResolvers\DirectiveResolverInterface;

trait ConfigurableMandatoryDirectivesForDirectivesTrait
{
    /**
     * Configuration entries
     *
     * @return array
     */
    abstract protected function getConfigurationEntries(): array;

    /**
     * Configuration entries
     *
     * @return array
     */
    final protected function getEntries(): array
    {
        $entryList = $this->getConfigurationEntries();
        if ($requiredEntryValue = $this->getRequiredEntryValue()) {
            return $this->getMatchingEntries(
                $entryList,
                $requiredEntryValue
            );
        }
        return $entryList;
    }

    /**
     * The value in the 2nd element from the entry
     *
     * @return string
     */
    protected function getRequiredEntryValue(): ?string
    {
        return null;
    }

    /**
     * Remove directiveName "translate" if the user is not logged in
     *
     * @param boolean $include
     * @param TypeResolverInterface $typeResolver
     * @param string $directiveName
     * @return boolean
     */
    protected function getDirectiveResolverClasses(): array
    {
        // Obtain all entries for the current combination of typeResolver/fieldName
        return array_values(array_unique(array_map(
            function($entry) {
                return $entry[0];
            },
            $this->getEntries()
        )));
    }

    /**
     * Filter all the entries from the list which apply to the passed typeResolver and fieldName
     *
     * @param array $entryList
     * @param string|null $value
     * @return array
     */
    final protected function getMatchingEntries(array $entryList, ?string $value): array
    {
        if ($value) {
            /**
             * If enabling individual control over public/private schema modes, then we must also check
             * that this field has the required mode.
             * If the schema mode was not defined in the entry, then this field is valid if the default
             * schema mode is the same required one
             */
            $enableIndividualControl = $matchNullControlEntry = false;
            if (Environment::enableIndividualControlForPublicPrivateSchemaMode()) {
                $enableIndividualControl = true;
                $individualControlSchemaMode = $this->getSchemaMode();
                $matchNullControlEntry =
                    (Environment::usePrivateSchemaMode() && $individualControlSchemaMode == SchemaModes::PRIVATE_SCHEMA_MODE) ||
                    (!Environment::usePrivateSchemaMode() && $individualControlSchemaMode == SchemaModes::PUBLIC_SCHEMA_MODE);
            }
            return array_filter(
                $entryList,
                function($entry) use($value, $enableIndividualControl, $individualControlSchemaMode, $matchNullControlEntry) {
                    return $entry[1] == $value &&
                    (
                        !$enableIndividualControl ||
                        $entry[2] == $individualControlSchemaMode ||
                        (
                            is_null($entry[2]) &&
                            $matchNullControlEntry
                        )
                    );
                }
            );
        }
        return $entryList;
    }

    abstract protected function getSchemaMode(): string;

    public function maybeFilterDirectiveName(bool $include, TypeResolverInterface $typeResolver, DirectiveResolverInterface $directiveResolver, string $directiveName): bool
    {
        if (!Environment::enableIndividualControlForPublicPrivateSchemaMode()) {
            return parent::maybeFilterDirectiveName($include, $typeResolver, $directiveResolver, $directiveName);
        }

        /**
         * On the entries we will resolve either the class of the directive resolver, or any of its ancestors
         * If there is any entry for this directive resolver, after filtering, then enable it
         * Otherwise, exit by returning the original hook value
         */
        $ancestorDirectiveResolverClasses = [];
        $directiveResolverClass = get_class($directiveResolver);
        do {
            $ancestorDirectiveResolverClasses[] = $directiveResolverClass;
            $directiveResolverClass = get_parent_class($directiveResolverClass);
        } while ($directiveResolverClass != null);
        $entries = $this->getEntries();
        foreach ($entries as $entry) {
            if (in_array($entry[0], $ancestorDirectiveResolverClasses)) {
                return parent::maybeFilterDirectiveName($include, $typeResolver, $directiveResolver, $directiveName);
            }
        }
        return $include;
    }
}
