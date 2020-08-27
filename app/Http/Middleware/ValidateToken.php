<?php

namespace App\Http\Middleware;

use GraphAware\Neo4j\Client\ClientBuilder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;

use Closure;

class ValidateToken
{
    /**
     * @var Neo4j PHP Client
     */
    protected $client = null;

    /**
     * Create a new middleware instance.
     */
    public function __construct()
    {
        $host = config('database.connections.neo4j.host');
        $port = config('database.connections.neo4j.port');
        $username = config('database.connections.neo4j.username');
        $password = config('database.connections.neo4j.password');

        $this->client = ClientBuilder::create()
            ->addConnection('default', "http://$username:$password@$host:$port")
            ->build();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $value = $request->bearerToken();
        if (empty($value)) {
            return response()->json([
                'success' => false,
                'error' => 'Token not found',
            ], 401);
        }

        $parser = new Parser();
        $token = $parser->parse($value);

        $now = time();
        $leeway = config('jwt.leeway');
        $data = new ValidationData($now, $leeway);
        $data->setIssuer(config('jwt.iss'));
        $data->setAudience(config('jwt.aud'));

        if (!$token->validate($data)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid token data',
            ], 401);
        }

        if ($token->isExpired()) {
            return response()->json([
                'success' => false,
                'error' => 'Token expired',
            ], 401);
        }

        $query = [
            'MATCH (u:User{ uuid: {uuid} })',
            'RETURN u',
        ];
        $result = $this->client->run(implode(' ', $query), [
            'uuid' => $token->getClaim('sub'),
        ]);

        if ($result->size() == 0) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
            ], 404);
        }

        $user = $result->getRecord()->get('u')->values();
        if (isset($user['password']))
            unset($user['password']);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
