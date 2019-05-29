<?php namespace Ellimelon\Socket2Me\Socket;

use Ellimelon\Socket2Me\Socket\Client;
use Ellimelon\Socket2Me\Log;

class Server extends Socket
{
    private $blacklist = array();
    private $clients = array();
    private $whitelist = array();
    
    public function __construct($local_port)
    {    
        $this->setLocalPort($local_port);
        
        $socket = $this->createSocket();
        
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        if (socket_bind($socket, 0, $this->getLocalPort()) === false) {
            throw \RuntimeException("Failed to bind Socket");
        }
        
        if (socket_listen($socket) === false) {
            throw new \RuntimeException("Failed to listen to Socket");
        }
        
        parent::__construct($socket);
    }
    
    public function clientReceive($client_offset)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->receive();
    }
    
    public function clientResetReceived($client_offset)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->resetReceived();
    }
    
    public function clientSend($client_offset, $data)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->send($data);
    }
    
    public function getBlacklist()
    {
        return $this->blacklist;
    }
    
    public function clientGetReceived($client_offset)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->getReceived();
    }
    
    public function clientGetRemoteAddress($client_offset)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->getRemoteAddress();
    }
    
    public function clientGetRemotePort($client_offset)
    {
        $this->validateCurrentClientOffset($client_offset);
        return $this->clients[$client_offset]->getRemotePort();
    }
    
    public function getClientOffset($client_address, $client_port = null)
    {
        $this->validateAddress($client_address);
        if ($client_port !== null) {
            $this->validatePort($client_port);
        }
        
        $client_offsets = array();
        foreach ($this->clients as $client_offset => $client) {
            if ($client_address === $client->getRemoteAddress()) {
                if ($client_port === null) {
                    $client_offsets[] = $client_offset;
                } elseif ($client_port === $client->getRemotePort()) {
                    return $client_offset;
                }
            }
        }
        
        if (count($client_offsets) > 1) {
            throw new \RuntimeException("Client's offset could not be determined, multiple Clients using the same Address");
        }
        
        if (count($client_offsets) > 0) {
            return $client_offsets[0];
        }
        
        return null;
    }
    
    public function getClientOffsets()
    {
        return array_keys($this->clients);
    }
    
    public function getClientsCount()
    {
        return count($this->clients);
    }
    
    public function getWhitelist()
    {
        return $this->whitelist;
    }
    
    public function setBlacklist($blacklist)
    {
        $this->validateBlacklist($blacklist);
        $this->blacklist = $blacklist;
    }
    
    private function setClients($clients)
    {
        $this->validateClients($clients);
        $this->clients = $clients;
    }
    
    public function setNewClient()
    {
        $socket = array($this->getSocket());
        $write = null;
        $except = null;
        socket_select($socket, $write, $except, 0);
        
        // If there's a Client waiting to connect
        if (in_array($this->getSocket(), $socket)) {
            $client = new \Ellimelon\Socket2Me\Socket\Client(null, null, socket_accept($this->getSocket()));
            
            // If the Whitelist is in use, and the Client isn't on it, or if the Client is on the Blacklist
            if ((count($this->whitelist) > 0 && !in_array($client->getRemoteAddress(), $this->whitelist)) || in_array($client->getRemoteAddress(), $this->blacklist)) {
                return array('blocked' => array('address' => $client->getRemoteAddress(), 'port' => $client->getRemotePort()));
            }
            
            $clients = $this->clients;
            array_push($clients, $client);
            $this->setClients($clients);
            end($clients);
            return array('accepted' => key($clients));
        }
        
        return array();
    }
    
    public function setWhitelist($whitelist)
    {
        $this->validateWhitelist($whitelist);
        $this->whitelist = $whitelist;
    }
    
    public function validateBlacklist($blacklist)
    {
        if (!is_array($blacklist)) {
            throw new \InvalidArgumentException("Invalid Blacklist");
        }
        foreach ($blacklist as $black_client_offset => $black_client) {
            $this->validateAddress($black_client);
        }
    }
    
    public function validateClient($client)
    {
        if (!($client instanceof \Ellimelon\Socket2Me\Socket\Client)) {
            throw new \InvalidArgumentException("Invalid Client");
        }
    }
    
    public function validateClientOffset($clients_offset)
    {
        // Valid values for a Client's Offest are Whole Numbers
        if (!is_int($clients_offset) || $clients_offset < 0 || $clients_offset !== (int)round($clients_offset)) {
            throw new \InvalidArgumentException("Invalid Client Offset");
        }
    }
    
    public function validateClients($clients)
    {
        if (!is_array($clients))
        {
            throw new \InvalidArgumentException("Invalid Clients");
        }
        foreach ($clients as $clients_offset => $client) {
            $this->validateClientOffset($clients_offset);
            $this->validateClient($client);
        }
    }
    
    public function validateCurrentClientOffset($client_offset)
    {
        if(!array_key_exists($client_offset, $this->clients)) {
            throw new \InvalidArgumentException ("Invalid Client Offset");
        }
    }
    
    public function validateWhitelist($whitelist)
    {
        if (!is_array($whitelist)) {
            throw new \InvalidArgumentException("Invalid Whitelist");
        }
        foreach ($whitelist as $white_client_offset => $white_client) {
            $this->validateAddress($white_client);
        }
    }
}
?>