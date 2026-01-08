<?php

namespace ControleOnline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/debug', name: 'app_debug', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function debug(Request $request): JsonResponse
    {
        $data = [
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
            'post' => $request->request->all(),
            'raw' => $request->getContent(),
            'server' => $request->server->all(),
        ];

        $dir = $this->getParameter('kernel.project_dir') . '/debug';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = $dir . '/debug_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return new JsonResponse(['status' => 'ok']);
    }
}
