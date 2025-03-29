<?php

namespace DoctrineEncryptBundle\Demo\Symfony6xOrm3\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route(name="home", path="/")
     */
    public function index(\DoctrineEncryptBundle\Demo\Symfony6xOrm3\Repository\SecretRepository $secretRepository): Response
    {
        return $this->render('index.html.twig', ['secrets' => $secretRepository->findAll()]);
    }

    /**
     * @Route(name="create", path="/create")
     */
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if (!$request->query->has('name') || !$request->query->has('secret')) {
            return new Response('Please specify name and secret in url-query');
        }

        $secret = new \DoctrineEncryptBundle\Demo\Symfony6xOrm3\Entity\Secret();

        $secret
            ->setName($request->query->getAlnum('name'))
            ->setSecret($request->query->getAlnum('secret'));

        $em->persist($secret);
        $em->flush();

        return new Response(sprintf('OK - secret %s stored', $secret->getName()));
    }
}
