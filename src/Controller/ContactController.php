<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/nous-contacter', name: 'contact')]
    public function index(RequestStack $request): Response
    {
    	$form = $this->createForm(ContactType::class);
    	$form->handleRequest($request->getCurrentRequest());

    	if ($form->isSubmitted() && $form->isValid()) {
    		$this->addFlash('notice', 'Merci de nous avoir contacté. Notre équipe va vous répondre dans les meilleurs délais');
		}

        return $this->render('contact/index.html.twig', [
        	'form' => $form->createView()
		]);
    }
}
