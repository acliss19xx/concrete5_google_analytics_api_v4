<?php
namespace Concrete\Package\PageRankingListGaV4\Block\PageRankingListV4;

use BlockType;
use CollectionAttributeKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Feed;
use Database;
use Page;
use Core;
use Config;
use PageList;
use File;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Tree\Node\Type\Topic;
use \Concrete\Core\Http\Response;
use \Concrete\Core\Http\ResponseFactoryInterface;

if (!function_exists('compat_is_version_8')) {
    function compat_is_version_8() {
        return interface_exists('\Concrete\Core\Export\ExportableInterface');
    }
}

class Controller extends BlockController
{
    protected $btTable = 'btPageRankingListV4';
    protected $btInterfaceWidth = "800";
    protected $btInterfaceHeight = "350";
    protected $btExportPageColumns = ['cParentID'];
    protected $btExportPageTypeColumns = ['ptID'];
    protected $btExportPageFeedColumns = ['pfID'];
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = null;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputLifetime = 300;
    protected $list;

    /**
     * Used for localization. If we want to localize the name/description we have to include this.
     */
    public function getBlockTypeDescription()
    {
        return t("List pages based on type, area.");
    }

    public function getBlockTypeName()
    {
        return t("Page Ranking List V4");
    }

    public function getJavaScriptStrings()
    {
        return [
            'feed-name' => t('Please give your RSS Feed a name.'),
        ];
    }

    public function on_start()
    {
        $this->list = new PageList();
        $this->list->disableAutomaticSorting();
        //$pl->setNameSpace('b' . $this->bID);

        $cArray = [];
/*
        switch ($this->orderBy) {
            case 'display_asc':
                $this->list->sortByDisplayOrder();
                break;
            case 'display_desc':
                $this->list->sortByDisplayOrderDescending();
                break;
            case 'chrono_asc':
                $this->list->sortByPublicDate();
                break;
            case 'random':
                $this->list->sortBy('RAND()');
                break;
            case 'alpha_asc':
                $this->list->sortByName();
                break;
            case 'alpha_desc':
                $this->list->sortByNameDescending();
                break;
            default:
                $this->list->sortByPublicDateDescending();
                break;
        }
*/
        $today = date('Y-m-d');
        $end = $start = null;

        switch ($this->filterDateOption) {
            case 'now':
                $start = "$today 00:00:00";
                $end = "$today 23:59:59";
                break;

            case 'past':
                $end = "$today 23:59:59";

                if ($this->filterDateDays > 0) {
                    $past = date('Y-m-d', strtotime("-{$this->filterDateDays} days"));
                    $start = "$past 00:00:00";
                }
                break;

            case 'future':
                $start = "$today 00:00:00";

                if ($this->filterDateDays > 0) {
                    $future = date('Y-m-d', strtotime("+{$this->filterDateDays} days"));
                    $end = "$future 23:59:59";
                }
                break;

            case 'between':
                $start = "{$this->filterDateStart} 00:00:00";
                $end = "{$this->filterDateEnd} 23:59:59";
                break;

            case 'all':
            default:
                break;
        }

        if ($start) {
            $this->list->filterByPublicDate($start, '>=');
        }
        if ($end) {
            $this->list->filterByPublicDate($end, '<=');
        }

        $c = Page::getCurrentPage();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        if ($this->displayFeaturedOnly == 1) {
            $cak = CollectionAttributeKey::getByHandle('is_featured');
            if (is_object($cak)) {
                $this->list->filterByIsFeatured(1);
            }
        }
        if ($this->displayAliases) {
            $this->list->includeAliases();
        }
        if (isset($this->ignorePermissions) && $this->ignorePermissions) {
            $this->list->ignorePermissions();
        }

        $this->list->filter('cvName', '', '!=');

        if ($this->ptID) {
            $this->list->filterByPageTypeID($this->ptID);
        }

        if ($this->filterByRelated) {
            $ak = CollectionKey::getByHandle($this->relatedTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $topics = $c->getAttribute($ak->getAttributeKeyHandle());
                if (count($topics) > 0 && is_array($topics)) {
                    $topic = $topics[array_rand($topics)];
                    $this->list->filter('p.cID', $c->getCollectionID(), '<>');
                    $this->list->filterByTopic($topic);
                }
            }
        }

        if ($this->filterByCustomTopic) {
            $ak = CollectionKey::getByHandle($this->customTopicAttributeKeyHandle);
            if (is_object($ak)) {
                $ak->getController()->filterByAttribute($this->list, $this->customTopicTreeNodeID);
            }
        }

        $this->list->filterByExcludePageList(false);

        if (intval($this->cParentID) != 0) {
            $cParentID = ($this->cThis) ? $this->cID : (($this->cThisParent) ? $this->cPID : $this->cParentID);
            if ($this->includeAllDescendents) {
                $this->list->filterByPath(Page::getByID($cParentID)->getCollectionPath());
            } else {
                $this->list->filterByParentID($cParentID);
            }
        }

        return $this->list;
    }


