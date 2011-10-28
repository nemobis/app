<?php

/**
 * This class represents RelatedVideos data for an article that is stored in the namspace NS_RELATED_VIDEOS.
 */

class RelatedVideosNamespaceData {

	private $mData;
	private $mId;
	private $mExists;
	private $mMemcacheKey;
	private $oParser;
	private $oParserOptions;
	private $oFakeTitle;

	const CACHE_TTL = 86400;
	const CACHE_VER = 5;
	const VIDEOWIKI_MARKER = 'VW:';
	const BLACKLIST_MARKER = 'BLACKLIST';
	const WHITELIST_MARKER = 'WHITELIST';
	const VIDEO_MARKER = '* ';
	const GLOBAL_RV_LIST = 'RelatedVideosGlobalList';
	
	private function __construct($id, Title $title=null) {
		$this->mId = $id;
		$this->mTitle = ($title ? $title : null);
		$this->mData = null;
		$this->mExists = ($id > 0);
		$this->mMemcacheKey = wfMemcKey('relatedVideosNSData', 'data', F::app()->wg->wikiaVideoRepoDBName, $id, self::CACHE_VER);

		wfDebug(__METHOD__ . ": relatedVideosNS article ID #{$id}\n");
	}
	
	/**
	 * Return instance of this class for given article from RelatedVideos namespace
	 */
	static public function newFromArticle( Article $article ) {
		$id = $article->getID();
		return self::newFromId( $id );
	}

	/**
	 * Return instance of this class for given title from RelatedVideos namespace
	 */
	static public function newFromTitle( Title $title ) {
		$id = $title->getArticleId();
		return new self( $id, $title );
	}

	/**
	 * Return instance of this class for given RelatedVideos namespace's article ID
	 */
	static public function newFromId( $id ) {
		return $id ? new self( $id ) : null;
	}

	static public function newFromTargetTitle( $title ) {
		$oTitle = Title::newFromText( $title->getText(), NS_RELATED_VIDEOS );
		return self::newFromTitle( $oTitle );
	}

	static public function newFromGeneralMessage() {
		$oTitle =  Title::newFromText( self::GLOBAL_RV_LIST, NS_MEDIAWIKI );
		return $oTitle->exists() ? self::newFromTitle( $oTitle ) : null;
	}
	
	static public function create(Title $title) {

		wfProfileIn( __METHOD__ );
		if (empty($title) || $title->exists()) {
			return;
		}

		$article = F::build('Article', array($title));		
		$status = $article->doEdit('', 'Article created', EDIT_NEW, false, F::app()->wg->user);
		
		wfProfileOut( __METHOD__ );
		return self::newFromTitle($article->getTitle());
	}
	
	public function getId() {
		return $this->mId;
	}

	/**
	 * Get this instance's data
	 */
	public function getData() {
		if ( is_null( $this->mData ) ) {
			$this->load();
		}

		return $this->mData;
	}

	/**
	 * Return true if article exists
	 */
	public function exists() {
		if ( is_null( $this->mData ) ) {
			$this->load();
		}

		return $this->mExists === true;
	}

