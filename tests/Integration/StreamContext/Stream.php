<?php
class Stream
{
    protected $baseuri;
    protected $version;
    protected $user;
    protected $password;

    public function __construct($baseuri, $version, $user, $password)
    {
        $this->baseuri = $baseuri;
        $this->version = $version;
        $this->user = $user;
        $this->password = $password;
    }

    public function postJson($endpoint, $json, $opts = [])
    {
        $url = $this->baseuri.$endpoint;

        $options = array_merge([
            'ignore_errors' => false,
            'method' => 'POST',
            'content' => $json,
            'header' => array(
                'Content-Type: application/json',
                'X-Experience-Api-Version: '.$this->version,
                'Authorization: Basic '.base64_encode($this->user.':'.$this->password),
            )
        ], $opts);

        $context = stream_context_create([
            'http' => $options
        ]);

        $fp = fopen($url, 'rb', false, $context);
        if (! $fp) {
            throw new \Exception('fopen(POST) failed with: '.print_r(error_get_last(), true));
        }

        $meta = stream_get_meta_data($fp);
        $content  = stream_get_contents($fp);
        fclose($fp);

        return [
            'options' => $options,
            'meta' => $meta,
            'content' => $content
        ];
    }

    public function getJson($endpoint, $opts = [])
    {
        $url = $this->baseuri.$endpoint;

        $options = array_merge([
            'ignore_errors' => false,
            'method' => 'GET',
            'header' => array(
                'Content-Type: application/json',
                'X-Experience-Api-Version: '.$this->version,
                'Authorization: Basic '.base64_encode($this->user.':'.$this->password),
            )
        ], $opts);

        $context = stream_context_create([
            'http' => $options
        ]);

        $fp = fopen($url, 'rb', false, $context);
        if (! $fp) {
            throw new \Exception('fopen(GET) failed with: '.print_r(error_get_last(), true));
        }

        $meta = stream_get_meta_data($fp);
        $content  = stream_get_contents($fp);
        fclose($fp);

        return [
            'options' => $options,
            'meta' => $meta,
            'content' => $content
        ];
    }
}