    private function analyticsInitialize(){
        $client = new \Google_Client();
        if($this->analyticsServiceJson){
            $api_file = File::getByID($this->analyticsServiceJson);
        }else{
            if(compat_is_version_8()){
                $api_file = Core::make('site')->getSite()->getAttribute('google_api_service_json');
            }
        }
        
        if(is_object($api_file)){
            $client->setApplicationName("Hello Analytics Reporting");
            $client->setAuthConfig($_SERVER['DOCUMENT_ROOT'] . $api_file->getRelativePath());
            $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
            $analytics = new \Google_Service_AnalyticsReporting($client);
            $response = $this->getReport($analytics);
            return $this->printResults($response);
        }else{
            echo t('file not found');
        }
    }
    
    private function getReport($analytics) {
    
      // Replace with your view ID. E.g., XXXX.
      // Create the DateRange object.
      $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
      if(is_numeric($this->analyticsStartDate)){
          $dateRange->setStartDate($this->analyticsStartDate . "daysAgo");
      }else{
          $dateRange->setStartDate("31daysAgo");
      }
      if(is_numeric($this->analyticsEndDate)){
         $dateRange->setEndDate($this->analyticsEndDate ."daysAgo");
      }else{
          $dateRange->setEndDate("0daysAgo");
      }    
      // Create the Metrics object.
      $sessions = new \Google_Service_AnalyticsReporting_Metric();
      $sessions->setExpression("ga:sessions");
      $sessions->setAlias("sessions");
 
      $pageviews = new \Google_Service_AnalyticsReporting_Metric();
      $pageviews->setExpression("ga:pageviews");
      $pageviews->setAlias("pageviews");

      // ディメンション指定
      $dimension = new \Google_Service_AnalyticsReporting_Dimension();
      $dimension->setName('ga:pagePath');

      $ordering = new \Google_Service_AnalyticsReporting_OrderBy();
      if($this->orderBy == 'ranking_asc'){
          $ordering->setSortOrder("ASCENDING");
      }else{
          $ordering->setSortOrder("DESCENDING");
      }
      $ordering->setFieldName("ga:pageviews");
      
      // Create the ReportRequest object.
      $request = new \Google_Service_AnalyticsReporting_ReportRequest();
      if($this->analyticsViewID){
          $request->setViewId($this->analyticsViewID);
      }else{
          if(compat_is_version_8()){
              $request->setViewId(Core::make('site')->getSite()->getAttribute('google_api_view_id'));
          }
      }
      $request->setDimensions($dimension);
      $request->setDateRanges($dateRange);
      $request->setOrderBys($ordering);
      $request->setMetrics(array($pageviews));
    
      $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
      $body->setReportRequests( array( $request) );

      return $analytics->reports->batchGet( $body );

    }

