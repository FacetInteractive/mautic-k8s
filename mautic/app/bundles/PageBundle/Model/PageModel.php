<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\BuilderModelTrait;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\QueueBundle\Queue\QueueName;
use Mautic\QueueBundle\Queue\QueueService;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class PageModel.
 */
class PageModel extends FormModel
{
    use TranslationModelTrait;
    use VariantModelTrait;
    use BuilderModelTrait;

    /**
     * @var bool
     */
    protected $catInUrl;

    /**
     * @var bool
     */
    protected $trackByFingerprint;

    /**
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var FieldModel
     */
    protected $leadFieldModel;

    /**
     * @var RedirectModel
     */
    protected $pageRedirectModel;

    /**
     * @var TrackableModel
     */
    protected $pageTrackableModel;

    /**
     * @var DateTimeHelper
     */
    protected $dateTimeHelper;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var DeviceTracker
     */
    private $deviceTracker;

    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * PageModel constructor.
     *
     * @param CookieHelper   $cookieHelper
     * @param IpLookupHelper $ipLookupHelper
     * @param LeadModel      $leadModel
     * @param FieldModel     $leadFieldModel
     * @param RedirectModel  $pageRedirectModel
     * @param TrackableModel $pageTrackableModel
     * @param QueueService   $queueService
     * @param CompanyModel   $companyModel
     * @param DeviceTracker  $deviceTracker
     */
    public function __construct(
        CookieHelper $cookieHelper,
        IpLookupHelper $ipLookupHelper,
        LeadModel $leadModel,
        FieldModel $leadFieldModel,
        RedirectModel $pageRedirectModel,
        TrackableModel $pageTrackableModel,
        QueueService $queueService,
        CompanyModel $companyModel,
        DeviceTracker $deviceTracker
    ) {
        $this->cookieHelper       = $cookieHelper;
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->leadModel          = $leadModel;
        $this->leadFieldModel     = $leadFieldModel;
        $this->pageRedirectModel  = $pageRedirectModel;
        $this->pageTrackableModel = $pageTrackableModel;
        $this->dateTimeHelper     = new DateTimeHelper();
        $this->queueService       = $queueService;
        $this->companyModel       = $companyModel;
        $this->deviceTracker      = $deviceTracker;
    }

    /**
     * @param $catInUrl
     */
    public function setCatInUrl($catInUrl)
    {
        $this->catInUrl = $catInUrl;
    }

    /**
     * @return \Mautic\PageBundle\Entity\PageRepository
     */
    public function getRepository()
    {
        $repo = $this->em->getRepository('MauticPageBundle:Page');
        $repo->setCurrentUser($this->userHelper->getUser());

        return $repo;
    }

    /**
     * @return \Mautic\PageBundle\Entity\HitRepository
     */
    public function getHitRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Hit');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'page:pages';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter()
    {
        return 'getTitle';
    }

    /**
     * {@inheritdoc}
     *
     * @param Page $entity
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        $pageIds = $entity->getRelatedEntityIds();

        if (empty($this->inConversion)) {
            $alias = $entity->getAlias();
            if (empty($alias)) {
                $alias = $entity->getTitle();
            }
            $alias = $this->cleanAlias($alias, '', false, '-');

            //make sure alias is not already taken
            $repo      = $this->getRepository();
            $testAlias = $alias;
            $count     = $repo->checkPageUniqueAlias($testAlias, $pageIds);
            $aliasTag  = 1;

            while ($count) {
                $testAlias = $alias.$aliasTag;
                $count     = $repo->checkPageUniqueAlias($testAlias, $pageIds);
                ++$aliasTag;
            }
            if ($testAlias != $alias) {
                $alias = $testAlias;
            }
            $entity->setAlias($alias);
        }

        // Set the author for new pages
        $isNew = $entity->isNew();
        if (!$isNew) {
            //increase the revision
            $revision = $entity->getRevision();
            ++$revision;
            $entity->setRevision($revision);
        }

        // Reset a/b test if applicable
        $variantStartDate = new \DateTime();
        $resetVariants    = $this->preVariantSaveEntity($entity, ['setVariantHits'], $variantStartDate);

        parent::saveEntity($entity, $unlock);

        $this->postVariantSaveEntity($entity, $resetVariants, $pageIds, $variantStartDate);
        $this->postTranslationEntitySave($entity);
    }

    /**
     * @param Page $entity
     */
    public function deleteEntity($entity)
    {
        if ($entity->isVariant() && $entity->getIsPublished()) {
            $this->resetVariants($entity);
        }

        parent::deleteEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(['Page']);
        }

