<?php

namespace App\Controller;

use App\Entity\User;
use Lcobucci\JWT\Token\Parser;
use App\Repository\UserRepository;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;

class ExternalWeatherApiController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private TokenStorageInterface $tokenStorage,
        private TagAwareCacheInterface $cache,
    )
    {
    }
    
    public function fetchWeatherApi(string $city): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather?q=' . $city . '&appid=' . $this->getParameter('app.weather_api') . '&units=metric' . '&lang=fr'
        );

        if ($response->getStatusCode() === 404) return [
            'error' => 'Ville non trouvée'
        ];

        return $response->toArray();
    }

    #[Route('/weather/{city}', name: 'weatherByCity', methods: ['GET'])]
    public function getWeatherByCity(string $city, Request $request): Response
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );
    
        $token = $extractor->extract($request);
        if (!$token) {
            // Gérer le cas où aucun token n'est présent dans l'en-tête d'autorisation
            return $this->json([
                'statut' => 'Utilisateur non authentifié',
                'error' => 'Pas de token présent dans l\'entête', 
            ], 401);
        }

        $parser = new Parser(new JoseEncoder());
        try {
            $jwt = $parser->parse($token);
        } catch (\Exception $e) {
            // Gérer le cas où le token est invalide
            return $this->json([
                'statut' => 'Utilisateur non authentifié',
                'error' => 'Token invalide. Veuillez fournir un token valide.',
            ], 401);
        }

        $username = $jwt->claims()->get('username');

        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        $idCache = "getWeatherByCity" . $city;

        $jsonWeather = $this->cache->get($idCache, function (ItemInterface $item) use ($city) {
            $item->tag('weatherCache');
            $item->expiresAfter(3600);

            $weather = $this->fetchWeatherApi($city);

            return $this->json([
                'weather' => $weather,
            ]);
        });

        return new JsonResponse(
            $jsonWeather, Response::HTTP_OK, [], true
        );
    }

    #[Route('/weather', name: 'weatherByCurrentUser', methods: ['GET'])]
    public function getWeatherByCurrentUser(User $user, Request $request): Response
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );
    
        $token = $extractor->extract($request);
        
        if (!$token) {
            // Gérer le cas où aucun token n'est présent dans l'en-tête d'autorisation
            return $this->json([
                'statut' => 'Utilisateur non authentifié',
                'error' => 'Pas de token présent dans l\'entête',
            ], 401);
        }

        $parser = new Parser(new JoseEncoder());
        try {
            $jwt = $parser->parse($token);
        } catch (\Exception $e) {
            // Gérer le cas où le token est invalide
            return $this->json([
                'statut' => 'Utilisateur non authentifié',
                'error' => 'Token invalide. Veuillez fournir un token valide.',
            ], 401);
        }
        $username = $jwt->claims()->get('username');

        $user = $this->userRepository->findOneBy(['username' => $username]);
        
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        $city = $user->getCity();
        $weather = $this->fetchWeatherApi($city);
        return $this->json(data: [
            'weather' => $weather,
        ]);
    }
}