    private function printResults($reports) {
      $pages_ga = [];

      for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();

        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
          $row = $rows[ $rowIndex ];
          $dimensions = $row->getDimensions();
          $metrics = $row->getMetrics();
          for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
            if(strpos($dimensions[$i],'cID') === false){
                $pages_ga[] = array('pagePath' => $dimensions[$i], 'pvCount'=> $metrics[$i]['values'][0]); 
            }
          }
        }
      }
      return $pages_ga;
    }


    
    public function view()
    {
        $c = Page::getCurrentPage();
        if(!$c->isEditMode()){
            $list = $this->list;
            $nh = Core::make('helper/navigation');
            $this->set('nh', $nh);
    
            if ($this->pfID) {
                $this->requireAsset('css', 'font-awesome');
                $feed = Feed::getByID($this->pfID);
                if (is_object($feed)) {
                    $this->set('rssUrl', $feed->getFeedURL());
                    $link = $feed->getHeadLinkElement();
                    $this->addHeaderItem($link);
                }
            }
    
    /*
            //Pagination...
            $showPagination = false;
            if ($this->num > 0) {
                $list->setItemsPerPage($this->num);
                $pagination = $list->getPagination();
                $pages = $pagination->getCurrentPageResults();
                if ($pagination->getTotalPages() > 1 && $this->paginate) {
                    $showPagination = true;
                    $pagination = $pagination->renderDefaultView();
                    $this->set('pagination', $pagination);
                }
            } else {
                $pages = $list->getResults();
            }
    */
    
            $pages_c5 = $list->getResults();
            $pages = [];
            try{
                $results = $this->analyticsInitialize();
            }catch(\Google_Service_Exception $e){
//                echo $e->getMessage();
                echo t("Can not access Google Analytics");
            }
            $li = [];
            // 取得結果でループします。
            if(is_array($results)){
                foreach ($results as $row) {
                    $analyURL = $row['pagePath'];
                    if(strpos($analyURL,'cID') === false){
                        $analyURL = substr($analyURL,strlen(DIR_REL),255);
                        $li[str_replace('/index.php' ,'', $analyURL)] = $row['pvCount'];  // 「キー: ページのパス、値: PV」で配列に追加します。
                    }
                }
            
                $pages_ga = [];
                if(count($li)){
                    foreach ($li as $key => $val){
                        $page_ga = Page::getByPath($key);
                        if(is_numeric($page_ga->getCollectionID())){
                            if(!$page_ga->isSystemPage() && !$page_ga->isHomePage()){
                                foreach($pages_c5 as $p){
                                    if($p->getCollectionID() === $page_ga->getCollectionID()){
                                        $pages_ga[] = $page_ga;
                                    }
                                }
                            }
                        }
                    }
                }
                $pages = [];
                if($this->num > 0){
                    for($i = 0; $this->num > $i && count($pages_ga) > $i; $i++){
                        $pages[] = $pages_ga[$i];          
                    }
                }else{
                 $pages = $pages_ga;   
                }
    
            }
            if ($showPagination) {
                $this->requireAsset('css', 'core/frontend/pagination');
            }
            $this->set('pages', $pages);
            $this->set('list', $list);
            $this->set('showPagination', $showPagination);
        }
    }

    public function add()
    {
        $this->requireAsset('core/topics');
        $c = Page::getCurrentPage();
        $uh = Core::make('helper/concrete/urls');
        $this->set('c', $c);
        $this->set('uh', $uh);
        $this->set('includeDescription', true);
        $this->set('includeName', true);
        $this->set('bt', BlockType::getByHandle('page_list'));
        $this->set('featuredAttribute', CollectionAttributeKey::getByHandle('is_featured'));
        $this->set('thumbnailAttribute', CollectionAttributeKey::getByHandle('thumbnail'));
        $this->loadKeys();
    }

    public function edit()
    {
        $this->requireAsset('core/topics');
        $b = $this->getBlockObject();
        $bCID = $b->getBlockCollectionID();
        $bID = $b->getBlockID();
        $this->set('bID', $bID);
        $c = Page::getCurrentPage();
        if ((!$this->cThis) && (!$this->cThisParent) && ($this->cParentID != 0)) {
            $isOtherPage = true;
            $this->set('isOtherPage', true);
        }
        if ($this->pfID) {
            $feed = Feed::getByID($this->pfID);
            if (is_object($feed)) {
                $this->set('rssFeed', $feed);
            }
        }
        
        if($this->analyticsServiceJson){
            $bf = File::getByID($this->analyticsServiceJson);
            if(is_object($bf)){
                $this->set('bf', $bf);
            }
        }        
        
        $uh = Core::make('helper/concrete/urls');
        $this->set('uh', $uh);
        $this->set('bt', BlockType::getByHandle('page_list'));
        $this->set('featuredAttribute', CollectionAttributeKey::getByHandle('is_featured'));
        $this->set('thumbnailAttribute', CollectionAttributeKey::getByHandle('thumbnail'));
        $this->loadKeys();
    }

    protected function loadKeys()
    {
        $attributeKeys = [];
        $keys = CollectionKey::getList();
        foreach ($keys as $ak) {
            if ($ak->getAttributeTypeHandle() == 'topics') {
                $attributeKeys[] = $ak;
            }
        }
        $this->set('attributeKeys', $attributeKeys);
    }

    public function action_filter_by_topic($treeNodeID = false, $topic = false)
    {
        if ($treeNodeID) {
            $topicObj = Topic::getByID(intval($treeNodeID));
            if (is_object($topicObj) && $topicObj instanceof Topic) {
                $this->list->filterByTopic(intval($treeNodeID));
                $seo = Core::make('helper/seo');
                $seo->addTitleSegment($topicObj->getTreeNodeDisplayName());
            }
        }
        $this->view();
    }

    public function action_filter_by_tag($tag = false)
    {
        $seo = Core::make('helper/seo');
        $seo->addTitleSegment($tag);
        $this->list->filterByTags(h($tag));
        $this->view();
    }

    public function action_filter_by_date($year = false, $month = false, $timezone = 'user')
    {
        if (is_numeric($year)) {
            $year = (($year < 0) ? '-' : '') . str_pad(abs($year), 4, '0', STR_PAD_LEFT);
            if ($month) {
                $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                $lastDayInMonth = date('t', strtotime("$year-$month-01"));
                $start = "$year-$month-01 00:00:00";
                $end = "$year-$month-$lastDayInMonth 23:59:59";
            } else {
                $start = "$year-01-01 00:00:00";
                $end = "$year-12-31 23:59:59";
            }
            $dh = Core::make('helper/date');
            /* @var $dh \Concrete\Core\Localization\Service\Date */
            if ($timezone !== 'system') {
                $start = $dh->toDB($start, $timezone);
                $end = $dh->toDB($end, $timezone);
            }
            $this->list->filterByPublicDate($start, '>=');
            $this->list->filterByPublicDate($end, '<=');

            $seo = Core::make('helper/seo');
            $seo->addTitleSegment($dh->date('F Y', $start));
        }
        $this->view();
    }

    public function validate($args)
    {
        $e = Core::make('helper/validation/error');
        $vs = Core::make('helper/validation/strings');
        $pf = false;
        if ($this->pfID) {
            $pf = Feed::getByID($this->pfID);
        }
        if ($args['rss'] && !is_object($pf)) {
            if (!$vs->handle($args['rssHandle'])) {
                $e->add(t('Your RSS feed must have a valid URL, containing only letters, numbers or underscores'));
            }
            if (!$vs->notempty($args['rssTitle'])) {
                $e->add(t('Your RSS feed must have a valid title.'));
            }
            if (!$vs->notempty($args['rssDescription'])) {
                $e->add(t('Your RSS feed must have a valid description.'));
            }
        }

        return $e;
    }

    public function getPassThruActionAndParameters($parameters)
    {
        if ($parameters[0] == 'topic') {
            $method = 'action_filter_by_topic';
            $parameters = array_slice($parameters, 1);
        } elseif ($parameters[0] == 'tag') {
            $method = 'action_filter_by_tag';
            $parameters = array_slice($parameters, 1);
        } elseif (Core::make('helper/validation/numbers')->integer($parameters[0])) {
            // then we're going to treat this as a year.
            $method = 'action_filter_by_date';
            $parameters[0] = intval($parameters[0]);
            if (isset($parameters[1])) {
                $parameters[1] = intval($parameters[1]);
            }
        } else {
            $parameters = $method = null;
        }

        return [$method, $parameters];
    }

    public function isValidControllerTask($method, $parameters = [])
    {
        if (!$this->enableExternalFiltering) {
            return false;
        }

        return parent::isValidControllerTask($method, $parameters);
    }

    public function save($args)
    {
        // If we've gotten to the process() function for this class, we assume that we're in
        // the clear, as far as permissions are concerned (since we check permissions at several
        // points within the dispatcher)
        $db = Database::connection();

        $bID = $this->bID;
        $c = $this->getCollectionObject();
        if (is_object($c)) {
            $this->cID = $c->getCollectionID();
            $this->cPID = $c->getCollectionParentID();
        }

        $args += [
            'enableExternalFiltering' => 0,
            'includeAllDescendents' => 0,
            'includeDate' => 0,
            'truncateSummaries' => 0,
            'displayFeaturedOnly' => 0,
            'topicFilter' => '',
            'displayThumbnail' => 0,
            'displayAliases' => 0,
            'truncateChars' => 0,
            'paginate' => 0,
            'rss' => 0,
            'pfID' => 0,
            'filterDateOption' => '',
            'cParentID' => null,
        ];

        $args['num'] = ($args['num'] > 0) ? $args['num'] : 0;
        $args['cThis'] = ($args['cParentID'] == $this->cID) ? '1' : '0';
        $args['cThisParent'] = ($args['cParentID'] == $this->cPID) ? '1' : '0';
        $args['cParentID'] = ($args['cParentID'] == 'OTHER') ? $args['cParentIDValue'] : $args['cParentID'];
        if (!$args['cParentID']) {
            $args['cParentID'] = 0;
        }
        $args['enableExternalFiltering'] = ($args['enableExternalFiltering']) ? '1' : '0';
        $args['includeAllDescendents'] = ($args['includeAllDescendents']) ? '1' : '0';
        $args['includeDate'] = ($args['includeDate']) ? '1' : '0';
        $args['truncateSummaries'] = ($args['truncateSummaries']) ? '1' : '0';
        $args['displayFeaturedOnly'] = ($args['displayFeaturedOnly']) ? '1' : '0';
        $args['filterByRelated'] = ($args['topicFilter'] == 'related') ? '1' : '0';
        $args['filterByCustomTopic'] = ($args['topicFilter'] == 'custom') ? '1' : '0';
        $args['displayThumbnail'] = ($args['displayThumbnail']) ? '1' : '0';
        $args['displayAliases'] = ($args['displayAliases']) ? '1' : '0';
        $args['truncateChars'] = intval($args['truncateChars']);
        $args['paginate'] = intval($args['paginate']);
        $args['rss'] = intval($args['rss']);
        $args['ptID'] = intval($args['ptID']);

        if (!$args['filterByRelated']) {
            $args['relatedTopicAttributeKeyHandle'] = '';
        }
        if (!$args['filterByCustomTopic']) {
            $args['customTopicAttributeKeyHandle'] = '';
            $args['customTopicTreeNodeID'] = 0;
        }

        if ($args['rss']) {
            if (isset($this->pfID) && $this->pfID) {
                $pf = Feed::getByID($this->pfID);
            }

            if (!is_object($pf)) {
                $pf = new \Concrete\Core\Entity\Page\Feed();
                $pf->setTitle($args['rssTitle']);
                $pf->setDescription($args['rssDescription']);
                $pf->setHandle($args['rssHandle']);
            }

            $pf->setParentID($args['cParentID']);
            $pf->setPageTypeID($args['ptID']);
            $pf->setIncludeAllDescendents($args['includeAllDescendents']);
            $pf->setDisplayAliases($args['displayAliases']);
            $pf->setDisplayFeaturedOnly($args['displayFeaturedOnly']);
            $pf->setDisplayAliases($args['displayAliases']);
            $pf->displayShortDescriptionContent();
            $pf->save();
            $args['pfID'] = $pf->getID();
        } elseif (isset($this->pfID) && $this->pfID && !$args['rss']) {
            // let's make sure this isn't in use elsewhere.
            $cnt = $db->fetchColumn('select count(pfID) from btPageList where pfID = ?', [$this->pfID]);
            if ($cnt == 1) { // this is the last one, so we delete
                $pf = Feed::getByID($this->pfID);
                if (is_object($pf)) {
                    $pf->delete();
                }
            }
            $args['pfID'] = 0;
        }

        if ($args['filterDateOption'] != 'between') {
            $args['filterDateStart'] = null;
            $args['filterDateEnd'] = null;
        }

        if ($args['filterDateOption'] == 'past') {
            $args['filterDateDays'] = $args['filterDatePast'];
        } elseif ($args['filterDateOption'] == 'future') {
            $args['filterDateDays'] = $args['filterDateFuture'];
        } else {
            $args['filterDateDays'] = null;
        }

        $args['pfID'] = intval($args['pfID']);
        parent::save($args);
    }

    public function isBlockEmpty()
    {
        $pages = $this->get('pages');
        if (isset($this->pageListTitle) && $this->pageListTitle) {
            return false;
        }
        if (count($pages) == 0) {
            if ($this->noResultsMessage) {
                return false;
            } else {
                return true;
            }
        } else {
            if ($this->includeName || $this->includeDate || $this->displayThumbnail
                || $this->includeDescription || $this->useButtonForLink
            ) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function cacheBlockOutput()
    {
        if ($this->btCacheBlockOutput === null) {
            if (!$this->enableExternalFiltering && !$this->paginate) {
                $this->btCacheBlockOutput = true;
            } else {
                $this->btCacheBlockOutput = false;
            }
        }

        return  $this->btCacheBlockOutput;
    }
}

