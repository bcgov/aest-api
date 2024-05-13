<?php

namespace App\Http\Middleware;

use App\Models\ServiceAccount;
use App\Models\User;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {

        $token = request()->bearerToken();
        if (is_null($token)) {
            return Response::json(['status' => false, 'error' => 'Missing token.'], 401);
        }
        $jwksUri = env('KEYCLOAK_CERT');
        $jwksJson = file_get_contents($jwksUri);
        $jwksData = json_decode($jwksJson, true);
        $matchingKey = null;
        foreach ($jwksData['keys'] as $key) {
            if (isset($key['use']) && $key['use'] === 'sig') {
                $matchingKey = $key;
                break;
            }
        }

        $wrappedPk = wordwrap($matchingKey['x5c'][0], 64, "\n", true);
        $pk = "-----BEGIN CERTIFICATE-----\n".$wrappedPk."\n-----END CERTIFICATE-----";

        try {
            $decoded = JWT::decode($token, new Key($pk, 'RS256'));
        } catch (ExpiredException $e) {
            return Response::json(['status' => false, 'error' => 'Token has expired.'], 401);
        } catch (\Exception $e) {
            return Response::json(['status' => false, 'error' => 'An error occurred: '.$e->getMessage()], 401);
        }

        if (is_null($decoded)) {
            return Response::json(['status' => false, 'error' => 'Invalid token.'], 401);
        } else {
            // Simple validation against env record
            if ($decoded->clientId === env('SERVICE_ACCOUNT')) {
                return $next($request);
            }

            // Validation against a user in our DB
            //            $user = ServiceAccount::where('client_id', $decoded->clientId)->first();
            //            if(!is_null($user)){
            //                if($user->active)
            //                    return $next($request);
            //            }

        }

        return Response::json(['status' => false, 'error' => 'Generic error.'], 401);
    }
}
