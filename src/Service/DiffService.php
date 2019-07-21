<?php

namespace Schemata\Service;

use Schemata\Entity\Schema;
use Pitpit\Component\Diff\Diff;
use Pitpit\Component\Diff\DiffEngine;

class DiffService
{
    public function calculateDiff(Schema $schemaOne, Schema $schemaTwo): array
    {
        $engine = new DiffEngine();
        $diff = $engine->compare($schemaOne, $schemaTwo);
        $res = [];

        $trace = static function (Diff $diff, $tab = '') use (&$trace, &$res) {

            foreach ($diff as $element) {
                if ($element->isModified()) {
                    $c = $element->isTypeChanged() ? 'T' : 'M';
                } else if ($element->isCreated()) {
                    $c = $element->isTypeChanged() ? 'T' : '+';
                } else if ($element->isDeleted()) {
                    $c = $element->isTypeChanged() ? 'T' : '-';
                } else {
                    $c = $element->isTypeChanged() ? 'T' : '=';
                }

                if ('=' !== $c) {
                    $res[] = sprintf(
                        '%s* %s (%s)',
                        $tab,
                        $element->getIdentifier(),
                        $c
                    );
                }

                if ($diff->isModified()) {
                    $trace($element, $tab . '  ');
                }
            }
        };

        $trace($diff);

        return $res;
    }
}
