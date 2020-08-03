<?php

use Everyman\Neo4j\Client;
use GraphAware\Neo4j\Client\ClientBuilder;
use Illuminate\Database\Seeder;

class NeoSeeder extends Seeder
{
    /**
     * @var Neo4j PHP Client
     */
    protected $client = null;

    /**
     * Initialize the client
     */
    public function __construct()
    {
        $host = config('database.connections.neo4j.host');
        $port = config('database.connections.neo4j.port');
        $username = config('database.connections.neo4j.username');
        $password = config('database.connections.neo4j.password');
        // $this->client = new Client($host, intval($port));
        // $this->client->getTransport()->setAuth($username, $password);
        $this->client = ClientBuilder::create()
            ->addConnection('default', "http://$username:$password@$host:$port")
            ->build();
    }
}