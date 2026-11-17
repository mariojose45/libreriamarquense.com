<?php

namespace LM\CyberSource;

class NewPurchaseRequired extends GatewayException
{
    private $confirmation;
    private $reference;

    public function __construct($message, $confirmation, $reference)
    {
        parent::__construct($message);
        $this->confirmation = $confirmation;
        $this->reference = $reference;
    }

    public function response()
    {
        return array('success' => false, 'requires_confirmation' => true, 'message' => $this->getMessage(),
            'purchase_confirmation' => $this->confirmation, 'previous_reference' => $this->reference);
    }
}
