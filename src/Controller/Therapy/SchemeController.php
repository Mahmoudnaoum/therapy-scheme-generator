<?php

namespace App\Controller\Therapy;

use Dompdf\Dompdf;
use Dompdf\Options;

use App\Entity\Therapy\Label;
use App\Service\LabelService;
use App\Entity\Therapy\Scheme;
use App\Service\SchemeService;
use App\Form\TherapySchemeType;
use App\Form\SchemeTemplateType;
use App\Form\EditTherapySchemeType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\Therapy\SchemeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SchemeController extends AbstractController
{
    const ITEMS_PER_PAGE = 5;

    protected EntityManagerInterface $entityManager;
    protected PaginatorInterface $paginator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ) {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/create', name: 'app_therapy_scheme_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $form = $this->createForm(TherapySchemeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $labels = $form->get('labels')->getData(); // * not mapped

            $formData = $form->getData();

            $scheme = new Scheme();
            $targets = json_decode($formData['targets'], true);
            $scheme->setTargets($targets);

            $comments = json_decode($formData['comments'], true);
            if ($comments === null) {
                $comments = [];
            }
            $scheme->setComments($comments);

            $scheme->setSuppress($formData['suppress']);
            $scheme->setExcerpt($formData['excerpt']);

            $scheme->setCreatedAt(new \DateTimeImmutable());
            $scheme->setUpdatedAt(new \DateTimeImmutable());
            $labelIDs = $labels->map(fn (Label $label) => $label->getId())->toArray();
            $scheme->setSelectedLabels($labelIDs);

            // * Store the scheme in the session
            $session = $request->getSession();
            $session->set('scheme', $scheme);
            return $this->redirectToRoute('app_therapy_scheme_saveAsTemplate');
        }

        return $this->render('therapy/scheme/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function validateRequestData(array $requestData): ?string
    {
        if (!isset($requestData['selectedLabels'])) {
            return 'Missing selectedLabels';
        }

        if (!isset($requestData['currentComments'])) {
            return 'Missing currentComments';
        }

        if (!isset($requestData['notCheckedCheckboxes'])) {
            return 'Missing notCheckedCheckboxes';
        }

        if (!isset($requestData['currentLanguage'])) {
            return 'Missing currentLanguage';
        }
        if (!in_array($requestData['currentLanguage'], ['en', 'de'])) {
            return 'Invalid currentLanguage';
        }

        if (!isset($requestData['suppress'])) {
            return 'Missing suppress';
        }
        if (!is_bool($requestData['suppress'])) {
            return 'Invalid suppress';
        }

        if (!isset($requestData['excerpt'])) {
            return 'Missing excerpt';
        }
        if (!is_bool($requestData['excerpt'])) {
            return 'Invalid excerpt';
        }

        return "";
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/generateForm', name: 'app_therapy_scheme_generateForm', methods: ['POST'])]
    public function generateForm(Request $request, SchemeService $schemeService): Response
    {
        // * Validation
        $requestData = json_decode($request->getContent(), true);
        $validationError = $this->validateRequestData($requestData);
        if (strlen($validationError) > 0) {
            return new JsonResponse(['error' => $validationError], 400);
        }

        $selectedLabels = $requestData['selectedLabels'];
        $currentLanguage = $requestData['currentLanguage'];
        $currentComments = $requestData['currentComments'];
        $notCheckedCheckboxes = $requestData['notCheckedCheckboxes'];
        $suppress = $requestData['suppress'];
        $excerpt = $requestData['excerpt'];

        $newTbody = $schemeService->generateTbody(
            $selectedLabels,
            $suppress,
            $currentComments,
            $notCheckedCheckboxes,
            $excerpt,
            $currentLanguage
        );

        return new JsonResponse($newTbody);
    }


    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/generateReport', name: 'app_therapy_scheme_generateReport', methods: ['POST'])]
    public function generateReport(Request $request, SchemeService $schemeService): JsonResponse
    {
        // * Validation
        $requestData = json_decode($request->getContent(), true);
        $validationError = $this->validateRequestData($requestData);
        if (strlen($validationError) > 0) {
            return new JsonResponse(['error' => $validationError], 400);
        }

        $selectedLabels = $requestData['selectedLabels'];
        $currentComments = $requestData['currentComments'];
        $notCheckedCheckboxes = $requestData['notCheckedCheckboxes'];
        $suppress = $requestData['suppress'];
        $excerpt = $requestData['excerpt'];


        $reportContent = $schemeService->generatePDFReport(
            $selectedLabels,
            $suppress,
            $currentComments,
            $notCheckedCheckboxes,
            $excerpt
        );

        $session = $request->getSession();
        $session->set('reportContent', $reportContent);
        $session->set('reportExcerpt', $excerpt);

        return new JsonResponse(['success' => true], 200);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/edit/{id}', name: 'app_therapy_scheme_edit')]
    public function edit(
        int $id,
        SchemeRepository $schemeRepository,
        Request $request,
        SchemeService $schemeService,
        LabelService $labelService
    ): Response {
        $currentLanguage = $request->getLocale();

        $editedScheme = $schemeRepository->find($id);

        $selectedLabels = $editedScheme->getSelectedLabels(); // IDs

        // * Going to be used in the form (Entity Type)
        $selectedLabelsEntities = $labelService->getLabelsByIds($selectedLabels);

        $currentComments = $editedScheme->getComments();
        $notCheckedCheckboxes = $editedScheme->getTargets();

        $suppress = $editedScheme->isSuppress();
        $excerpt = $editedScheme->isExcerpt();

        $oldTbody = $schemeService->generateTbody(
            $selectedLabels,
            $suppress,
            $currentComments,
            $notCheckedCheckboxes,
            $excerpt,
            $currentLanguage
        );

        $form = $this->createForm(EditTherapySchemeType::class, $editedScheme);
        $form->add('labels', EntityType::class, [
            'class' => Label::class,
            'choice_label' => 'shortName',
            'multiple' => true,
            'mapped' => false,
            'data' => $selectedLabelsEntities,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check if someone the user was able to submit the form with empty labels
            $labels = $form->get('labels')->getData();
            if (count($labels) === 0) {
                return $this->redirect($request->headers->get('referer'));
            }

            $updateCurrent = $form->get('updateCurrent')->getData();

            if ($updateCurrent) {
                $scheme = $editedScheme;
            } else {
                $scheme = new Scheme();
            }

            $formData = $form->getData();

            $targets = $formData->getTargets();
            $scheme->setTargets($targets);

            $comments = $formData->getComments();
            $scheme->setComments($comments);

            $suppress = $formData->isSuppress();
            $scheme->setSuppress($suppress);

            $excerpt = $formData->isExcerpt();
            $scheme->setExcerpt($excerpt);

            $scheme->setCreatedAt(new \DateTimeImmutable());
            $scheme->setUpdatedAt(new \DateTimeImmutable());
            $labelIDs = [];
            foreach ($labels as $label) {
                $labelIDs[] = $label->getId();
            }
            $scheme->setSelectedLabels($labelIDs);

            // * Store the scheme in the session
            $session = $request->getSession();
            $session->set('scheme', $scheme);
            return $this->redirectToRoute('app_therapy_scheme_saveAsTemplate');
        }

        return $this->render('therapy/scheme/edit.html.twig', [
            'scheme' => $editedScheme,
            'form' => $form->createView(),
            'oldTbody' => $oldTbody,
            'selectedLabelsEntities' => $selectedLabelsEntities,
            'reportExcerpt' => $excerpt,
            'reportSuppress' => $suppress,
            'selectedLabels' => $selectedLabels
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/save-as-template', name: 'app_therapy_scheme_saveAsTemplate', methods: ['GET', 'POST'])]
    public function saveAsTemplate(Request $request, SchemeRepository $schemeRepository): Response
    {
        $session = $request->getSession();
        $data = $session->get('scheme');
        if (!$data) {
            return $this->redirectToRoute('app_therapy_scheme_create');
        }
        $scheme = new Scheme();
        $scheme->setName('Date: ' . date(DATE_RSS));

        $isEditing = $data->getId() != null;
        $toBeUpdatedScheme = null;

        if ($isEditing) {
            $toBeUpdatedScheme = $schemeRepository->find($data->getId());
            $scheme->setName($toBeUpdatedScheme->getName());
        }

        $form = $this->createForm(SchemeTemplateType::class, $scheme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data->setName($scheme->getName());
            if ($isEditing) {
                // * Update existing scheme
                $toBeUpdatedScheme->setName($data->getName());
                $toBeUpdatedScheme->setSelectedLabels($data->getSelectedLabels());
                $toBeUpdatedScheme->setTargets($data->getTargets());
                $toBeUpdatedScheme->setComments($data->getComments());
                $toBeUpdatedScheme->setSuppress($data->isSuppress());
                $toBeUpdatedScheme->setExcerpt($data->isExcerpt());
                $toBeUpdatedScheme->setUpdatedAt(new \DateTimeImmutable());
            } else {
                $this->entityManager->persist($data);
            }
            $this->entityManager->flush();

            $session->set('scheme', null);
            return $this->redirectToRoute('app_therapy_scheme_templates_list');
        }

        return $this->render('therapy/scheme/save-as-template.html.twig', [
            'form' => $form->createView(),
            'isEditing' => $isEditing
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/generate/html', name: 'app_therapy_scheme_generate_html', methods: ['GET'])]
    public function generateHtml(Request $request): Response
    {
        $session = $request->getSession();
        $reportContent = $session->get('reportContent');
        $reportExcerpt = $session->get('reportExcerpt');
        if (!$reportContent) {
            return $this->redirectToRoute('app_therapy_scheme_create'); // todo changeName
        }
        $reportContent = "<tbody>$reportContent</tbody>";
        return $this->render('therapy/scheme/pdf-template.html.twig', [
            'reportContent' => $reportContent,
            'reportExcerpt' => $reportExcerpt,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/generate/pdf', name: 'app_therapy_scheme_generate_pdf', methods: ['POST'])]
    public function generatePdf(Request $request): Response
    {
        $session = $request->getSession();
        $reportContent = $session->get('reportContent');
        $reportExcerpt = $session->get('reportExcerpt');
        if (!$reportContent) {
            return $this->redirectToRoute('app_therapy_scheme_create');
        }
        $reportContent = "<tbody>$reportContent</tbody>";


        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('therapy/scheme/pdf-template.html.twig', [
            'reportContent' => $reportContent,
            'reportExcerpt' => $reportExcerpt,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("report-" . date('Y-m-d') . ".pdf", [
            "Attachment" => true
        ]);
        return new Response();
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/searchRedirector', name: 'app_therapy_scheme_search_redirector')]
    public function searchRedirector(Request $request): Response
    {
        $searchValue = $request->get('searchName_scheme');
        if ($searchValue !== null && strlen($searchValue) > 0) {
            return $this->redirectToRoute('app_therapy_scheme_search', ['searchValue' => $searchValue]);
        } else {
            return $this->redirect($request->headers->get('referer'));
        }
    }


    const NB_PER_PAGE = 5;

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/search?searchValue={searchValue}', name: 'app_therapy_scheme_search')]
    public function searchLabels(Request $request, SchemeRepository $schemeRepository, string $searchValue): Response
    {
        if ($searchValue !== null) {
            $templates = $schemeRepository->createQueryBuilder('t')
                ->where('t.name LIKE :searchValue')
                ->setParameter('searchValue', '%' . $searchValue . '%')
                ->getQuery()
                ->getResult();
        } else {
            $templates = $schemeRepository->findAll();
        }

        $pagination = $this->paginator->paginate(
            $templates,
            $request->query->getInt('page', 1),
            self::NB_PER_PAGE
        );

        return $this->render('therapy/scheme/searchResult.html.twig', [
            'templates' => $pagination,
            'searchValue' => $searchValue,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/templates/list', name: 'app_therapy_saved_templates', methods: ['GET', 'POST'])]
    public function loadTemplates(Request $request, SchemeRepository $schemeRepository): Response
    {
        $templates = $schemeRepository->findAll();

        $templates = $this->paginator->paginate(
            $templates,
            $request->query->getInt('page', 1),
            self::ITEMS_PER_PAGE
        );
        return $this->render('therapy/scheme/templates-list.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/therapy/scheme/delete/template/{templateId}', name: 'app_therapy_scheme_delete')]
    public function deleteTemplate(SchemeRepository $schemeRepository, $templateId): Response
    {
        $template = $schemeRepository->find($templateId);
        if ($template) {
            $this->entityManager->remove($template);
            $this->entityManager->flush();
        }
        return $this->redirectToRoute('app_therapy_saved_templates');
    }
}