        $formName = 'page';

        if (!empty($options['formName'])) {
            $formName = $options['formName'];
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create($formName, $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return null|Page
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Page();
            $entity->setSessionId('new_'.hash('sha1', uniqid(mt_rand())));
        } else {
            $entity = parent::getEntity($id);
            if ($entity !== null) {
                $entity->setSessionId($entity->getId());
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Page) {
            throw new MethodNotAllowedHttpException(['Page']);
        }

        switch ($action) {
            case 'pre_save':
                $name = PageEvents::PAGE_PRE_SAVE;
                break;
            case 'post_save':
                $name = PageEvents::PAGE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = PageEvents::PAGE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = PageEvents::PAGE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Get list of entities for autopopulate fields.
     *
     * @param string $type
     * @param string $filter
     * @param int    $limit
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10)
    {
        $results = [];
        switch ($type) {
            case 'page':
                $viewOther = $this->security->isGranted('page:pages:viewother');
                $repo      = $this->getRepository();
                $repo->setCurrentUser($this->userHelper->getUser());
                $results = $repo->getPageList($filter, $limit, 0, $viewOther);
                break;
        }

        return $results;
    }

    /**
     * Generate URL for a page.
     *
     * @param Page  $entity
     * @param bool  $absolute
     * @param array $clickthrough
     *
     * @return string
     */
    public function generateUrl($entity, $absolute = true, $clickthrough = [])
    {
        // If this is a variant, then get the parent's URL
        $parent = $entity->getVariantParent();
        if ($parent != null) {
            $entity = $parent;
        }

        $slug = $this->generateSlug($entity);

        return $this->buildUrl('mautic_page_public', ['slug' => $slug], $absolute, $clickthrough);
    }

    /**
     * Generates slug string.
     *
     * @param $entity
     *
     * @return string
     */
    public function generateSlug($entity)
    {
        $pageSlug = $entity->getAlias();

        //should the url include the category
        if ($this->catInUrl) {
            $category = $entity->getCategory();
            $catSlug  = (!empty($category))
                ? $category->getAlias()
                :
                $this->translator->trans('mautic.core.url.uncategorized');
        }

        $parent = $entity->getTranslationParent();
        $slugs  = [];
        if ($parent) {
            //multiple languages so tack on the language
            $slugs[] = $entity->getLanguage();
        }

        if (!empty($catSlug)) {
            // Insert category slug
            $slugs[] = $catSlug;
            $slugs[] = $pageSlug;
        } else {
            // Insert just the page slug
            $slugs[] = $pageSlug;
        }

        return implode('/', $slugs);
    }

    /**
     * @param Hit $hit
     *
     * @return array|mixed
     */
    protected function generateClickThrough(Hit $hit)
    {
        $query = $hit->getQuery();

        // Check for any clickthrough info
        $clickthrough = [];
        if (!empty($query['ct'])) {
            $clickthrough = $query['ct'];
            if (!is_array($clickthrough)) {
                $clickthrough = $this->decodeArrayFromUrl($clickthrough);
            }
        }

        return $clickthrough;
    }

    /**
     * @param Page|Redirect $page
     * @param Request       $request
     * @param string        $code
     * @param Lead|null     $lead
     * @param array         $query
     *
     * @throws \Exception
     */
    public function hitPage($page, Request $request, $code = '200', Lead $lead = null, $query = [])
    {
        // Don't skew results with user hits
        if (!$this->security->isAnonymous()) {
            return;
        }

        // Process the query
        if (empty($query)) {
            $query = $this->getHitQuery($request, $page);
        }

        // Get lead if required
        if (null == $lead) {
            $lead = $this->leadModel->getContactFromRequest($query);

            // company
            list($company, $leadAdded, $companyEntity) = IdentifyCompanyHelper::identifyLeadsCompany($query, $lead, $this->companyModel);
            if ($leadAdded) {
                $lead->addCompanyChangeLogEntry('form', 'Identify Company', 'Lead added to the company, '.$company['companyname'], $company['id']);
            } elseif ($companyEntity instanceof Company) {
                $this->companyModel->setFieldValues($companyEntity, $query);
                $this->companyModel->saveEntity($companyEntity);
            }

            if (!empty($company) and $companyEntity instanceof Company) {
                // Save after the lead in for new leads created through the API and maybe other places
                $this->companyModel->addLeadToCompany($companyEntity, $lead);
                $this->leadModel->setPrimaryCompany($companyEntity->getId(), $lead->getId());
            }
        }

        if (!$lead || !$lead->getId()) {
            // Lead came from a non-trackable IP so ignore
            return;
        }

        $hit = new Hit();
        $hit->setDateHit(new \Datetime());
        $hit->setIpAddress($this->ipLookupHelper->getIpAddress());

        // Set info from request
        $hit->setQuery($query);
        $hit->setCode($code);

        $trackedDevice = $this->deviceTracker->createDeviceFromUserAgent($lead, $request->server->get('HTTP_USER_AGENT'));
        if (!empty($query['fingerprint']) && $trackedDevice->getDeviceFingerprint() !== $query['fingerprint']) {
            $trackedDevice->setDeviceFingerprint($query['fingerprint']);
        }
        $hit->setTrackingId($trackedDevice->getTrackingId());
        $hit->setDeviceStat($trackedDevice);

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush();
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            } else {
                $this->logger->addError(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        }

        //save hit to the cookie to use to update the exit time
        if ($hit) {
            $this->cookieHelper->setCookie('mautic_referer_id', $hit->getId() ?: null);
        }

        if ($this->queueService->isQueueEnabled()) {
            $msg = [
                'hitId'         => $hit->getId(),
                'pageId'        => $page ? $page->getId() : null,
                'request'       => $request,
                'leadId'        => $lead ? $lead->getId() : null,
                'isNew'         => $this->deviceTracker->wasDeviceChanged(),
                'isRedirect'    => ($page instanceof Redirect),
            ];
            $this->queueService->publishToQueue(QueueName::PAGE_HIT, $msg);
        } else {
            $this->processPageHit($hit, $page, $request, $lead, $this->deviceTracker->wasDeviceChanged());
        }
    }

    /**
     * Process page hit.
     *
     * @param Hit           $hit
     * @param Page|Redirect $page
     * @param Request       $request
     * @param Lead          $lead
     * @param bool          $trackingNewlyGenerated
     * @param bool          $activeRequest
     *
     * @throws \Exception
     */
    public function processPageHit(Hit $hit, $page, Request $request, Lead $lead, $trackingNewlyGenerated, $activeRequest = true)
    {
        // Store Page/Redirect association
        if ($page) {
            if ($page instanceof Page) {
                $hit->setPage($page);
            } else {
                $hit->setRedirect($page);
            }
        }

        // Check for any clickthrough info
        $clickthrough = $this->generateClickThrough($hit);
        if (!empty($clickthrough)) {
            if (!empty($clickthrough['channel'])) {
                if (count($clickthrough['channel']) === 1) {
                    $channelId = reset($clickthrough['channel']);
                    $channel   = key($clickthrough['channel']);
                } else {
                    $channel   = $clickthrough['channel'][0];
                    $channelId = (int) $clickthrough['channel'][1];
                }
                $hit->setSource($channel);
                $hit->setSourceId($channelId);
            } elseif (!empty($clickthrough['source'])) {
                $hit->setSource($clickthrough['source'][0]);
                $hit->setSourceId($clickthrough['source'][1]);
            }

            if (!empty($clickthrough['email'])) {
                $emailRepo = $this->em->getRepository('MauticEmailBundle:Email');
                if ($emailEntity = $emailRepo->getEntity($clickthrough['email'])) {
                    $hit->setEmail($emailEntity);
                }
            }
        }

        $query = $hit->getQuery() ? $hit->getQuery() : [];

        if (isset($query['timezone_offset']) && !$lead->getTimezone()) {
            // timezone_offset holds timezone offset in minutes. Multiply by 60 to get seconds.
            // Multiply by -1 because Firgerprint2 seems to have it the other way around.
            $timezone = (-1 * $query['timezone_offset'] * 60);
            $lead->setTimezone($this->dateTimeHelper->guessTimezoneFromOffset($timezone));
        }

        $query = $this->cleanQuery($query);

        if (isset($query['page_referrer'])) {
            $hit->setReferer($query['page_referrer']);
        }
        if (isset($query['page_language'])) {
            $hit->setPageLanguage($query['page_language']);
        }
        if (isset($query['page_title'])) {
            $safeTitle = InputHelper::transliterate($query['page_title']);
            $hit->setUrlTitle($safeTitle);
            $query['page_title'] = $safeTitle;
        }

        $hit->setQuery($query);
        $hit->setUrl((isset($query['page_url'])) ? $query['page_url'] : $request->getRequestUri());

        // Add entry to contact log table
        $this->setLeadManipulator($page, $hit, $lead);

        // Store tracking ID
        $hit->setLead($lead);

        if (!$activeRequest) {
            // Queue is consuming this hit outside of the lead's active request so this must be set in order for listeners to know who the request belongs to
            $this->leadModel->setSystemCurrentLead($lead);
        }
        $trackingId = $hit->getTrackingId();
        if (!$trackingNewlyGenerated) {
            $lastHit = $request->cookies->get('mautic_referer_id');
            if (!empty($lastHit)) {
                //this is not a new session so update the last hit if applicable with the date/time the user left
                $this->getHitRepository()->updateHitDateLeft($lastHit);
            }
        }

        // Check if this is a unique page hit
        $isUnique = $this->getHitRepository()->isUniquePageHit($page, $trackingId, $lead);

        if (!empty($page)) {
            if ($page instanceof Page) {
                $hit->setPageLanguage($page->getLanguage());

                $isVariant = ($isUnique) ? $page->getVariantStartDate() : false;

                try {
                    $this->getRepository()->upHitCount($page->getId(), 1, $isUnique, !empty($isVariant));
                } catch (\Exception $exception) {
                    $this->logger->addError(
                        $exception->getMessage(),
                        ['exception' => $exception]
                    );
                }
            } elseif ($page instanceof Redirect) {
                try {
                    $this->pageRedirectModel->getRepository()->upHitCount($page->getId(), 1, $isUnique);

                    // If this is a trackable, up the trackable counts as well
                    if ($hit->getSource() && $hit->getSourceId()) {
                        $this->pageTrackableModel->getRepository()->upHitCount(
                            $page->getId(),
                            $hit->getSource(),
                            $hit->getSourceId(),
                            1,
                            $isUnique
                        );
                    }
                } catch (\Exception $exception) {
                    if (MAUTIC_ENV === 'dev') {
                        throw $exception;
                    } else {
                        $this->logger->addError(
                            $exception->getMessage(),
                            ['exception' => $exception]
                        );
                    }
                }
            }
        }

        //glean info from the IP address
        $ipAddress = $hit->getIpAddress();
        if ($details = $ipAddress->getIpDetails()) {
            $hit->setCountry($details['country']);
            $hit->setRegion($details['region']);
            $hit->setCity($details['city']);
            $hit->setIsp($details['isp']);
            $hit->setOrganization($details['organization']);
        }

        if (!$hit->getReferer()) {
            $hit->setReferer($request->server->get('HTTP_REFERER'));
        }

        $hit->setUserAgent($request->server->get('HTTP_USER_AGENT'));
        $hit->setRemoteHost($request->server->get('REMOTE_HOST'));

        if ($isUnique) {
            // Add UTM tags entry if a UTM tag exist
            $queryHasUtmTags = false;
            if (!is_array($query)) {
                parse_str($query, $query);
            }

            foreach ($query as $key => $value) {
                if (strpos($key, 'utm_') !== false) {
                    $queryHasUtmTags = true;
                    break;
                }
            }

            if ($queryHasUtmTags && $lead) {
                $utmTags = new UtmTag();
                $utmTags->setDateAdded($hit->getDateHit());
                $utmTags->setUrl($hit->getUrl());
                $utmTags->setReferer($hit->getReferer());
                $utmTags->setQuery($hit->getQuery());
                $utmTags->setUserAgent($hit->getUserAgent());
                $utmTags->setRemoteHost($hit->getRemoteHost());
                $utmTags->setLead($lead);

                if (key_exists('utm_campaign', $query)) {
                    $utmTags->setUtmCampaign($query['utm_campaign']);
                }
                if (key_exists('utm_term', $query)) {
                    $utmTags->setUtmTerm($query['utm_term']);
                }
                if (key_exists('utm_content', $query)) {
                    $utmTags->setUtmContent($query['utm_content']);
                }
                if (key_exists('utm_medium', $query)) {
                    $utmTags->setUtmMedium($query['utm_medium']);
                }
                if (key_exists('utm_source', $query)) {
                    $utmTags->setUtmSource($query['utm_source']);
                }

                $repo = $this->em->getRepository('MauticLeadBundle:UtmTag');
                $repo->saveEntity($utmTags);

                $this->leadModel->setUtmTags($lead, $utmTags);
            }
        }
        //get a list of the languages the user prefers
        $browserLanguages = $request->server->get('HTTP_ACCEPT_LANGUAGE');
        if (!empty($browserLanguages)) {
            $languages = explode(',', $browserLanguages);
            foreach ($languages as $k => $l) {
                if ($pos = strpos(';q=', $l) !== false) {
                    //remove weights
                    $languages[$k] = substr($l, 0, $pos);
                }
            }
            $hit->setBrowserLanguages($languages);
        }

        // Wrap in a try/catch to prevent deadlock errors on busy servers
        try {
            $this->em->persist($hit);
            $this->em->flush();
        } catch (\Exception $exception) {
            if (MAUTIC_ENV === 'dev') {
                throw $exception;
            } else {
                $this->logger->addError(
                    $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        }

        if ($this->dispatcher->hasListeners(PageEvents::PAGE_ON_HIT)) {
            $event = new PageHitEvent($hit, $request, $hit->getCode(), $clickthrough, $isUnique);
            $this->dispatcher->dispatch(PageEvents::PAGE_ON_HIT, $event);
        }
    }

    /**
     * @param Request            $request
     * @param null|Redirect|Page $page
     *
     * @return array
     */
    public function getHitQuery(Request $request, $page = null)
    {
        $get  = $request->query->all();
        $post = $request->request->all();

        $query = \array_merge($get, $post);

        // Set generated page url
        $query['page_url'] = $this->getPageUrl($request, $page);

        // Process clickthrough if applicable
        if (!empty($query['ct'])) {
            $query['ct'] = $this->decodeArrayFromUrl($query['ct']);
        }

        return $query;
    }

    /**
     * Get array of page builder tokens from bundles subscribed PageEvents::PAGE_ON_BUILD.
     *
     * @param null|Page    $page
     * @param array|string $requestedComponents all | tokens | abTestWinnerCriteria
     * @param null|string  $tokenFilter
     *
     * @return array
     */
    public function getBuilderComponents(Page $page = null, $requestedComponents = 'all', $tokenFilter = null)
    {
        $event = new PageBuilderEvent($this->translator, $page, $requestedComponents, $tokenFilter);
        $this->dispatcher->dispatch(PageEvents::PAGE_ON_BUILD, $event);

        return $this->getCommonBuilderComponents($requestedComponents, $event);
    }

    /**
     * Get number of page bounces.
     *
     * @param Page      $page
     * @param \DateTime $fromDate
     *
     * @return int
     */
    public function getBounces(Page $page, \DateTime $fromDate = null)
    {
        return $this->getHitRepository()->getBounces($page->getId(), $fromDate);
    }

    /**
     * Joins the page table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder &$q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = t.page_id')
            ->andWhere('p.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of hits.
     *
     * @param char      $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$flag || $flag == 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.page.show.total.visits'), $data);
        }

        if ($flag == 'unique' || $flag == 'total_and_unique') {
            $q = $query->prepareTimeDataQuery('page_hits', 'date_hit', $filter, 'distinct(t.lead_id)');

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.page.show.unique.visits'), $data);
        }

        return $chart->render();
    }

    /**
     * Get data for pie chart showing new vs returning leads.
     * Returning leads are even leads who visits 2 different page once.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getNewVsReturningPieChartData($dateFrom, $dateTo, $filters = [], $canViewOthers = true)
    {
        $chart              = new PieChart();
        $query              = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $allQ               = $query->getCountQuery('page_hits', 'id', 'date_hit', $filters);
        $filters['lead_id'] = [
            'expression' => 'isNull',
        ];
        $returnQ            = $query->getCountQuery('page_hits', 'id', 'date_hit', $filters);

        if (!$canViewOthers) {
            $this->limitQueryToCreator($allQ);
            $this->limitQueryToCreator($returnQ);
        }

        $all = $query->fetchCount($allQ);
//        $unique    = $query->fetchCount($uniqueQ);
        $returning = $query->fetchCount($returnQ);
        $unique    = $all - $returning;
        $chart->setDataset($this->translator->trans('mautic.page.unique'), $unique);
        $chart->setDataset($this->translator->trans('mautic.page.graph.pie.new.vs.returning.returning'), $returning);

        return $chart->render();
    }

    /**
     * Get pie chart data of dwell times.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getDwellTimesPieChartData(\DateTime $dateFrom, \DateTime $dateTo, $filters = [], $canViewOthers = true)
    {
        $timesOnSite = $this->getHitRepository()->getDwellTimeLabels();
        $chart       = new PieChart();
        $query       = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        foreach ($timesOnSite as $time) {
            $q = $query->getCountDateDiffQuery('page_hits', 'date_hit', 'date_left', $time['from'], $time['till'], $filters);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->fetchCountDateDiff($q);
            $chart->setDataset($time['label'], $data);
        }

        return $chart->render();
    }

    /**
     * Get bar chart data of hits.
     *
     * @param char     $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string   $dateFormat
     * @param array    $filter
     *
     * @return array
     */
    public function getDeviceGranularityData(\DateTime $dateFrom, \DateTime $dateTo, $filters = [], $canViewOthers = true)
    {
        $data['values'] = [];
        $data['labels'] = [];

        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(h.id) as count, ds.device as device')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->join('h', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id=h.device_id')
            ->orderBy('device', 'DESC')
            ->andWhere($q->expr()->gte('h.date_hit', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('h.date_hit', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d'.' 23:59:59'));
        $q->groupBy('ds.device');

        $results = $q->execute()->fetchAll();

        $chart = new PieChart($data['labels']);

        if (empty($results)) {
            $results[] = [
                'device' => $this->translator->trans('mautic.report.report.noresults'),
                'count'  => 0,
            ];
        }

        foreach ($results as $result) {
            $label = empty($result['device']) ? $this->translator->trans('mautic.core.no.info') : $result['device'];

            // $data['backgroundColor'][]='rgba(220,220,220,0.5)';
            $chart->setDataset($label, $result['count']);
        }

        return $chart->render();
    }

    /**
     * Get a list of popular (by hits) pages.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getPopularPages($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(DISTINCT t.id) AS hits, p.id, p.title, p.alias')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'pages', 'p', 'p.id = t.page_id')
            ->orderBy('hits', 'DESC')
            ->groupBy('p.id')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('p.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyFilters($q, $filters);
        $chartQuery->applyDateFilters($q, 'date_hit');

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of pages created in a date range.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array     $filters
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getPageList($limit = 10, \DateTime $dateFrom = null, \DateTime $dateTo = null, $filters = [], $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('t.id, t.title AS name, t.date_added, t.date_modified')
            ->from(MAUTIC_TABLE_PREFIX.'pages', 't')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
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
     * @param      $page
     * @param Hit  $hit
     * @param Lead $lead
     */
    private function setLeadManipulator($page, Hit $hit, Lead $lead)
    {
        // Only save the lead and dispatch events if needed
        $source   = 'hit';
        $sourceId = $hit->getId();
        if ($page) {
            $source   = $page instanceof Page ? 'page' : 'redirect';
            $sourceId = $page->getId();
        }

        $lead->setManipulator(
            new LeadManipulator(
                'page',
                $source,
                $sourceId,
                $hit->getUrl()
            )
        );

        $this->leadModel->saveEntity($lead);
    }

    /**
     * @param Request $request
     * @param         $page
     *
     * @return mixed|string
     */
    private function getPageUrl(Request $request, $page)
    {
        // Default to page_url set in the query from tracking pixel and/or contactfield token
        if ($pageURL = $request->get('page_url')) {
            return $pageURL;
        }

        if ($page instanceof Redirect) {
            //use the configured redirect URL
            return $page->getUrl();
        }

        // Use the current URL
        $isPageEvent = false;
        if (strpos($request->server->get('REQUEST_URI'), $this->router->generate('mautic_page_tracker')) !== false) {
            // Tracking pixel is used
            if ($request->server->get('QUERY_STRING')) {
                parse_str($request->server->get('QUERY_STRING'), $query);
                $isPageEvent = true;
            }
        } elseif (strpos($request->server->get('REQUEST_URI'), $this->router->generate('mautic_page_tracker_cors')) !== false) {
            $query       = $request->request->all();
            $isPageEvent = true;
        }

        if ($isPageEvent) {
            $pageURL = $request->server->get('HTTP_REFERER');

            // if additional data were sent with the tracking pixel
            if (isset($query)) {
                // URL attr 'd' is encoded so let's decode it first.
                $decoded = false;
                if (isset($query['d'])) {
                    // parse_str auto urldecodes
                    $query   = $this->decodeArrayFromUrl($query['d'], false);
                    $decoded = true;
                }

                if (is_array($query) && !empty($query)) {
                    if (isset($query['page_url'])) {
                        $pageURL = $query['page_url'];
                        if (!$decoded) {
                            $pageURL = urldecode($pageURL);
                        }
                    }

                    if (isset($query['page_referrer'])) {
                        if (!$decoded) {
                            $query['page_referrer'] = urldecode($query['page_referrer']);
                        }
                    }

                    if (isset($query['page_language'])) {
                        if (!$decoded) {
                            $query['page_language'] = urldecode($query['page_language']);
                        }
                    }

                    if (isset($query['page_title'])) {
                        if (!$decoded) {
                            $query['page_title'] = urldecode($query['page_title']);
                        }
                    }

                    if (isset($query['tags'])) {
                        if (!$decoded) {
                            $query['tags'] = urldecode($query['tags']);
                        }
                    }
                }
            }

            return $pageURL;
        }

        $pageURL = 'http';
        if ($request->server->get('HTTPS') == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://';

        if (!in_array((int) $request->server->get('SERVER_PORT', 80), [80, 8080, 443])) {
            return $pageURL.$request->server->get('SERVER_NAME').':'.$request->server->get('SERVER_PORT').
                $request->server->get('REQUEST_URI');
        }

        return $pageURL.$request->server->get('SERVER_NAME').$request->server->get('REQUEST_URI');
    }

    /**
     * @deprecated 2.13.0; no longer used
     *
     * @param $trackByFingerprint
     */
    public function setTrackByFingerprint($trackByFingerprint)
    {
    }

    /**
     * @deprecated 2.1 - use $entity->getVariants() instead; to be removed in 3.0
     *
     * @param Page $entity
     *
     * @return array
     */
    public function getVariants(Page $entity)
    {
        return $entity->getVariants();
    }

    /*
     * Cleans query params saving url values.
     *
     * @param $query array
     *
     * @return array
     */
    private function cleanQuery($query)
    {
        foreach ($query as $key => $value) {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $query[$key] = InputHelper::url($value);
            } else {
                $query[$key] = InputHelper::clean($value);
            }
        }

        return $query;
    }
}
