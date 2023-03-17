<?php

namespace App\Controller\Therapy;


use App\Entity\Therapy\Stub;
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
    const PAGINATION_PAGE = 5;
    
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

        $query = $stubRepository
            ->createQueryBuilder('stub')
            ->setFirstResult($request->query->getInt('page', 0))
            ->setMaxResults(self::PAGINATION_PAGE);

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/stub/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }


    #[Route('/{_locale<%app.supported_locales%>}/therapy/stubs/searchRedirector', name: 'app_therapy_stubs_search_redirector')]
    public function searchRedirector(Request $request): Response
    {
        $searchValue = $request->get('searchName_stub');
        if ($searchValue !== null && strlen($searchValue) > 0) {
            return $this->redirectToRoute('app_therapy_stubs_search', ['searchValue' => $searchValue]);
        } else {
            return $this->redirect($request->headers->get('referer'));
        }
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stubs/search?searchValue={searchValue}', name: 'app_therapy_stubs_search')]
    public function searchLabels(Request $request, StubRepository $stubRepository, string $searchValue): Response
    {

        $query = $stubRepository
            ->createQueryBuilder('stub')
            ->setFirstResult($request->query->getInt('page', 0))
            ->setMaxResults(self::PAGINATION_PAGE);

        if ($searchValue !== null) {
            $query
                ->where('stub.name LIKE :search')
                ->orWhere('stub.description LIKE :search')
                ->orWhere('stub.excerpt LIKE :search')
                ->orWhere('stub.background LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            self::PAGINATION_PAGE
        );

        return $this->render('therapy/stub/searchResult.html.twig', [
            'pagination' => $pagination,
            'searchValue' => $searchValue,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stub/new', name: 'app_therapy_stub_new')]
    public function newStub(Request $request): Response
    {
        $stubForm = $this->createForm(StubType::class);
        $stubForm->handleRequest($request);

        if ($stubForm->isSubmitted() && $stubForm->isValid()) {
            $data = $stubForm->getData();

            $stub = $this->entityManager
                ->getRepository(Stub::class)
                ->getNewStubObjectFromArray($data);

            $nextAction = $stubForm->get('saveAndNew')->isClicked()
                ? 'app_therapy_stub_new'
                : 'app_main';

            return $this->redirectToRoute($nextAction);
        }

        return $this->render('therapy/stub/index.html.twig', [
            'formTitle' => $this->translator->trans('app-new-therapy-stub-form-title'),
            'stubForm' => $stubForm->createView(),
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/stub/edit/{id<\d+>}', name: 'app_therapy_stub_edit')]
    public function editStub(Request $request, int $id): Response
    {
        $repository = $this->entityManager->getRepository(Stub::class);
        $stub = $repository->find($id);

        if (!$stub) {
            // TODO exception about stub not exists
        }

        $stubForm = $this->createForm(StubType::class, $repository->getStubObjectFromEntity($stub));
        $stubForm->handleRequest($request);

        if ($stubForm->isSubmitted() && $stubForm->isValid()) {
            $data = $stubForm->getData();

            $repository->updateEntityFromDto($stub, $data);
        }

        return $this->render('therapy/stub/index.html.twig', [
            'formTitle' => $this->translator->trans('app-edit-therapy-stub-form-title', [
                'stub_name' => $stub->getName()
            ]),
            'stubForm' => $stubForm->createView(),
        ]);
    }
}
