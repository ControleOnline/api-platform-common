<?php

namespace ControleOnline\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class QuotePeople extends Constraint
{
    public $message = 'Parameter {{ string }}';
}
