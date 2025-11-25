<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use function file_get_contents;

class AssetsController extends AbstractController
{
    public function __construct(private readonly Kernel $kernel)
    {
    }

    #[Route('/style.css', name: 'stylesheet')]
    public function stylesheet(AuthenticationUtils $authUtils): Response
    {
        $filePath = $this->kernel->getProjectDir().'/public/assets/style.css';
        $content = file_get_contents($filePath);

        return new Response($content, 200, ['Content-Type' => 'text/css']);
    }
}
