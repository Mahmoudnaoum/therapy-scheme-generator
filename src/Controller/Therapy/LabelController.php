<?php

namespace App\Controller\Therapy;

use App\Entity\Therapy\Label;
use App\Entity\Therapy\Stub;
use App\Form\Therapy\LabelType;
use App\Repository\Therapy\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LabelController extends AbstractController
{
    const PAGINATION_PAGE = 5;

    protected EntityManagerInterface $entityManager;
    protected PaginatorInterface $paginator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/labels', name: 'app_therapy_labels_list')]
    public function listLabels(Request $request, LabelRepository $labelRepository): Response
    {
        $query = $labelRepository
            ->createQueryBuilder('label')
            ->setFirstResult($request->query->getInt('page', 0))
            ->setMaxResults(self::PAGINATION_PAGE);

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/label/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/label/searchRedirector', name: 'app_therapy_labels_search_redirector')]
    public function searchRedirector(Request $request): Response
    {
        $searchValue = $request->get('label_name');
        if ($searchValue !== null && strlen($searchValue) > 0) {
            return $this->redirectToRoute('app_therapy_labels_search', ['searchValue' => $searchValue]);
        } else {
            return $this->redirect($request->headers->get('referer'));
        }
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/labels/search?searchValue={searchValue}', name: 'app_therapy_labels_search')]
    public function searchLabels(Request $request, LabelRepository $labelRepository, string $searchValue): Response
    {
        $query = $labelRepository
            ->createQueryBuilder('label')
            ->setFirstResult($request->query->getInt('page', 0))
            ->setMaxResults(self::PAGINATION_PAGE);

        if ($searchValue !== null) {
            $query
                ->where('label.reportName LIKE :search')
                ->orWhere('label.shortName LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/label/searchResult.html.twig', [
            'pagination' => $pagination,
            'searchValue' => $searchValue,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/label/{id<\d+>}', name: 'app_therapy_label_edit')]
    public function editLabel(Request $request, int $id): Response
    {
        $label = $this->entityManager->getRepository(Label::class)->find($id);
        
        if (!$label) {
            // TODO throw exception
        }

        $stubs =  $label->getStubs();

        $labelForm = $this->createForm(LabelType::class, $label);
        $labelForm->handleRequest($request);
        
        if ($labelForm->isSubmitted() && $labelForm->isValid()) {
            $data = $labelForm->getData();
            
            $label->setShortName($data->getShortName());
            $label->setReportName($data->getReportName());
            
            $this->entityManager->persist($label);
            $this->entityManager->flush();
            
            return $this->redirectToRoute('app_therapy_labels_list');
        }
        
        return $this->render('therapy/label/edit.html.twig', [
            'labelForm' => $labelForm->createView(),
            'currentId' => $id,
            'stubs' => $stubs
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/delete/label/{id<\d+>}', name: 'app_therapy_label_delete')]
    public function deleteLabel(Request $request, int $id): Response
    {
        $label = $this->entityManager->getRepository(Label::class)->find($id);

        if ($label) {
            $this->entityManager->remove($label);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => 1,
                'redirect' => $this->generateUrl('app_therapy_labels_list'),
            ]);
        }

        return new JsonResponse([
            'success' => 0,
            'message' => 'Label not exists'
        ]);
    }
}
