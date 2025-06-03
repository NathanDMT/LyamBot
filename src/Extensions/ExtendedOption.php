<?php

namespace Extensions;

use Discord\Parts\Interactions\Command\Option;

class ExtendedOption extends Option
{
    public function setChoices(array $choices): self
    {
        $this->attributes['choices'] = $choices;
        return $this;
    }
}
