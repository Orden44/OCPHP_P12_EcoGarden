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
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        // $jsonConseilList = $serializer->serialize($conseilList, 'json', ['groups' => 'getConseils'], ['groups' => 'getUsers']);
        $jsonConseilList = $this->serializeConseil($conseilList, $serializer);
        return new JsonResponse($jsonConseilList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/conseils/currentMonth', name: 'conseil_current_month', methods: ['GET'])]
    public function getConseilCurrentMonth(ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // Obtenir le mois actuel
        $currentMonth = (int)date('m');  

        // Récupérer les conseils du mois en cours
        $conseilCurrentMonth = $conseilRepository->findByMonth($currentMonth);

        $jsonConseilList = $serializer->serialize($conseilCurrentMonth, 'json', ['groups' => 'getConseils']);
        // $jsonConseilList = $this->serializeConseil($conseilCurrentMonth, $serializer);
        return new JsonResponse($jsonConseilList, Response::HTTP_OK, [], true);
    }


    #[Route('/api/conseils/month/{month}', name: 'conseil_month', methods: ['GET'])]
    public function getConseilsByMonth($month, ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // Vérifier si le mois est un chiffre valide (1-12)
        if (!is_numeric($month) || $month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Mois invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Filtrer les conseils par mois
        $conseilsMonth = $conseilRepository->findByMonth($month);

        $jsonConseilList = $serializer->serialize($conseilsMonth, 'json', ['groups' => 'getConseils']);
        return new JsonResponse($jsonConseilList, Response::HTTP_OK, [], true);
    }

    public function serializeConseil($conseil, SerializerInterface $serializer)
    {
        // $json = $serializer->serialize($conseil, 'json', [
        //     'groups' => 'getConseils',
        //     'ignored_attributes' => ['user'],
        //     'userId' => $conseil->getUser()->getId()           
        // ]);

        $data = [
            'id' => $conseil->getId(),
            'title' => $conseil->getTitle(),
            'description' => $conseil->getDescription(),
            'createdate' => $conseil->getCreatedate(),
            'updatedate' => $conseil->getUpdatedate(),
            'userId' => $conseil->getUser()->getId(),
        ];
        
        $json = $serializer->serialize($data, 'json');
    
        return $json;
    }

    #[Route('/api/conseils/detail/{id}', name: 'detailConseil', methods: ['GET'])]
    public function getDetailConseil(Conseil $conseil, SerializerInterface $serializer): JsonResponse 
    {
        // $jsonConseil = $serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        $jsonConseil = $this->serializeConseil($conseil, $serializer);

        return new JsonResponse($jsonConseil, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/conseils/user/{userId}', name: 'getConseilsByUserId', methods: ['GET'])]
    public function getConseilsByUserId($userId, ConseilRepository $conseilRepository, SerializerInterface $serializer): JsonResponse
    {
        // Récupérer les conseils de l'utilisateur
        $conseils = $conseilRepository->findBy(['user' => $userId]);
        
        // Serializer les conseils
        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => 'getConseils']);

        return new JsonResponse($jsonConseils, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/conseils/{id}', name: 'deleteConseil', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function deleteConseil(Conseil $conseil, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($conseil);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/conseils/{userId}', name:"createConseil", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function createConseil(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, ValidatorInterface $validator, $userId): JsonResponse 
    {
        $conseil = $serializer->deserialize($request->getContent(), Conseil::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($conseil);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // // Récupération de l'ensemble des données envoyées sous forme de tableau
        // $content = $request->toArray();
        // // Récupération de l'idUser. S'il n'est pas défini, alors on met -1 par défaut.
        // $user = $content['user_id'] ?? -1;
        // // On cherche l'auteur qui correspond et on l'assigne au conseil.
        // // Si "find" ne trouve pas l'auteur, alors null sera retourné.

        $conseil->setUser($userRepository->find($userId));

        $em->persist($conseil);
        $em->flush();

        $jsonConseil = $serializer->serialize($conseil, 'json', ['groups' => 'getConseils']);
        
        $location = $urlGenerator->generate('detailConseil', ['id' => $conseil->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonConseil, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/conseils/{id}', name:"updateConseil", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function updateConseil(Request $request, SerializerInterface $serializer, Conseil $currentConseil, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse 
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
        
        // $content = $request->toArray();
        // $user = $content['user_id'] ?? -1;
        $updateConseil->setUser($this->getUser());
        $updateConseil->setUpdatedate(new \DateTime());
        // $updateConseil->setUser($userRepository->find($user));
       
        $em->persist($updateConseil);
        $em->flush();
        // return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

        // $jsonConseil = $serializer->serialize($updateConseil, 'json', ['groups' => 'getConseils']);
        $jsonConseil = $this->serializeConseil($updateConseil, $serializer);
        return new JsonResponse($jsonConseil, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
