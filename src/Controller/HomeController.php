<?php

namespace App\Controller;

use App\Service\GeoIPService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, GeoIPService $geoIPService): Response
    {
        $client_ip = $request->getClientIp();
        $IpLookupEndpoint = $this->getParameter('ip_lookup_endpoint');

        $countryRecord = $geoIPService->lookupCountry($client_ip);
        $cityRecord = $geoIPService->lookupCity($client_ip);
        $asnRecord = $geoIPService->lookupASN($client_ip);

        $countryCode2 = $countryRecord->country->isoCode ?? null;
        $currency = $geoIPService->getCurrencyByIso2($countryCode2);
        return $this->render('home/index.html.twig', [
            'IpLookupEndpoint' => $IpLookupEndpoint,
            'client_ip' => $client_ip,
            'country' => $countryRecord->country->name ?? null,
            'country_code2' => strtolower($countryCode2),
            'currency_name' => $currency['name'] ?? null,
            'currency_symbol' => $currency['symbol'] ?? null,
            'region' => $cityRecord->subdivisions[0]->name ?? null,
            'city' => $cityRecord->city->name ?? null,
            'latitude' => $cityRecord->location->latitude ?? null,
            'longitude' => $cityRecord->location->longitude ?? null,
            'timezone' => $cityRecord->location->timeZone ?? null,
            'asn_organization' => $asnRecord->autonomousSystemOrganization ?? null,
            'is_eu' => isset($countryRecord?->country?->isInEuropeanUnion)
                ? ($countryRecord->country->isInEuropeanUnion ? 'Yes' : 'No')
                : '-',
        ]);
    }

}
