<?php
namespace PoP\MandatoryDirectivesByConfiguration\TypeResolverDecorators;

use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\MandatoryDirectivesByConfiguration\ConfigurationEntries\ConfigurableMandatoryDirectivesForFieldsTrait;

trait ConfigurableMandatoryDirectivesForFieldsTypeResolverDecoratorTrait
{
    use ConfigurableMandatoryDirectivesForFieldsTrait;

    public static function getClassesToAttachTo(): array
    {
        return array_map(
            function($entry) {
                // The tuple has format [typeResolverClass, fieldName] or [typeResolverClass, fieldName, $role] or [typeResolverClass, fieldName, $capability]
                // So, in position [0], will always be the $typeResolverClass
                return $entry[0];
            },
            static::getConfigurationEntries()
        );
    }

    abstract protected function getMandatoryDirectives($entryValue = null): array;

    public function getMandatoryDirectivesForFields(TypeResolverInterface $typeResolver): array
    {
        $mandatoryDirectivesForFields = [];
        // Obtain all capabilities allowed for the current combination of typeResolver/fieldName
        foreach ($this->getFieldNames() as $fieldName) {
            foreach ($this->getEntries($typeResolver, $fieldName) as $entry) {
                $entryValue = $entry[2];
                if ($this->removeFieldNameBasedOnMatchingEntryValue($entryValue)) {
                    $mandatoryDirectivesForFields[$fieldName] = $this->getMandatoryDirectives($entryValue);
                }
            }
        }
        return $mandatoryDirectivesForFields;
    }

    protected function removeFieldNameBasedOnMatchingEntryValue($entryValue = null): bool
    {
        return true;
    }
}
