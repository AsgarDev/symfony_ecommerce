<?php

namespace App\Controller;

use App\Class\Cart;
use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
	private $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	#[Route('/commande', name: 'order')]
    public function index(RequestStack $request, Cart $cart): Response
    {
    	if (!$this->getUser()->getAddresses()->getValues()) {
    		$this->redirectToRoute('account_address_add');
		}

    	$form = $this->createForm(OrderType::class, null, [
    		'user' => $this->getUser()
		]);

    	$form->handleRequest($request->getCurrentRequest());

//    	if ($form->isSubmitted() && $form->isValid()) {
//
//		}

        return $this->render('order/index.html.twig', [
        	'form' => $form->createView(),
			'cart' => $cart->getFull()
		]);
    }

	#[Route('/commande/recapitulatif', name: 'order_recap', methods:'POST')]
	public function add(RequestStack $request, Cart $cart)
	{
		$form = $this->createForm(OrderType::class, null, [
			'user' => $this->getUser()
		]);

		$form->handleRequest($request->getCurrentRequest());

		if ($form->isSubmitted() && $form->isValid()) {
			$date = new \DateTimeImmutable();
			$carriers = $form->get('carriers')->getData();
			$delivery = $form->get('addresses')->getData();
			$delivery_content = $delivery->getFirstname().' '.$delivery->getLastname();
			$delivery_content .= '<br>'.$delivery->getPhone();
			if ($delivery->getCompany()) {
				$delivery_content .= '<br>'.$delivery->getCompany();
			}

			$delivery_content .= '<br>'.$delivery->getAddress();
			$delivery_content .= '<br>'.$delivery->getPostal().' '.$delivery->getCity();
			$delivery_content .= '<br>'.$delivery->getCountry();

			// Enregistrer ma commande Order()
			$order = new Order();
			$reference = $date->format('dmY').'-'.uniqid();
			$order->setReference($reference);
			$order->setUser($this->getUser());
			$order->setCreatedAt($date);
			$order->setCarrierName($carriers->getName());
			$order->setCarrierPrice($carriers->getPrice());
			$order->setDelivery($delivery_content);
			$order->setState(0);

			$this->entityManager->persist($order);

			// Enregistrer mes produits OrderDetails()
			foreach ($cart->getFull() as $product) {
				$orderDetails = new OrderDetails();
				$orderDetails->setMyOrder($order);
				$orderDetails->setProduct($product['product']->getName());
				$orderDetails->setQuantity($product['quantity']);
				$orderDetails->setPrice($product['product']->getPrice());
				$orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);

				$this->entityManager->persist($orderDetails);
			}

			$this->entityManager->flush();

			return $this->render('order/add.html.twig', [
				'cart' => $cart->getFull(),
				'carrier' => $carriers,
				'delivery' => $delivery_content,
				'reference' => $order->getReference()
			]);
		}

		return $this->redirectToRoute('cart');
	}
}
