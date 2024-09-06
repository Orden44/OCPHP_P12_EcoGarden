<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class ExternalWeatherApiController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
        private TokenStorageInterface $tokenStorage,
    )
    {
    }
    
    public function fetchWeatherApi(string $city): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather?q=' . $city . '&appid=' . $this->getParameter('app.weather_api')
        );

        if ($response->getStatusCode() === 404) return [
            'error' => 'Ville non trouvée'
        ];

        return $response->toArray();
    }

    #[Route('/weather/{city}', name: 'weatherByCity', methods: ['GET'])]
    public function getWeatherByCity(string $city): Response
    {
        $weather = $this->fetchWeatherApi($city);

        return $this->json([
            'weather' => $weather,
        ]);
    }

    #[Route('/weather', name: 'weatherByCurrentUser', methods: ['GET'])]
    // #[IsGranted('ROLE_USER')]
    public function getWeatherByCurrentUser(User $user, Request $request): Response
    {
        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );
    
        $token = $extractor->extract($request);
        
        var_dump($token);

        if (!$token) {
            // Gérer le cas où aucun token n'est présent dans l'en-tête d'autorisation
            return $this->json([
                'error' => 'Pas de token présent dans l\'entête',
            ]);
        }

        $decodedToken = $this->jwtManager->decode($token);

        if (!isset($decodedToken['username'])) {
            return $this->json([
                'error' => 'Clé "username" manquante dans le token',
            ]);
        }
        
        $username = $decodedToken['username'];
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