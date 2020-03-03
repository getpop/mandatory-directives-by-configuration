<?php
namespace PoP\MandatoryDirectivesByConfiguration\TypeResolverDecorators;

use PoP\MandatoryDirectivesByConfiguration\ConfigurationEntries\ConfigurableMandatoryDirectivesForDirectivesTrait;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;

trait ConfigurableMandatoryDirectivesForDirectivesTypeResolverDecoratorTrait
{
    use ConfigurableMandatoryDirectivesForDirectivesTrait;

    abstract protected function getMandatoryDirectives($entryValue = null): array;

    public function getMandatoryDirectivesForDirectives(TypeResolverInterface $typeResolver): array
    {
        $mandatoryDirectivesForDirectives = [];
        foreach ($this->getEntries() as $entry) {
            $directiveResolverClass = $entry[0];
            $entryValue = $entry[1]; // this might be any value (string, array, etc) or, if not defined, null
            $directiveName = $directiveResolverClass::getDirectiveName();
            $mandatoryDirectivesForDirectives[$directiveName] = $this->getMandatoryDirectives($entryValue);
        }
        return $mandatoryDirectivesForDirectives;
    }
}
