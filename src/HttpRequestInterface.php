<?php

namespace Dyndns;

interface HttpRequestInterface
{
    /**
     * @param array<int, string> $headers
     * @return array{code:int,body:string}
     */
    public function request($method, $url, array $headers = array(), $body = null);
}
