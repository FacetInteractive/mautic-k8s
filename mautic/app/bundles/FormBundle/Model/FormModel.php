<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use DOMDocument;
use Mautic\CoreBundle\Doctrine\Helper\SchemaHelperFactory;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\FormFieldHelper as ContactFieldHelper;
use Mautic\LeadBundle\Model\FieldModel as LeadFieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class FormModel.
 */
class FormModel extends CommonFormModel
{
    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var TemplatingHelper
     */
    protected $templatingHelper;

    /**
     * @var ThemeHelper
     */
    protected $themeHelper;

    /**
     * @var SchemaHelperFactory
     */
    protected $schemaHelperFactory;

    /**
     * @var ActionModel
     */
    protected $formActionModel;

    /**
     * @var FieldModel
     */
    protected $formFieldModel;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var FormFieldHelper
     */
    protected $fieldHelper;

    /**
     * @var LeadFieldModel
     */
    protected $leadFieldModel;

    /**
     * @var FormUploader
     */
    private $formUploader;

    /**
     * FormModel constructor.
     *
     * @param RequestStack        $requestStack
     * @param TemplatingHelper    $templatingHelper
     * @param ThemeHelper         $themeHelper
     * @param SchemaHelperFactory $schemaHelperFactory
     * @param ActionModel         $formActionModel
     * @param FieldModel          $formFieldModel
     * @param LeadModel           $leadModel
     * @param FormFieldHelper     $fieldHelper
     * @param LeadFieldModel      $leadFieldModel
     * @param FormUploader        $formUploader
     */
    public function __construct(
        RequestStack $requestStack,
        TemplatingHelper $templatingHelper,
        ThemeHelper $themeHelper,
        SchemaHelperFactory $schemaHelperFactory,
        ActionModel $formActionModel,
        FieldModel $formFieldModel,
        LeadModel $leadModel,
        FormFieldHelper $fieldHelper,
        LeadFieldModel $leadFieldModel,
        FormUploader $formUploader
    ) {
        $this->request             = $requestStack->getCurrentRequest();
        $this->templatingHelper    = $templatingHelper;
        $this->themeHelper         = $themeHelper;
        $this->schemaHelperFactory = $schemaHelperFactory;
        $this->formActionModel     = $formActionModel;
        $this->formFieldModel      = $formFieldModel;
        $this->leadModel           = $leadModel;
        $this->fieldHelper         = $fieldHelper;
        $this->leadFieldModel      = $leadFieldModel;
        $this->formUploader        = $formUploader;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\FormBundle\Entity\FormRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Form');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'form:forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter()
    {
        return 'getName';
    }

    /**
     * {@inheritdoc}
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create('mauticform', $entity, $options);
    }

    /**
     * @param null $id
     *
     * @return Form
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Form();
        }

        $entity = parent::getEntity($id);

        if ($entity && $entity->getFields()) {
            foreach ($entity->getFields() as $field) {
                $this->addLeadFieldOptions($field);
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|FormEvent|void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Form) {
            throw new MethodNotAllowedHttpException(['Form']);
        }

        switch ($action) {
            case 'pre_save':
                $name = FormEvents::FORM_PRE_SAVE;
                break;
            case 'post_save':
                $name = FormEvents::FORM_POST_SAVE;
                break;
            case 'pre_delete':
                $name = FormEvents::FORM_PRE_DELETE;
                break;
            case 'post_delete':
                $name = FormEvents::FORM_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new FormEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Form $entity
     * @param      $sessionFields
     */
    public function setFields(Form $entity, $sessionFields)
    {
        $order          = 1;
        $existingFields = $entity->getFields()->toArray();
        $formName       = $entity->generateFormName();
        foreach ($sessionFields as $key => $properties) {
            $isNew = (!empty($properties['id']) && isset($existingFields[$properties['id']])) ? false : true;
            $field = !$isNew ? $existingFields[$properties['id']] : new Field();

            if (!$isNew) {
                if (empty($properties['alias'])) {
                    $properties['alias'] = $field->getAlias();
                }
                if (empty($properties['label'])) {
                    $properties['label'] = $field->getLabel();
                }
            }

            if ($formName === $properties['alias']) {
                // Change the alias to prevent potential ID collisions in the rendered HTML
                $properties['alias'] = 'f_'.$properties['alias'];
            }

            foreach ($properties as $f => $v) {
                if (in_array($f, ['id', 'order'])) {
                    continue;
                }

                $func = 'set'.ucfirst($f);
                if (method_exists($field, $func)) {
                    $field->$func($v);
                }
            }
            $field->setForm($entity);
            $field->setSessionId($key);
            $field->setOrder($order);
            ++$order;
            $entity->addField($properties['id'], $field);
        }

        // Persist if the entity is known
        if ($entity->getId()) {
            $this->formFieldModel->saveEntities($existingFields);
        }
    }

    /**
     * @param Form $entity
     * @param      $sessionFields
     */
    public function deleteFields(Form $entity, $sessionFields)
    {
        if (empty($sessionFields)) {
            return;
        }

        $existingFields = $entity->getFields()->toArray();
        $deleteFields   = [];
        foreach ($sessionFields as $fieldId) {
            if (!isset($existingFields[$fieldId])) {
                continue;
            }
            $this->handleFilesDelete($existingFields[$fieldId]);
            $entity->removeField($fieldId, $existingFields[$fieldId]);
            $deleteFields[] = $fieldId;
        }

        // Delete fields from db
        if (count($deleteFields)) {
            $this->formFieldModel->deleteEntities($deleteFields);
        }
    }

    private function handleFilesDelete(Field $field)
    {
        if (!$field->isFileType()) {
            return;
        }

        $this->formUploader->deleteAllFilesOfFormField($field);
    }

    /**
     * @param Form $entity
     * @param      $sessionActions
     */
    public function setActions(Form $entity, $sessionActions)
    {
        $order           = 1;
        $existingActions = $entity->getActions()->toArray();
        $savedFields     = $entity->getFields()->toArray();

        //match sessionId with field Id to update mapped fields
        $fieldIds = [];
        foreach ($savedFields as $id => $field) {
            $fieldIds[$field->getSessionId()] = $field->getId();
        }

        foreach ($sessionActions as $properties) {
            $isNew  = (!empty($properties['id']) && isset($existingActions[$properties['id']])) ? false : true;
            $action = !$isNew ? $existingActions[$properties['id']] : new Action();

            foreach ($properties as $f => $v) {
                if (in_array($f, ['id', 'order'])) {
                    continue;
                }

                $func = 'set'.ucfirst($f);

                if ($f == 'properties') {
                    if (isset($v['mappedFields'])) {
                        foreach ($v['mappedFields'] as $pk => $pv) {
                            if (strpos($pv, 'new') !== false) {
                                $v['mappedFields'][$pk] = $fieldIds[$pv];
                            }
                        }
                    }
                }

                if (method_exists($action, $func)) {
                    $action->$func($v);
                }
            }
            $action->setForm($entity);
            $action->setOrder($order);
            ++$order;
            $entity->addAction($properties['id'], $action);
        }

        // Persist if form is being edited
        if ($entity->getId()) {
            $this->formActionModel->saveEntities($existingActions);
        }
    }

    /**
     * @param Form  $entity
     * @param array $actions
     */
    public function deleteActions(Form $entity, $actions)
    {
        if (empty($actions)) {
            return;
        }

        $existingActions = $entity->getActions()->toArray();
        $deleteActions   = [];
        foreach ($actions as $actionId) {
            if (isset($existingActions[$actionId])) {
                $actionEntity = $this->em->getReference('MauticFormBundle:Action', (int) $actionId);
                $entity->removeAction($actionEntity);
                $deleteActions[] = $actionId;
            }
        }

        // Delete actions from db
        if (count($deleteActions)) {
            $this->formActionModel->deleteEntities($deleteActions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveEntity($entity, $unlock = true)
    {
        $isNew = ($entity->getId()) ? false : true;

        if ($isNew && !$entity->getAlias()) {
            $alias = $this->cleanAlias($entity->getName(), '', 10);
            $entity->setAlias($alias);
        }

        //save the form so that the ID is available for the form html
        parent::saveEntity($entity, $unlock);

        //now build the form table
        if ($entity->getId()) {
            $this->createTableSchema($entity, $isNew);
        }

        $this->generateHtml($entity);
    }

    /**
     * Obtains the content.
     *
     * @param Form      $form
     * @param bool|true $withScript
     * @param bool|true $useCache
     *
     * @return string
     */
    public function getContent(Form $form, $withScript = true, $useCache = true)
    {
        $html = $this->getFormHtml($form, $useCache);

        if ($withScript) {
            $html = $this->getFormScript($form)."\n\n".$this->removeScriptTag($html);
        } else {
            $html = $this->removeScriptTag($html);
        }

        return $html;
    }

    /**
     * Obtains the cached HTML of a form and generates it if missing.
     *
     * @param Form      $form
     * @param bool|true $useCache
     *
     * @return string
     */
    public function getFormHtml(Form $form, $useCache = true)
    {
        if ($useCache && !$form->usesProgressiveProfiling()) {
            $cachedHtml = $form->getCachedHtml();
        }

        if (empty($cachedHtml)) {
            $cachedHtml = $this->generateHtml($form, $useCache);
        }

        if (!$form->getInKioskMode()) {
            $this->populateValuesWithLead($form, $cachedHtml);
        }

        return $cachedHtml;
    }

    /**
     * Get results for a form and lead.
     *
     * @param Form $form
     * @param int  $leadId
     * @param int  $limit
     *
     * @return array
     */
    public function getLeadSubmissions(Form $form, $leadId, $limit = 200)
    {
        return $this->getRepository()->getFormResults(
            $form,
            [
                'leadId' => $leadId,
                'limit'  => $limit,
            ]
        );
    }

    /**
     * Generate the form's html.
     *
     * @param Form $entity
     * @param bool $persist
     *
     * @return string
     */
    public function generateHtml(Form $entity, $persist = true)
    {
        //generate cached HTML
        $theme       = $entity->getTemplate();
        $submissions = null;
        $lead        = ($this->request) ? $this->leadModel->getCurrentLead() : null;
        $style       = '';

        if (!empty($theme)) {
            $theme .= '|';
        }

        if ($lead instanceof Lead && $lead->getId() && $entity->usesProgressiveProfiling()) {
            $submissions = $this->getLeadSubmissions($entity, $lead->getId());
        }

        if ($entity->getRenderStyle()) {
            $templating = $this->templatingHelper->getTemplating();
            $styleTheme = $theme.'MauticFormBundle:Builder:style.html.php';
            $style      = $templating->render($this->themeHelper->checkForTwigTemplate($styleTheme));
        }

        // Determine pages
        $fields = $entity->getFields()->toArray();

        // Ensure the correct order in case this is generated right after a form save with new fields
        uasort($fields, function ($a, $b) {
            if ($a->getOrder() === $b->getOrder()) {
                return 0;
            }

            return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
        });

        $pages = ['open' => [], 'close' => []];

        $openFieldId  =
        $closeFieldId =
        $previousId   =
        $lastPage     = false;
        $pageCount    = 1;

        foreach ($fields as $fieldId => $field) {
            if ('pagebreak' == $field->getType() && $openFieldId) {
                // Open the page
                $pages['open'][$openFieldId] = $pageCount;
                $openFieldId                 = false;
                $lastPage                    = $fieldId;

                // Close the page at the next page break
                if ($previousId) {
                    $pages['close'][$previousId] = $pageCount;

                    ++$pageCount;
                }
            } else {
                if (!$openFieldId) {
                    $openFieldId = $fieldId;
                }
            }

            $previousId = $fieldId;
        }

        if (!empty($pages)) {
            if ($openFieldId) {
                $pages['open'][$openFieldId] = $pageCount;
            }
            if ($previousId !== $lastPage) {
                $pages['close'][$previousId] = $pageCount;
            }
        }

        $html = $this->templatingHelper->getTemplating()->render(
            $theme.'MauticFormBundle:Builder:form.html.php',
            [
                'fieldSettings' => $this->getCustomComponents()['fields'],
                'fields'        => $fields,
                'contactFields' => $this->leadFieldModel->getFieldListWithProperties(),
                'companyFields' => $this->leadFieldModel->getFieldListWithProperties('company'),
                'form'          => $entity,
                'theme'         => $theme,
                'submissions'   => $submissions,
                'lead'          => $lead,
                'formPages'     => $pages,
                'lastFormPage'  => $lastPage,
                'style'         => $style,
                'inBuilder'     => false,
            ]
        );

        if (!$entity->usesProgressiveProfiling()) {
            $entity->setCachedHtml($html);

            if ($persist) {
                //bypass model function as events aren't needed for this
                $this->getRepository()->saveEntity($entity);
            }
        }

        return $html;
    }

    /**
     * Creates the table structure for form results.
     *
     * @param Form $entity
     * @param bool $isNew
     * @param bool $dropExisting
     */
    public function createTableSchema(Form $entity, $isNew = false, $dropExisting = false)
    {
        //create the field as its own column in the leads table
        $schemaHelper = $this->schemaHelperFactory->getSchemaHelper('table');
        $name         = 'form_results_'.$entity->getId().'_'.$entity->getAlias();
        $columns      = $this->generateFieldColumns($entity);
        if ($isNew || (!$isNew && !$schemaHelper->checkTableExists($name))) {
            $schemaHelper->addTable([
                'name'    => $name,
                'columns' => $columns,
                'options' => [
                    'primaryKey'  => ['submission_id'],
                    'uniqueIndex' => ['submission_id', 'form_id'],
                ],
            ], true, $dropExisting);
            $schemaHelper->executeChanges();
        } else {
            //check to make sure columns exist
            $schemaHelper = $this->schemaHelperFactory->getSchemaHelper('column', $name);
            foreach ($columns as $c) {
                if (!$schemaHelper->checkColumnExists($c['name'])) {
                    $schemaHelper->addColumn($c, false);
                }
            }
            $schemaHelper->executeChanges();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEntity($entity)
    {
        /* @var Form $entity */
        $this->deleteFormFiles($entity);

        if (!$entity->getId()) {
            //delete the associated results table
            $schemaHelper = $this->schemaHelperFactory->getSchemaHelper('table');
            $schemaHelper->deleteTable('form_results_'.$entity->deletedId.'_'.$entity->getAlias());
            $schemaHelper->executeChanges();
        }
        parent::deleteEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEntities($ids)
    {
        $entities     = parent::deleteEntities($ids);
        $schemaHelper = $this->schemaHelperFactory->getSchemaHelper('table');
        foreach ($entities as $id => $entity) {
            /* @var Form $entity */
            //delete the associated results table
            $schemaHelper->deleteTable('form_results_'.$id.'_'.$entity->getAlias());
            $this->deleteFormFiles($entity);
        }
        $schemaHelper->executeChanges();

        return $entities;
    }

    private function deleteFormFiles(Form $form)
    {
        $this->formUploader->deleteFilesOfForm($form);
    }

    /**
     * Generate an array of columns from fields.
     *
     * @param Form $form
     *
     * @return array
     */
    public function generateFieldColumns(Form $form)
    {
        $fields = $form->getFields()->toArray();

        $columns = [
            [
                'name' => 'submission_id',
                'type' => 'integer',
            ],
            [
                'name' => 'form_id',
                'type' => 'integer',
            ],
        ];
        $ignoreTypes = $this->getCustomComponents()['viewOnlyFields'];
        foreach ($fields as $f) {
            if (!in_array($f->getType(), $ignoreTypes) && $f->getSaveResult() !== false) {
                $columns[] = [
                    'name'    => $f->getAlias(),
                    'type'    => 'text',
                    'options' => [
                        'notnull' => false,
                    ],
                ];
            }
        }

        return $columns;
    }

    /**
     * Gets array of custom fields and submit actions from bundles subscribed FormEvents::FORM_ON_BUILD.
     *
     * @return mixed
     */
    public function getCustomComponents()
    {
        static $customComponents;

        if (empty($customComponents)) {
            //build them
            $event = new FormBuilderEvent($this->translator);
            $this->dispatcher->dispatch(FormEvents::FORM_ON_BUILD, $event);
            $customComponents['fields']     = $event->getFormFields();
            $customComponents['actions']    = $event->getSubmitActions();
            $customComponents['choices']    = $event->getSubmitActionGroups();
            $customComponents['validators'] = $event->getValidators();

            // Generate a list of fields that are not persisted to the database by default
            $notPersist = ['button', 'captcha', 'freetext', 'freehtml', 'pagebreak'];
            foreach ($customComponents['fields'] as $type => $field) {
                if (isset($field['builderOptions']) && isset($field['builderOptions']['addSaveResult']) && false === $field['builderOptions']['addSaveResult']) {
                    $notPersist[] = $type;
                }
            }
            $customComponents['viewOnlyFields'] = $notPersist;
        }

        return $customComponents;
    }

    /**
     * Get the document write javascript for the form.
     *
     * @param Form $form
     *
     * @return string
     */
    public function getAutomaticJavascript(Form $form)
    {
        $html       = $this->getContent($form, false);
        $formScript = $this->getFormScript($form);

        //replace line breaks with literal symbol and escape quotations
        $search        = ["\r\n", "\n", '"'];
        $replace       = ['', '', '\"'];
        $html          = str_replace($search, $replace, $html);
        $oldFormScript = str_replace($search, $replace, $formScript);
        $newFormScript = $this->generateJsScript($formScript);

        // Write html for all browser and fallback for IE
        $script = '
            var scr  = document.currentScript;
            var html = "'.$html.'";
            
            if (scr !== undefined) {
                scr.insertAdjacentHTML("afterend", html);
                '.$newFormScript.'
            } else {
                document.write("'.$oldFormScript.'"+html);
            }
        ';

        return $script;
    }

    /**
     * @param Form $form
     *
     * @return string
     */
    public function getFormScript(Form $form)
    {
        $theme = $form->getTemplate();

        if (!empty($theme)) {
            $theme .= '|';
        }

        $script = $this->templatingHelper->getTemplating()->render(
            $theme.'MauticFormBundle:Builder:script.html.php',
            [
                'form'  => $form,
                'theme' => $theme,
            ]
        );

        $html    = $this->getFormHtml($form);
        $scripts = $this->extractScriptTag($html);

        foreach ($scripts as $item) {
            $script .= $item."\n";
        }

        return $script;
    }

    /**
     * Writes in form values from get parameters.
     *
     * @param $form
     * @param $formHtml
     */
    public function populateValuesWithGetParameters(Form $form, &$formHtml)
    {
        $formName = $form->generateFormName();

        $fields = $form->getFields()->toArray();
        /** @var \Mautic\FormBundle\Entity\Field $f */
        foreach ($fields as $f) {
            $alias = $f->getAlias();
            if ($this->request->query->has($alias)) {
                $value = $this->request->query->get($alias);

                $this->fieldHelper->populateField($f, $value, $formName, $formHtml);
            }
        }
    }

    /**
     * @param Form $form
     * @param      $formHtml
     */
    public function populateValuesWithLead(Form $form, &$formHtml)
    {
        $formName       = $form->generateFormName();
        $fields         = $form->getFields();
        $autoFillFields = [];

        /** @var \Mautic\FormBundle\Entity\Field $field */
        foreach ($fields as $key => $field) {
            $leadField  = $field->getLeadField();
            $isAutoFill = $field->getIsAutoFill();

            // we want work just with matched autofill fields
            if (isset($leadField) && $isAutoFill) {
                $autoFillFields[$key] = $field;
            }
        }

        // no fields for populate
        if (!count($autoFillFields)) {
            return;
        }

        $lead = $this->leadModel->getCurrentLead();
        if (!$lead instanceof Lead) {
            return;
        }

        foreach ($autoFillFields as $field) {
            $value = $lead->getFieldValue($field->getLeadField());
            // just skip string empty field
            if ($value !== '') {
                $this->fieldHelper->populateField($field, $value, $formName, $formHtml);
            }
        }
    }

    /**
     * @param null $operator
     *
     * @return array
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOptions = [
            '=' => [
                'label'       => 'mautic.lead.list.form.operator.equals',
                'expr'        => 'eq',
                'negate_expr' => 'neq',
            ],
            '!=' => [
                'label'       => 'mautic.lead.list.form.operator.notequals',
                'expr'        => 'neq',
                'negate_expr' => 'eq',
            ],
            'gt' => [
                'label'       => 'mautic.lead.list.form.operator.greaterthan',
                'expr'        => 'gt',
                'negate_expr' => 'lt',
            ],
            'gte' => [
                'label'       => 'mautic.lead.list.form.operator.greaterthanequals',
                'expr'        => 'gte',
                'negate_expr' => 'lt',
            ],
            'lt' => [
                'label'       => 'mautic.lead.list.form.operator.lessthan',
                'expr'        => 'lt',
                'negate_expr' => 'gt',
            ],
            'lte' => [
                'label'       => 'mautic.lead.list.form.operator.lessthanequals',
                'expr'        => 'lte',
                'negate_expr' => 'gt',
            ],
            'like' => [
                'label'       => 'mautic.lead.list.form.operator.islike',
                'expr'        => 'like',
                'negate_expr' => 'notLike',
            ],
            '!like' => [
                'label'       => 'mautic.lead.list.form.operator.isnotlike',
                'expr'        => 'notLike',
                'negate_expr' => 'like',
            ],
            'startsWith' => [
                'label'       => 'mautic.core.operator.starts.with',
                'expr'        => 'startsWith',
                'negate_expr' => 'startsWith',
            ],
            'endsWith' => [
                'label'       => 'mautic.core.operator.ends.with',
                'expr'        => 'endsWith',
                'negate_expr' => 'endsWith',
            ],
            'contains' => [
                'label'       => 'mautic.core.operator.contains',
                'expr'        => 'contains',
                'negate_expr' => 'contains',
            ],
        ];

        return ($operator === null) ? $operatorOptions : $operatorOptions[$operator];
    }

    /**
     * Get a list of assets in a date range.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param array     $options
     *
     * @return array
     */
    public function getFormList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $options = [])
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'forms', 't')
            ->setMaxResults($limit);

        if (!empty($options['canViewOthers'])) {
            $q->andWhere('t.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_added');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Load HTML consider Libxml < 2.7.8.
     *
     * @param $html
     */
    private function loadHTML(&$dom, $html)
    {
        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } else {
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        }
    }

    /**
     * Save HTML consider Libxml < 2.7.8.
     *
     * @param $html
     *
     * @return string
     */
    private function saveHTML($dom, $html)
    {
        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            return $dom->saveHTML($html);
        } else {
            // remove DOCTYPE, <html>, and <body> tags for old libxml
            return preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(['<html>', '</html>', '<body>', '</body>'], ['', '', '', ''], $dom->saveHTML($html)));
        }
    }

    /**
     * Extract script from html.
     *
     * @param $html
     *
     * @return array
     */
    private function extractScriptTag($html)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $this->loadHTML($dom, $html);
        $items = $dom->getElementsByTagName('script');

        $scripts = [];
        foreach ($items as $script) {
            $scripts[] = $this->saveHTML($dom, $script);
        }

        return $scripts;
    }

    /**
     * Remove script from html.
     *
     * @param $html
     *
     * @return string
     */
    private function removeScriptTag($html)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $this->loadHTML($dom, '<div>'.$html.'</div>');
        $items = $dom->getElementsByTagName('script');

        $remove = [];
        foreach ($items as $item) {
            $remove[] = $item;
        }

        foreach ($remove as $item) {
            $item->parentNode->removeChild($item);
        }

        $root   = $dom->documentElement;
        $result = '';
        foreach ($root->childNodes as $childNode) {
            $result .= $this->saveHTML($dom, $childNode);
        }

        return $result;
    }

    /**
     * Generate dom manipulation javascript to include all script.
     *
     * @param $html
     *
     * @return string
     */
    private function generateJsScript($html)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $this->loadHTML($dom, '<div>'.$html.'</div>');
        $items = $dom->getElementsByTagName('script');

        $javascript = '';
        foreach ($items as $key => $script) {
            if ($script->hasAttribute('src')) {
                $javascript .= "
                var script$key = document.createElement('script');
                script$key.src = '".$script->getAttribute('src')."';
                document.getElementsByTagName('head')[0].appendChild(script$key);";
            } else {
                $scriptContent = $script->nodeValue;
                $scriptContent = str_replace(["\r\n", "\n", '"'], ['', '', '\"'], $scriptContent);

                $javascript .= "
                var inlineScript$key = document.createTextNode(\"$scriptContent\");
                var script$key       = document.createElement('script');
                script$key.appendChild(inlineScript$key);
                document.getElementsByTagName('head')[0].appendChild(script$key);";
            }
        }

        return $javascript;
    }

    /**
     * Finds out whether the.
     *
     * @param Field $field
     */
    private function addLeadFieldOptions(Field $formField)
    {
        $formFieldProps    = $formField->getProperties();
        $contactFieldAlias = $formField->getLeadField();

        if (empty($formFieldProps['syncList']) || empty($contactFieldAlias)) {
            return;
        }

        $contactField = $this->leadFieldModel->getEntityByAlias($contactFieldAlias);

        if (empty($contactField) || !in_array($contactField->getType(), ContactFieldHelper::getListTypes())) {
            return;
        }

        $contactFieldProps = $contactField->getProperties();

        switch ($contactField->getType()) {
            case 'select':
            case 'multiselect':
            case 'lookup':
                $list = isset($contactFieldProps['list']) ? $contactFieldProps['list'] : [];
                break;
            case 'boolean':
                $list = [$contactFieldProps['no'], $contactFieldProps['yes']];
                break;
            case 'country':
                $list = ContactFieldHelper::getCountryChoices();
                break;
            case 'region':
                $list = ContactFieldHelper::getRegionChoices();
                break;
            case 'timezone':
                $list = ContactFieldHelper::getTimezonesChoices();
                break;
            case 'locale':
                $list = ContactFieldHelper::getLocaleChoices();
                break;
            default:
                return;
        }

        if (!empty($list)) {
            $formFieldProps['list'] = ['list' => $list];
            $formField->setProperties($formFieldProps);
        }
    }
}
