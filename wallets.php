<?php
namespace blockchain;

class Wallet{
    private $current_blockchain, $password;

    public $guid, $address, $label;

    public $balance, $total_received, $extend_public_key, $extend_private_key, $receive_address; // hd accounts stuff

    function __construct(BlockChain $current, array $data, $password){
        $this->current_blockchain = $current;

        $this->guid = $data['guid'];

        $this->address = $data['address'];

        $this->label = $data['label'] ?? null;
        $this->balance = $data['balance'] ?? null;
        $this->total_received = $data['total_received'] ?? null;

        $this->extend_public_key = $data['extendedPublicKey'] ?? null;
        $this->extend_private_key = $data['extendedPrivateKey'] ?? null;
        $this->receive_address = $data['receiveAddress'] ?? null;

        $this->password = $password;
    }

    public function make_payment($to, $amount, $fee, $fee_per_byte, $second_password = null, $from = null){
        //amount, fee and fee per byte are all in satoshi

        $response = $this->current_blockchain->do_request("/merchant/$this->guid/payment", array(
            'to' => $to,
            'amount' => $amount,
            'password' => $this->password,
            'second_password' => $second_password,
            'api_code' => $this->current_blockchain->api_code,
            'from' => $from,
            'fee' => $fee,
            'fee_per_byte' => $fee_per_byte
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return (array)$decoded_response;
    }

    public function get_balance(){
        $response = $this->current_blockchain->do_request("/merchant/$this->guid/balance", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return $decoded_response->balance; //satoshi
    }

    public function enable_hd(){
        $this->current_blockchain->do_request("/merchant/$this->guid/enableHD", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));
    }

    public function list_hd_accounts(){
        $response = $this->current_blockchain->do_request("/merchant/$this->guid/accounts", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = (array)json_decode($response);

        if(isset($decoded_response['error']))
            throw new BlockChainException($decoded_response['error']);

        $wallet_array = array();

        foreach($decoded_response as $single_wallet) {
            $single_wallet = (array)$single_wallet;

            array_push($wallet_array,
                new HDWallet($single_wallet));
        }

        return $wallet_array;
    }

    public function list_hd_pubs(){
        $response = $this->current_blockchain->do_request("/merchant/$this->guid/accounts/xpubs", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return (array)$decoded_response;
    }

    public function create_hd_account($label = null){
        if($label == null) $label = $this->label;

        $response = $this->current_blockchain->do_request("/merchant/$this->guid/accounts/create", array(
            'label' => $label,
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return new HDWallet((array)$decoded_response);
    }

    public function get_hd_account($pub_or_index){
        $response = $this->current_blockchain->do_request("/merchant/$this->guid/accounts/$pub_or_index", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return new HDWallet((array)$decoded_response);
    }

    public function archive_hd_account($pub_or_index, $archive = true){
        $arc = $archive ? 'archive' : 'unarchive';

        $response = $this->current_blockchain->do_request("/merchant/$this->guid/accounts/$pub_or_index/$arc", array(
            'password' => $this->password,
            'api_code' => $this->current_blockchain->api_code
        ));

        $decoded_response = json_decode($response);

        if(isset($decoded_response->error))
            throw new BlockChainException($decoded_response->error);

        return new HDWallet((array)$decoded_response); // for some reason the response is the wallet
    }
}

class HDWallet{
    public $label, $balance, $total_received, $extend_public_key, $extend_private_key, $receive_address, $archived;

    public $xpriv, $xpub;

    function __construct(array $data){
        $this->label = $data['label'] ?? null;
        $this->balance = $data['balance'] ?? null;
        $this->total_received = $data['total_received'] ?? null;

        $this->extend_public_key = $data['extendedPublicKey'] ?? null;
        $this->extend_private_key = $data['extendedPrivateKey'] ?? null;
        $this->receive_address = $data['receiveAddress'] ?? null;

        $this->archived = $data['archived'] ?? false;

        $this->xpriv = $data['xpriv'] ?? null;
        $this->xpub = $data['xpub'] ?? null;
    }
}