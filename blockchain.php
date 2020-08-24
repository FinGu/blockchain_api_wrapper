<?php
namespace blockchain;

use Exception;

class BlockChainException extends Exception{}

class BlockChain{
    public $service_url, $api_code;

    private $debug = FALSE;

    function __construct($service_url, $api_code = null){
        $this->service_url = $service_url; //https://github.com/blockchain/service-my-wallet-v3

        if($api_code != null) $this->api_code = $api_code;
    }

    public function create_wallet($password, $private_key = null, $label = null, $email = null){
        //private_key is used to import into wallet as first address (optional)
        //label is used to give to the first address generated in the wallet (optional)
        //email is used to associate with the newly created wallet (optional)

        $response = $this->do_request('/api/v2/create', array(
            'password' => $password,
            'api_code' => $this->api_code,
            'priv' => $private_key,
            'label' => $label,
            'email' => $email
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        if($this->debug && isset($decoded_response->warning))
            trigger_error($decoded_response->warning);

        return new Wallet($this, (array)$decoded_response, $password);
    }

    public function do_request($endpoint, array $post_data){
        $c_url = curl_init($this->service_url . $endpoint);

        curl_setopt($c_url, CURLOPT_POST, true);
        curl_setopt($c_url, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($c_url, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($c_url);

        if(curl_errno($c_url))
            throw new BlockChainException(curl_error($c_url));

        curl_close($c_url);

        return $response;
    }

}