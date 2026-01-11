<?php

namespace App\Controller;

use App\Service\GeoIPService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Exception\GeoIp2Exception;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class ApiController extends AbstractController
{

    #[Route('/api/v1/lookup', name: 'api_lookup_self', methods: ['GET'])]
    #[OA\Tag(name: 'IP Lookup')]
    #[OA\Get(
        path: '/api/v1/lookup',
        description: 'Returns geolocation information based on the caller IP address. No parameters needed.',
        summary: 'Lookup your own IP address',
    )]
    public function lookupSelf(
        Request $request,
        GeoIPService $geoIPService,
        \Symfony\Component\RateLimiter\RateLimiterFactory $anonymousApiLimiter
    ): JsonResponse {
        $clientIp = $request->getClientIp();

        if (!$clientIp) {
            return $this->json([
                'success' => false,
                'code' => 400,
                'message' => 'Unable to detect client IP.'
            ], 400);
        }

        // Call the main lookup method using the client's IP
        return $this->lookup($clientIp, $geoIPService, $anonymousApiLimiter, $request);
    }

    #[Route('/api/v1/lookup/{query}', name: 'api_lookup', requirements: ['query' => '.+'], methods: ['GET'])]
    #[OA\Tag(name: 'IP Lookup')]
    #[OA\Parameter(
        name: 'query',
        description: 'IP address or domain name to lookup',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '8.8.8.8')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful lookup',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'code', type: 'integer', example: 200),
                new OA\Property(property: 'query', type: 'string', example: '8.8.8.8'),
                new OA\Property(property: 'message', type: 'string', example: 'IP lookup successful'),
                new OA\Property(property: 'country', type: 'string', example: 'United States'),
                new OA\Property(property: 'country_code2', type: 'string', example: 'US'),
                new OA\Property(property: 'city', type: 'string', example: 'Mountain View'),
                new OA\Property(property: 'timezone', type: 'string', example: 'America/Los_Angeles'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Unable to detect client IP',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'code', type: 'integer', example: 400),
                new OA\Property(property: 'query', type: 'string', example: ''),
                new OA\Property(property: 'message', type: 'string', example: 'Unable to detect client IP.'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'IP not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'code', type: 'integer', example: 404),
                new OA\Property(property: 'query', type: 'string', example: ''),
                new OA\Property(property: 'message', type: 'string', example: 'IP not found in database.'),
            ]
        )
    )]
    #[OA\Response(
        response: 429,
        description: 'Too many requests',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'code', type: 'integer', example: 429),
                new OA\Property(property: 'query', type: 'string', example: ''),
                new OA\Property(property: 'message', type: 'string', example: 'Too many requests. Please try again later.'),
            ]
        )
    )]
    public function lookup(string $query, GeoIPService $geoIPService, \Symfony\Component\RateLimiter\RateLimiterFactory $anonymousApiLimiter, Request $request): JsonResponse
    {
        // Rate Limiting
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException();
        }

        // Determine if input is an IP or a domain
        if (filter_var($query, FILTER_VALIDATE_IP)) {
            $ip = $query;
        } else {
            // Resolve domain to IP
            $ip = gethostbyname($query);
            if ($ip === $query) {
                return $this->json([
                    'success' => false,
                    'code' => 404,
                    'query' => $query,
                    'message' => 'IP not found in database.'
                ], 404);
            }
        }

        try {
            $countryRecord = $geoIPService->lookupCountry($ip);
            $cityRecord = $geoIPService->lookupCity($ip);
            $asnRecord = $geoIPService->lookupASN($ip);

            // If nothing found in MaxMind DB
            if (!$countryRecord && !$cityRecord) {
                return $this->json([
                    'success' => false,
                    'code' => 404,
                    'query' => $ip,
                    'message' => 'IP not found in database.'
                ], 404);
            }

            $countryCode2 = $countryRecord->country->isoCode ?? null;
            $countryCode3 = $geoIPService->getCountryIso3ByIso2($countryCode2);
            $currency = $geoIPService->getCurrencyByIso2($countryCode2);

            $response = [
                'success' => true,
                'code' => 200,
                'query' => $countryRecord->traits->ipAddress ?? $ip,
                'message' => 'IP lookup successful',
                'continent_name' => $countryRecord->continent->name ?? null,
                'continent_code' => $countryRecord->continent->code ?? null,
                'country' => $countryRecord->country->name ?? null,
                'country_code2' => $countryCode2,
                'country_code3' => $countryCode3,
                'currency' => $currency,
                'network' => $countryRecord->traits->network ?? null,
                'organization' => $countryRecord->traits->organization ?? null,
                'is_eu' => $countryRecord->country->isInEuropeanUnion ? 'Yes' : 'No',
            ];

            if ($cityRecord) {
                $response['city'] = $cityRecord->city->name ?? null;
                $response['region'] = $cityRecord->subdivisions[0]->name ?? null;
                $response['subdivisions'] = array_map(fn($sub) => $sub->name, $cityRecord->subdivisions);
                $response['postal_code'] = $cityRecord->postal->code ?? null;
                $response['latitude'] = $cityRecord->location->latitude;
                $response['longitude'] = $cityRecord->location->longitude;
                $response['timezone'] = $cityRecord->location->timeZone;
            }

            if ($asnRecord) {
                $response['asn'] = $asnRecord->autonomousSystemNumber ?? null;
                $response['asn_organization'] = $asnRecord->autonomousSystemOrganization ?? null;
            }

            return $this->json($response);

        } catch (AddressNotFoundException $e) {
            // IP not in MaxMind DB
            return $this->json([
                'success' => false,
                'code' => 404,
                'query' => $ip,
                'message' => 'IP not found in database.'
            ], 404);
        } catch (GeoIp2Exception $e) {
            // GeoIP library error
            return $this->json([
                'success' => false,
                'code' => 500,
                'query' => $ip,
                'message' => 'GeoIP lookup failed. Please try again later.'
            ], 500);
        } catch (\Exception $e) {
            // Catch-all unexpected errors
            // Optionally log: $this->logger->error($e->getMessage());
            return $this->json([
                'success' => false,
                'code' => 500,
                'query' => $ip,
                'message' => 'Unexpected error occurred. Please try again later.'
            ], 500);
        }
    }
}
