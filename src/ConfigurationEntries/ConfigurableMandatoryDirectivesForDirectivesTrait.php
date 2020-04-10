<?php
namespace PoP\MandatoryDirectivesByConfiguration\ConfigurationEntries;

use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;

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
        $requiredEntryValue = $this->getRequiredEntryValue();
        return $this->getMatchingEntries(
            $entryList,
            $requiredEntryValue
        );
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
            return array_filter(
                $entryList,
                function($entry) use($value) {
                    return $entry[1] == $value;
                }
            );
        }
        return $entryList;
    }
}
