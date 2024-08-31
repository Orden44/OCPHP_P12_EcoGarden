<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class AuthController extends AbstractController
{
    private $jwtEncoder;

    public function __construct(JWTEncoderInterface $jwtEncoder)
    {
        $this->jwtEncoder = $jwtEncoder;
    }

    #[Route('/auth', name: 'app_auth', methods: ['POST'])]
    public function auth(SerializerInterface $serializer, Request $request, UserRepository $userRepository): Response 
    {
        $userRequest = $serializer->deserialize($request->getContent(), User::class, "json");
    
        $storedUser = $userRepository->findOneBy(['username' => $userRequest->getUsername()]);

        if (!$storedUser || !password_verify($userRequest->getPassword(), $storedUser->getPassword())) {
            return new JsonResponse(['message' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
        }

        // Générer un token JWT valide
        $token = $this->jwtEncoder->encode([
            'username' => $storedUser->getUsername(),
            'exp' => time() + 7200,
        ]);
    
        return $this->json([
            'token' => $token,
            'Utilisateur connecté' => $storedUser->getUsername().' de la ville de '.$storedUser->getCity(),
        ]);
    }
}
