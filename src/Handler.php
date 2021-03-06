<?php
namespace GitHubWebhook;

class Handler
{
    private $secret;
    private $remote;
    private $gitDir;
    private $data;
    private $event;
    private $delivery;
    private $gitOutput;

    public function __construct($secret, $gitDir, $remote = null)
    {
        $this->secret = $secret;
        $this->remote = $remote;
        $this->gitDir = $gitDir;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getDelivery()
    {
        return $this->delivery;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getGitDir()
    {
        return $this->gitDir;
    }

    public function getGitOutput()
    {
        return $this->gitOutput;
    }

    public function getRemote()
    {
        return $this->remote;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function handle()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $data = $this->getData();
            
        print shell_exec("/usr/bin/git --git-dir={$this->gitDir}/.git --work-tree={$this->gitDir} pull -f {$this->remote} 2>&1");
		
        return true;
    }

    public function validate()
    {
        $headers = $this->getallheaders();
        $payload = file_get_contents('php://input');

       	if (!$this->validateSignature($headers['X-Hub-Signature'], $payload)) {
            return false;
        }

        $this->data = json_decode($payload,true);
        
        // Only accept pushes to the master branch
        if(isset($this->data['ref']) && $this->data['ref'] !== 'refs/heads/master')
        	return false;

        if(isset($headers['X-Github-Event']))
        	$this->event = $headers['X-GitHub-Event'];
		
		if(isset($headers['X-GitHub-Delivery']))
			$this->delivery = $headers['X-GitHub-Delivery'];
			
        return true;        
    }

    protected function validateSignature($gitHubSignatureHeader, $payload)
    {
        list ($algo, $gitHubSignature) = explode("=", $gitHubSignatureHeader);
        $payloadHash = hash_hmac($algo, $payload, $this->secret);
        return ($payloadHash == $gitHubSignature);
    }
    
    protected function getallheaders() 
    { 
       
       $headers = ''; 
       foreach ($_SERVER as $name => $value) 
       { 
           if (substr($name, 0, 5) == 'HTTP_') 
           { 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } 
       }
        
       return $headers; 
    } 
}
