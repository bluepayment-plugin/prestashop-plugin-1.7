<?php

namespace BlueMedia\OnlinePayments\HttpClient;

class CurtHttpClient
{
    public function post($url, array $headers = [], $data = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        return $this->send($ch);
    }

    private function send($ch)
    {
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0) {
            throw new \RuntimeException($error, $errno);
        }

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return new CurtHttpResponse(
            $status_code,
            $result
        );
    }
}
