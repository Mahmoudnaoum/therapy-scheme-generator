<?php

namespace App\Controller\Therapy;

use App\Form\Therapy\StubType;
use App\Repository\Therapy\StubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StubController extends AbstractController
{
    const PAGINATION_PAGE = 50;

    protected EntityManagerInterface $entityManager;
    protected TranslatorInterface $translator;
    protected PaginatorInterface $paginator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->paginator = $paginator;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    #[Route('/{_locale<%app.supported_locales%>}/therapy/stubs', name: 'app_therapy_stubs_list')]
    public function index(Request $request, StubRepository $stubRepository): Response
    {
        $sortField = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'asc');   
        $showDeleted = $request->query->get('showDeleted', 0);;  
        $query = $stubRepository
        ->createQueryBuilder('stub')
        ->setFirstResult($request->query->getInt('page', 0))
        ->setMaxResults(self::PAGINATION_PAGE);
        
        $query->orderBy("stub.$sortField", $direction);

        if ($showDeleted != true) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->isNull('stub.isDeleted'),
                $query->expr()->eq('stub.isDeleted', 0)
            ));
        }

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/stub/list.html.twig', [
            'pagination' => $pagination,
            'sort' => $sortField,
            'direction' => $direction,
            'showDeleted' => $showDeleted
        ]);
    }
    


    #[Route('/{_locale<%app.supported_locales%>}/therapy/stubs/searchRedirector', name: 'app_therapy_stubs_search_redirector')]
    public function searchRedirector(Request $request): Response
    {
        $searchValue = $request->get('searchName_stub');
        $searchCriteria = $request->get('searchCriteria','all');
        $showDeleted = $request->get('showDeleted', 0);  

        $sortField = $request->get('sort','id');
        $direction = $request->get('direction', 'asc'); 
      
        if ($searchValue !== null && strlen($searchValue) > 0) {
            return $this->redirectToRoute('app_therapy_stubs_search', [
                'searchValue' => $searchValue ,
                'searchCriteria' => $searchCriteria,
                'showDeleted' => $showDeleted,
                'sort' => $sortField,
                'direction' => $direction,
            ]);
        } else {
            return $this->redirect($request->headers->get('referer'));
        }
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stubs/search?searchValue={searchValue}?searchCriteria={searchCriteria}?sort={sort}?direction={direction}', name: 'app_therapy_stubs_search')]
    public function searchLabels(Request $request, StubRepository $stubRepository, string $searchValue, string $searchCriteria): Response
    {

        // Access the selected criteria from the form data
        $searchCriteria = strtolower($searchCriteria);
        $showDeleted = $request->get('showDeleted'); 

        $sort = $request->get('sort');
        $direction = $request->get('direction'); 

        $query = $stubRepository
            ->createQueryBuilder('stub')
            ->setFirstResult($request->query->getInt('page', 0))
            ->setMaxResults(self::PAGINATION_PAGE);

            
        if ($searchValue !== null) {
            if ($searchCriteria === 'all') {
                // Search in all columns
                $query
                    ->where('stub.name LIKE :search OR stub.description LIKE :search OR stub.excerpt LIKE :search OR stub.background LIKE :search')
                    ->setParameter('search', '%' . $searchValue . '%');
            } else {
                // Search in a specific column based on the selected criteria
                $query
                    ->where('stub.' . $searchCriteria . ' LIKE :search')
                    ->setParameter('search', '%' . $searchValue . '%');
            }
        }
        
        if ($showDeleted != true) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->isNull('stub.isDeleted'),
                $query->expr()->eq('stub.isDeleted', 0)
            ));
        }
        
        $query->orderBy("stub.$sort", $direction);

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/stub/searchResult.html.twig', [
            'pagination' => $pagination,
            'searchValue' => $searchValue,
            'searchCriteria' => $searchCriteria,
            'sort' => $sort,
            'direction' => $direction,
            'showDeleted' => $showDeleted
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stub/new', name: 'app_therapy_stub_new')]
    public function newStub(Request $request, StubRepository $stubRepository): Response
    {
        $stubForm = $this->createForm(StubType::class);
        $stubForm->handleRequest($request);

        if ($stubForm->isSubmitted() && $stubForm->isValid()) {
            $data = $stubForm->getData();

            if ($data["description"] === null) {
                $data["description"] = "";
            }
            if ($data["excerpt"] === null) {
                $data["excerpt"] = "";
            }
            if ($data["background"] === null) {
                $data["background"] = "";
            }

            $submitAndNew = $stubForm->get('submitAndNew')->getData();

            $stubRepository->getNewStubObjectFromArray($data);

            $nextAction = $submitAndNew ? 'app_therapy_stub_new' : 'app_main';
            return $this->redirectToRoute($nextAction);
        }

        return $this->render('therapy/stub/index.html.twig', [
            'formTitle' => $this->translator->trans('app-new-therapy-stub-form-title'),
            'stubForm' => $stubForm->createView(),
            "status" => "add",
            'stub' => null,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stub/edit/{id<\d+>}', name: 'app_therapy_stub_edit')]
    public function editStub(Request $request, int $id, StubRepository $stubRepository): Response
    {
        $stub = $stubRepository->find($id);
        if ($stub === null) {
            return $this->redirectToRoute('app_therapy_stubs_list');
        }

        // * Getting the list of oldLabels
        $oldLabels = [];
        foreach ($stub->getLabels() as $old) {
            $oldLabels[] = $old;
        }

        $stubForm = $this->createForm(StubType::class, $stub);
        $stubForm->handleRequest($request);

        if ($stubForm->isSubmitted() && $stubForm->isValid()) {
            $data = $stubForm->getData();


            if ($data->getDescription() === null) {
                $data->setDescription("");
            }
            if ($data->getExcerpt() === null) {
                $data->setExcerpt("");
            }
            if ($data->getBackground() === null) {
                $data->setBackground("");
            }

            // * Getting the list of newLabels
            $newLabels = [];
            foreach ($data->getLabels() as $newL) {
                $newLabels[] = $newL;
            }

            // * Removing the old labels
            foreach ($oldLabels as $old) {
                $old->deleteStub($stub, $this->entityManager);
            }

            $this->entityManager->flush();


            // * Adding the new labels
            foreach ($newLabels as $new) {
                $new->addStub($stub);
            }

            $this->entityManager->flush();

            $submitAndNew = $stubForm->get('submitAndNew')->getData();
            $nextAction = $submitAndNew ? 'app_therapy_stub_new' : 'app_therapy_stubs_list';
            return $this->redirectToRoute($nextAction);

        }

        return $this->render('therapy/stub/index.html.twig', [
            'formTitle' => $this->translator->trans('app-edit-therapy-stub-form-title', [
                'stub_name' => $stub->getName()
            ]),
            'stubForm' => $stubForm->createView(),
            "status" => "edit",
            'stub' => $stub,
            'usedSchemes' => $stubRepository->findSchemesByStubId($id)
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stub/deleteUndelete/{id<\d+>}', name: 'app_therapy_stub_delete_undelete')]
    public function deleteUndeleteStub(int $id, StubRepository $stubRepository): Response
    {
        $stub = $stubRepository->find($id);
        if ($stub === null) {
            return $this->redirectToRoute('app_therapy_stubs_list');
        }

        $stub->setIsDeleted(!$stub->getIsDeleted());
        $this->entityManager->persist($stub);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_therapy_stubs_list');
    }
}
