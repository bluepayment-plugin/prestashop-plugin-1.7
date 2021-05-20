<?php

namespace BlueMedia\OnlinePayments\HttpClient;

class CurtHttpResponse
{
    private $code;
    private $body;

    public function __construct($code, $body)
    {
        $this->code = $code;
        $this->body = $body;
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    public function getBody()
    {
        return $this->body;
    }
}
