<?php

/** The engine's contract, as a bridge in remote mode reads it (`GET /openapi.json`): the document alone. */
function contract(): array
{
    static $document = null;

    return $document ??= OpenSim\Api\Contract::document();
}
