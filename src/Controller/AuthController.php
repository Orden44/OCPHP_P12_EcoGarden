<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
    public function auth(User $user, SerializerInterface $serializer, Request $request, UserRepository $userRepository): Response
    {
        $user = $serializer->deserialize($request->getContent(), User::class,"json");

        $user = $userRepository->findOneBy(['username' => $user->getUsername(), 'password' => $user->getPassword()]);
        // var_dump($user);

        if (!$user) {
            // Si l'utilisateur n'est pas trouvé dans la base de données, renvoyer une réponse d'erreur
            return new JsonResponse(['message' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
        }
        
        // Générer un token JWT valide
        $token = $this->jwtEncoder->encode([
            'username' => $user->getUsername(),
            'exp' => time() + 3600, // expiration du token (ici 1 heure)
        ]);

        // Retourner le token JWT encodé en JSON
        return $this->json(['token' => $token]);
    }
}
