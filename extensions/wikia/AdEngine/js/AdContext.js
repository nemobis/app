/*global define*/
/**
 * The AMD module to hold all the context needed for the client-side scripts to run.
 */
define('ext.wikia.adEngine.adContext', ['wikia.window'], function (w, document) {
	'use strict';

	var context = w.ads ? w.ads.context : {
		opts: {
			adsInHead: w.wgLoadAdsInHead,
			disableLateQueue: w.wgAdEngineDisableLateQueue,
			lateAdsAfterPageLoad: w.wgLoadLateAdsAfterPageLoad,
			pageType: w.adEnginePageType,
			showAds: w.wgShowAds,
			usePostScribe: w.wgUsePostScribe,
			trackSlotState: w.wgAdDriverTrackState
		},

		targeting: {
			enableKruxTargeting: w.wgEnableKruxTargeting,
			kruxCategoryId: w.wgKruxCategoryId,

			pageArticleId: w.wgArticleId,
			pageCategories: w.wgAdDriverUseCatParam ? w.wgCategories : [],
			pageIsArticle: w.wgIsArticle,
			pageIsHub: w.wikiaPageIsHub,
			pageName: w.wgPageName,
			pageType: w.wikiaPageType,

			sevenOneMediaSub2Site: w.wgAdDriverSevenOneMediaOverrideSub2Site,
			skin: w.skin,

			wikiCategory: w.cityShort,
			wikiCustomKeyValues: w.wgDartCustomKeyValues,
			wikiDbName: w.wgDBname,
			wikiDirectedAtChildren: w.wgWikiDirectedAtChildren,
			wikiLanguage: w.wgContentLanguage,
			wikiVertical: w.cscoreCat
		},

		providers: {
			ebay: w.wgAdDriverUseEbay,
			sevenOneMedia: w.wgAdDriverUseSevenOneMedia,
			sevenOneMediaCombinedUrl: w.wgAdDriverSevenOneMediaCombinedUrl,
			remnantGptMobile: w.wgAdDriverEnableRemnantGptMobile
		},

		slots: {
			bottomLeaderboardImpressionCapping: w.wgAdDriverBottomLeaderboardImpressionCapping
		},

		// TODO: make it like forceadprovider=liftium
		forceProviders: {
			directGpt: w.wgAdDriverForceDirectGptAd,
			liftium: w.wgAdDriverForceLiftiumAd
		}
	};

	// Always have objects in all categories
	context.opts = context.opts || {};
	context.targeting = context.targeting || {};
	context.providers = context.providers || {};
	context.forceProviders = context.forceProviders || {};

	// Don't show ads when Sony requests the page
	if (document && document.referrer && document.referrer.match(/info\.tvsideview\.sony\.net/)) {
		context.opts.showAds = false;
	}

	// Use PostScribe for ScriptWriter implementation when SevenOne Media ads are enabled
	context.opts.usePostScribe = context.opts.usePostScribe || context.providers.sevenOneMedia;

	return context;
});