	/**
	 * Load RelatedVideos NS article data (try to use cache layer)
	 */
	private function load( $master = false ) {
		global $wgMemc;
		
		wfProfileIn(__METHOD__);

		if (!$master) {
			$this->mData = $wgMemc->get( $this->mMemcacheKey );
		}

		if ( empty( $this->mData ) ) {
			$article = Article::newFromID($this->mId);

			// check existence
			if ( empty( $article ) ) {
				wfDebug(__METHOD__ . ": RelatedVideos NS article doesn't exist\n");
				wfProfileOut(__METHOD__);
				return;
			}

			// parse wikitext with RelatedVideos NS data (stored as wikitext list)
			$content = $article->getContent();

			$lines = explode("\n", $content);
			$lists = array();
			$lists[ self::BLACKLIST_MARKER ] = array();
			$lists[ self::WHITELIST_MARKER ] = array();
			$mode = '';
			foreach($lines as $line) {
				$line = trim($line);
				
				if (strtoupper($line) == self::BLACKLIST_MARKER) {
					$mode = self::BLACKLIST_MARKER;
				}
				elseif (strtoupper($line) == self::WHITELIST_MARKER) {
					$mode = self::WHITELIST_MARKER;
				}
				elseif (startsWith($line, self::VIDEO_MARKER)) {
					$line = substr($line, strlen(self::VIDEO_MARKER));
					
					$isFromVideoWiki = false;
					$title = $line;
					if (startsWith($line, self::VIDEOWIKI_MARKER)) {
						$title = substr($line, strlen(self::VIDEOWIKI_MARKER));
						$isFromVideoWiki = true;
					}
					
					if ($mode) {
						$lists[$mode][] = $this->createEntry($title, $isFromVideoWiki);
					}
				}
			}
				
			
			$this->mData = array(
				'lists' => $lists,
			);

			wfDebug(__METHOD__ . ": loaded from scratch\n");
			
			// store it in memcache
			F::app()->wg->memc->set($this->mMemcacheKey, $this->mData, self::CACHE_TTL);
		}
		else {
			wfDebug(__METHOD__ . ": loaded from memcache\n");
		}

		$this->mExists = true;

		wfProfileOut(__METHOD__);
		return;
	}
	
	/**
	 * Make contents for RelatedVideosNamespace article out of mData
	 */
	private function serialize() {

		wfProfileIn( __METHOD__ );
		$text = '';
		if (is_array($this->mData['lists'])) {
			if (!empty($this->mData['lists'][self::WHITELIST_MARKER])) {
				$text .= self::WHITELIST_MARKER . "\n\n";
				$text .= $this->serializeList($this->mData['lists'][self::WHITELIST_MARKER]);
			}

			if (!empty($this->mData['lists'][self::BLACKLIST_MARKER])) {
				$text .= "\n" . self::BLACKLIST_MARKER . "\n\n";
				$text .= $this->serializeList($this->mData['lists'][self::BLACKLIST_MARKER]);				
			}
		}
		wfProfileOut( __METHOD__ );
		return $text;
	}
	
	/**
	 * Make contents for RelatedVideosNamespace article out of a video list
	 * @param Array $list
	 * @return string wikitext
	 */
	private function serializeList(Array $list) {

		wfProfileIn( __METHOD__ );
		$text = '';
		foreach ($list as $videoData) {
			$text .= self::VIDEO_MARKER;
			if ($videoData['source'] == F::app()->wg->WikiaVideoRepoDBName) {
				$text .= self::VIDEOWIKI_MARKER;
			}
			$text .= $videoData['title'] . "\n";
		}
		wfProfileOut( __METHOD__ );
		return $text;
	}
	
	/**
	 * Purges memcache entry
	 */
	public function purge() {

		global $wgMemc;

		wfProfileIn(__METHOD__);
		// clear data cache
		$wgMemc->delete($this->mMemcacheKey);
		$this->mData = null;

		$article = Article::newFromId($this->mId);
		if (!empty($article)) {
			// purge page
			$article->doPurge();

			// apply changes to page_touched fields
			$dbw = wfGetDB(DB_MASTER);
			$dbw->commit();
		}
		
		wfDebug(__METHOD__ . ": purged RelatedVideos NS article #{$this->mId}\n");
		wfProfileOut(__METHOD__);
	}
	
