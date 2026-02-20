<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JBSNewMedia\DDMBundle\Trait\DDMEntityAccessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DDMDatatableFormHandler
{
    use DDMEntityAccessor;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
    ) {
    }

    /**
     * Handles form rendering (GET) and form submission (POST).
     *
     * Options:
     *   - translation_domain (string|null): translation domain for field labels
     *   - id (mixed): optional entity id for templates
     *   - auto_flush (bool): whether to call entityManager::flush() after persist (default: true)
     *
     * @param array<string, mixed> $options
     */
    public function handle(
        Request $request,
        DDM $ddm,
        ?object $entity = null,
        bool $preload = false,
        string $template = '',
        array $options = [],
    ): Response {
        if ('' === $template) {
            $template = $ddm->getFormTemplate() ?? '@DDM/vis/form.html.twig';
        }

        if (null !== $entity && !$preload) {
            foreach ($ddm->getFields() as $field) {
                if (!$field->isRenderInForm()) {
                    continue;
                }
                $value = $this->getEntityValue($entity, $field->getIdentifier());
                if (null !== $value) {
                    $field->setValueForm($field->prepareValue($value));
                }
            }
        }

        if ($request->isMethod('POST')) {
            return $this->handlePost($request, $ddm, $entity, $preload, $options);
        }

        return $this->renderForm($ddm, $template, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function handlePost(
        Request $request,
        DDM $ddm,
        ?object $entity,
        bool $preload,
        array $options,
    ): JsonResponse {
        $invalid = [];
        $valid = [];
        $translationDomain = isset($options['translation_domain']) && is_string($options['translation_domain'])
            ? $options['translation_domain']
            : null;

        $ddm->setEntity($entity);

        foreach ($ddm->getFields() as $field) {
            if (!$field->isRenderInForm()) {
                continue;
            }

            /** @var array<string, mixed> $requestData */
            $requestData = $request->request->all();
            $value = $requestData[$field->getIdentifier()] ?? null;
            $error = null;

            if (!$field->validate($value)) {
                $fieldError = $field->getError();
                if (null !== $fieldError) {
                    $error = $this->translator->trans(
                        $fieldError['message'],
                        $fieldError['parameters'] ?? [],
                        $fieldError['domain']
                    );
                }
            }

            if (null !== $error) {
                $invalid[$field->getIdentifier()] = $error;
            } else {
                $valid[] = $field->getIdentifier();
            }
        }

        if ([] !== $invalid) {
            return new JsonResponse([
                'success' => false,
                'invalid' => $invalid,
                'valid' => $valid,
            ]);
        }

        $isNew = false;
        if ($preload || null === $entity) {
            $entityClass = $ddm->getEntityClass();
            $entity = new $entityClass();
            $isNew = true;
        }

        /** @var array<string, mixed> $requestData */
        $requestData = $request->request->all();

        foreach ($ddm->getFields() as $field) {
            if (!$field->isRenderInForm()) {
                continue;
            }
            $value = $requestData[$field->getIdentifier()] ?? null;
            $this->setEntityValue($entity, $field->getIdentifier(), $field->finalizeValue($value));
        }

        if ($isNew) {
            $this->entityManager->persist($entity);
        }

        $autoFlush = isset($options['auto_flush']) ? (bool) $options['auto_flush'] : true;
        if ($autoFlush) {
            $this->entityManager->flush();
        }

        $messageKey = $isNew ? 'ddm.successCreate' : 'ddm.successUpdate';

        return new JsonResponse([
            'success' => true,
            'message' => $this->translator->trans($messageKey, [], 'datatable'),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderForm(DDM $ddm, string $template, array $options): Response
    {
        $fields = [];
        foreach ($ddm->getFields() as $field) {
            if (!$field->isRenderInForm()) {
                continue;
            }
            $fields[] = $field;
        }

        $renderParams = array_merge($options, [
            'fields' => $fields,
            'ddm' => $ddm,
            'options' => $options,
            'id' => $options['id'] ?? null,
        ]);

        return new Response($this->twig->render($template, $renderParams));
    }
}
