<?php

namespace LinkORB\Schemata\Validators;

use Symfony\Component\Validator\Constraint;

class SQLIdentifier extends Constraint
{
    public $message = 'The string "{{ string }}" should be valid SQL Identifier';
}