	/**
	 * Add entries to specified list, and remove them from the other list
	 * @param string $list BLACKLIST_MARKER or WHITELIST_MARKER
	 * @param array $entries
	 * @param int $mainNsArticleId ID of associated article in NS_MAIN
	 * @return type 
	 */
	public function addToList($list, Array $entries, $mainNsArticleId) {

		wfProfileIn( __METHOD__ );
		$content = '';
		$status = '';
		$summary = '';
		$this->load();

		if ($list == self::WHITELIST_MARKER) {
			// check Related Pages results for duplicate
			// initialize categories of RelatedPages object
			$mainNsTitle = Title::newFromID($mainNsArticleId);
			$categoryMap = $mainNsTitle->getParentCategories();
			$startIdx = strlen('Category:');
			$categories = array();
			foreach ($categoryMap as $categoryTitle=>$articleTitle) {
				$shortCategoryTitle = substr($categoryTitle, $startIdx);
				$categories[$shortCategoryTitle] = $articleTitle;
			}
			RelatedVideos::getInstance()->setCategories($categories);

			// get related videos
			$relatedVideos = RelatedVideos::getInstance()->get(
				$mainNsArticleId,
				RelatedVideos::MAX_RELATEDVIDEOS
				);

			// check for duplicate
			foreach ($relatedVideos as $dateId=>$rvData) {
				foreach ($entries as $newEntry) {
					if ($newEntry['title'] == $rvData['title']) {
						wfProfileOut( __METHOD__ );
						return wfMsg('related-videos-add-video-error-duplicate');						
					}
				}
			}
		}
		
		// first, add to this list
		if (!empty($this->mData['lists'][$list])) {
			foreach ($this->mData['lists'][$list] as $entry) {
				foreach ($entries as $newEntry) {
					if ($newEntry['title'] == $entry['title']
					&& $newEntry['source'] == $entry['source']) {
						if ($list == self::WHITELIST_MARKER) {
							wfProfileOut( __METHOD__ );
							return wfMsg('related-videos-add-video-error-duplicate');
						}
						else {
							// no need to report duplicate error.
							// ok to remove a video that is already on blacklist
						}
					}
				}
			}
			// no duplicate found. add!
			$this->mData['lists'][$list] = array_merge($this->mData['lists'][$list], $entries);
		}
		else {
			$this->mData['lists'][$list] = $entries;
			$newEntry = end( $entries );
		}

		// managing WikiActivity
		$oTmpTitle = F::build( 'Title', array( $newEntry['title'], NS_VIDEO), 'newFromText');
		if ( $list == self::WHITELIST_MARKER ){
			$summary = wfMsg('related-videos-wiki-summary-whitelist', array( $oTmpTitle->getText(), $oTmpTitle->getFullText() ));
		} else {
			$summary = wfMsg('related-videos-wiki-summary-blacklist', array( $oTmpTitle->getText(), $oTmpTitle->getFullText() ));
		}
		
		// next, remove from other list
		if ($list == self::WHITELIST_MARKER) {
			$otherList = self::BLACKLIST_MARKER;
		}
		else {
			$otherList = self::WHITELIST_MARKER;
		}
		
		if (!empty($this->mData['lists'][$otherList])) {
			foreach ($this->mData['lists'][$otherList] as $key=>&$entry) {
				foreach ($entries as $newEntry) {
					if ($newEntry['title'] == $entry['title']
					&& $newEntry['source'] == $entry['source']) {
						unset($this->mData['lists'][$otherList][$key]);
					}
				}
			}
			$this->mData['lists'][$otherList] = array_values($this->mData['lists'][$otherList]);
		}
		
		$content = $this->serialize();
		
		if ($this->mId) {
			$title = F::build('Title', array($this->mId), 'newFromID');
		}
		elseif ($this->mTitle) {
			$title = $this->mTitle;
		}
		else {
			wfProfileOut( __METHOD__ );
			return wfMsg('related-videos-error-unknown', 76543);
		}

		$article = F::build('Article', array($title));

		if ( empty( $summary )){
			$summary = wfMsg( 'related-videos-updated', array( ucfirst(strtolower($list)) ) );
		}

		$status = $article->doEdit($content, $summary, $this->exists() ? EDIT_UPDATE : EDIT_NEW, false, F::app()->wg->user);
		$this->purge();	// probably unnecessary b/c a hook does this, but can't hurt

		wfProfileOut( __METHOD__ );
		return $status;
	}
	
	public function createEntry($title, $isFromVideoWiki) {

		wfProfileIn( __METHOD__ );
		$entry = array();
		if (startsWith($title, F::app()->wg->Lang->getNsText(NS_VIDEO))) {
			$title = substr($title, strlen(F::app()->wg->Lang->getNsText(NS_VIDEO).':'));
		}
		$entry['title'] = $title;
		$entry['source'] = ($isFromVideoWiki ? F::app()->wg->WikiaVideoRepoDBName : '');

		wfProfileOut( __METHOD__ );
		return $entry;
	}
}