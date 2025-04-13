<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\WordPressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class WordpressController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private WordPressService $wordPressService) {}
    #[Route('/wordpress', name: "api_wordpress", methods: ["GET"])]
    public function getDownload(Request $request): JsonResponse
    {
        $request->get();
        return new JsonResponse($this->wordPressService->getAllPosts($domain, $input));
    }
}
