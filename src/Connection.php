<?php namespace Ellimelon\Socket2Me;
class Connection
{
    
    /*
    The purpose of this class is to manage Server/Clients as the same system, you shouldn't be able to tell which you've created! Basically a Client is treated like a server...
    */
    
    private $client;
    private $ip;
    private $port;
    private $server;
    
    function __construct($port, $ip = null)
    {
        $this->ip = $ip;
        $this->port = $port;
        
        if ($this->ip === null) {            
            $this->server = new Socket\Server($this->port);
        } else{
            $this->client = new Socket\Client($this->ip, $this->port);
        }
    }
    
    public function clientReceive($client_offset = null)
    {
        if ($this->server !== null) {
            return $this->server->clientReceive($client_offset);
        }
        return $this->client->receive();
    }
    
    public function clientSend($client_offset = null, $data)
    {
        if ($this->server !== null) {
            return $this->server->clientSend($client_offset, $data);
        }
        return $this->client->send($data);
    }
    
    public function getBlacklist()
    {
        if ($this->server !== null) {
            return $this->server->getBlacklist();
        }
        return array();
    }
    
    public function getClientRemoteIP($client_offset = null)
    {
        if ($this->server !== null) {
            return $this->server->getClientRemoteIP($client_offset);
        }
        return $this->client->getRemoteIP();
    }
    
    public function getClientRemotePort($client_offset)
    {
        if ($this->server !== null) {
            return $this->server->getClientRemoteIP($client_offset);
        }
        return $this->client->getRemotePort();
    }
    
    public function getClientOffsets()
    {
        if ($this->server !== null) {
            return $this->server->getClientOffsets();
        }
        return array(0);
    }
    
    public function getClientsCounts()
    {
        if ($this->server !== null) {
            return $this->server->getClientsCount();
        }
        return 1;
    }
    
    public function getWhitelist()
    {
        if ($this->server !== null) {
            return $this->server->getWhitelist();
        }
        return array();
    }
    
    public function setBlacklist($blacklist)
    {
        if ($this->server !== null) {
            $this->server->setBlacklist();
        }
    }
}
?>