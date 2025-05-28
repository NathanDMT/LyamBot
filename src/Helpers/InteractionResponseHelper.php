<?php

namespace Helpers;

use Discord\Parts\Interactions\Command\Interaction;

trait InteractionResponseHelper
{
    public function respondWithMessage(Interaction $interaction, string $message, bool $ephemeral = false)
    {
        $interaction->respond([
            'type' => 4,
            'data' => [
                'content' => $message,
                'flags' => $ephemeral ? 64 : 0,
            ]
        ]);
    }
}
