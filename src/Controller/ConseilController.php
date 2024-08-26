<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\UserRepository;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ConseilController extends AbstractController
{
    #[Route('/api/conseils', name: 'conseil', methods: ['GET'])]
    public function getConseilList(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        $conseilList = $conseilRepository->findAll();
        $jsonConseilList = $serializer->serialize($conseilList, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($jsonConseilList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseils/{id}', name: 'detailConseil', methods: ['GET'])]
    public function getDetailConseil(Conseil $conseil, SerializerInterface $serializer, ConseilRepository $conseilRepository): JsonResponse 
    {
        $jsonConseil = $serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($jsonConseil, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/conseils/{id}', name: 'deleteConseil', methods: ['DELETE'])]
    public function deleteConseil(Conseil $conseil, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($conseil);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/conseils', name:"createConseil", methods: ['POST'])]
    public function createConseil(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, ValidatorInterface $validator): JsonResponse 
    {
        $conseil = $serializer->deserialize($request->getContent(), Conseil::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($conseil);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();
        // Récupération de l'idUser. S'il n'est pas défini, alors on met -1 par défaut.
        $user = $content['user_id'] ?? -1;
        // On cherche l'auteur qui correspond et on l'assigne au conseil.
        // Si "find" ne trouve pas l'auteur, alors null sera retourné.
        $conseil->setUser($userRepository->find($user));

        $em->persist($conseil);
        $em->flush();

        $jsonConseil = $serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        
        $location = $urlGenerator->generate('detailConseil', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonConseil, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/conseils/{id}', name:"updateConseil", methods:['PUT'])]
    public function updateConseil(Request $request, SerializerInterface $serializer, Conseil $currentConseil, EntityManagerInterface $em, UserRepository $userRepository, ValidatorInterface $validator): JsonResponse 
    {
        $updateConseil = $serializer->deserialize($request->getContent(), 
            Conseil::class, 
            'json', 
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentConseil]);

            // On vérifie les erreurs
            $errors = $validator->validate($updateConseil);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
        
        $content = $request->toArray();
        $user = $content['user_id'] ?? -1;
        $updateConseil->setUser($userRepository->find($user));
       
        $em->persist($updateConseil);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
