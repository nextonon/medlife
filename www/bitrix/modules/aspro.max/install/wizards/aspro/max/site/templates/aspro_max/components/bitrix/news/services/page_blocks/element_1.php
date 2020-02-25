<?$APPLICATION->IncludeComponent(
	"bitrix:news.detail",
	"news",
	Array(
		"S_ASK_QUESTION" => $arParams["S_ASK_QUESTION"],
		"S_ORDER_SERVISE" => $arParams["S_ORDER_SERVISE"],
		"T_GALLERY" => $arParams["T_GALLERY"],
		"T_DOCS" => $arParams["T_DOCS"],
		"T_GOODS" => $arParams["T_GOODS"],
		"T_SERVICES" => $arParams["T_SERVICES"],
		"T_PROJECTS" => $arParams["T_PROJECTS"],
		"T_REVIEWS" => $arParams["T_REVIEWS"],
		"T_STAFF" => $arParams["T_STAFF"],
		"T_VIDEO" => $arParams["T_VIDEO"],
		"FORM_ID_ORDER_SERVISE" => ($arParams["FORM_ID_ORDER_SERVISE"] ? $arParams["FORM_ID_ORDER_SERVISE"] : 'SERVICES'),
		"DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
		"DISPLAY_NAME" => $arParams["DISPLAY_NAME"],
		"DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
		"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"FIELD_CODE" => $arParams["DETAIL_FIELD_CODE"],
		"PROPERTY_CODE" => $arParams["DETAIL_PROPERTY_CODE"],
		"DETAIL_URL"	=>	$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["detail"],
		"SECTION_URL"	=>	$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"META_KEYWORDS" => $arParams["META_KEYWORDS"],
		"META_DESCRIPTION" => $arParams["META_DESCRIPTION"],
		"BROWSER_TITLE" => $arParams["BROWSER_TITLE"],
		"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
		"SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
		"ADD_SECTIONS_CHAIN" => $arParams["ADD_SECTIONS_CHAIN"],
		"ADD_ELEMENT_CHAIN" => $arParams["ADD_ELEMENT_CHAIN"],
		"ACTIVE_DATE_FORMAT" => $arParams["DETAIL_ACTIVE_DATE_FORMAT"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
		"GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
		"DISPLAY_TOP_PAGER" => $arParams["DETAIL_DISPLAY_TOP_PAGER"],
		"DISPLAY_BOTTOM_PAGER" => $arParams["DETAIL_DISPLAY_BOTTOM_PAGER"],
		"PAGER_TITLE" => $arParams["DETAIL_PAGER_TITLE"],
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => $arParams["DETAIL_PAGER_TEMPLATE"],
		"PAGER_SHOW_ALL" => $arParams["DETAIL_PAGER_SHOW_ALL"],
		"CHECK_DATES" => $arParams["CHECK_DATES"],
		"ELEMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
		"ELEMENT_CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
		"IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["news"],
		"USE_SHARE" 			=> $arParams["USE_SHARE"],
		"SHARE_HIDE" 			=> $arParams["SHARE_HIDE"],
		"SHARE_TEMPLATE" 		=> $arParams["SHARE_TEMPLATE"],
		"SHARE_HANDLERS" 		=> $arParams["SHARE_HANDLERS"],
		"SHARE_SHORTEN_URL_LOGIN"	=> $arParams["SHARE_SHORTEN_URL_LOGIN"],
		"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
		"GALLERY_TYPE" => $arParams["GALLERY_TYPE"],
		"DETAIL_USE_COMMENTS" => $arParams["DETAIL_USE_COMMENTS"],
		"DETAIL_BLOG_USE" => $arParams["DETAIL_BLOG_USE"],
		"DETAIL_BLOG_URL" => $arParams["DETAIL_BLOG_URL"],
		"DETAIL_BLOG_EMAIL_NOTIFY" => $arParams["DETAIL_BLOG_EMAIL_NOTIFY"],
		"DETAIL_VK_USE" => $arParams["DETAIL_VK_USE"],
		"DETAIL_VK_API_ID" => $arParams["DETAIL_VK_API_ID"],
		"DETAIL_FB_USE" => $arParams["DETAIL_FB_USE"],
		"DETAIL_FB_APP_ID" => $arParams["DETAIL_FB_APP_ID"],
		"COMMENTS_COUNT" => $arParams["COMMENTS_COUNT"],
		"BLOG_TITLE" => $arParams["BLOG_TITLE"],
		"VK_TITLE" => $arParams["VK_TITLE"],
		"FB_TITLE" => $arParams["FB_TITLE"],
		"STAFF_TYPE" => $arParams["STAFF_TYPE_DETAIL"],
		"IBLOCK_LINK_NEWS_ID" => $arParams["IBLOCK_LINK_NEWS_ID"],
		"IBLOCK_LINK_BLOG_ID" => $arParams["IBLOCK_LINK_BLOG_ID"],
		"IBLOCK_LINK_SERVICES_ID" => $arParams["IBLOCK_LINK_SERVICES_ID"],
	    	"IBLOCK_LINK_TIZERS_ID" => $arParams["IBLOCK_LINK_TIZERS_ID"],
		"IBLOCK_LINK_REVIEWS_ID" => $arParams["IBLOCK_LINK_REVIEWS_ID"],
		"IBLOCK_LINK_STAFF_ID" => $arParams["IBLOCK_LINK_STAFF_ID"],
		"IBLOCK_LINK_VACANCY_ID" => $arParams["IBLOCK_LINK_VACANCY_ID"],
		"IBLOCK_LINK_PROJECTS_ID" => $arParams["IBLOCK_LINK_PROJECTS_ID"],
		"IBLOCK_LINK_BRANDS_ID" => $arParams["IBLOCK_LINK_BRANDS_ID"],
		"IBLOCK_LINK_LANDINGS_ID" => $arParams["IBLOCK_LINK_LANDINGS_ID"],
		"IBLOCK_LINK_PARTNERS_ID" => $arParams["IBLOCK_LINK_PARTNERS_ID"],
		"BLOCK_SERVICES_NAME" => $arParams["BLOCK_SERVICES_NAME"],
		"BLOCK_NEWS_NAME" => $arParams["BLOCK_NEWS_NAME"],
		"BLOCK_BLOG_NAME" => $arParams["BLOCK_BLOG_NAME"],
	    	"BLOCK_TIZERS_NAME" => $arParams["BLOCK_TIZERS_NAME"],
		"BLOCK_REVIEWS_NAME" => $arParams["BLOCK_REVIEWS_NAME"],
		"BLOCK_STAFF_NAME" => $arParams["BLOCK_STAFF_NAME"],
		"BLOCK_VACANCY_NAME" => $arParams["BLOCK_VACANCY_NAME"],
		"BLOCK_PROJECTS_NAME" => $arParams["BLOCK_PROJECTS_NAME"],
		"BLOCK_BRANDS_NAME" => $arParams["BLOCK_BRANDS_NAME"],
		"BLOCK_LANDINGS_NAME" => $arParams["BLOCK_LANDINGS_NAME"],
		"BLOCK_PARTNERS_NAME" => $arParams["BLOCK_PARTNERS_NAME"],
		"DETAIL_BLOCKS_ALL_ORDER" => ($arParams["DETAIL_BLOCKS_ALL_ORDER"] ? $arParams["DETAIL_BLOCKS_ALL_ORDER"] : 'tizers,desc,char,docs,services,news,blog,vacancy,reviews,projects,staff,landings,comments'),
		"CONTENT_LINKED_FILTER_BY_FILTER" => ($arTmpGoods['CHILDREN'] ? $arElement['~PROPERTY_LINK_GOODS_FILTER_VALUE']:''),
		"PRICE_CODE" => $arParams["PRICE_CODE"],
		"STORES" => $arParams["STORES"],
		"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
		"DISPLAY_ELEMENT_SLIDER" => $arParams["LINKED_ELEMENST_PAGE_COUNT"],
		"LINKED_ELEMENST_PAGINATION" => $arParams["LINKED_ELEMENST_PAGINATION"],
		"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
		"DETAIL_LINKED_GOODS_SLIDER" => $arParams["DETAIL_LINKED_GOODS_SLIDER"],

		"SHOW_SECTIONS_FILTER" => $arParams["SHOW_SECTIONS_FILTER"],
		"IS_AJAX_SECTIONS" => (strtolower($_REQUEST['ajax_get_sections']) == 'y'),
		"FROM_AJAX" => (CMax::checkAjaxRequest() && strtolower($_REQUEST['ajax']) == 'y'),
		"TAGS_SECTION_COUNT" => $arParams["TAGS_SECTION_COUNT"],
		"DISPLAY_LINKED_PAGER" => ($arParams["DETAIL_LINKED_GOODS_SLIDER"] == "Y") ? "N" : $arParams["DISPLAY_LINKED_PAGER"],
		"MAX_GALLERY_ITEMS" => $arParams["MAX_GALLERY_ITEMS"],
		"SHOW_GALLERY" => isset($arParams["SHOW_GALLERY_GOODS"]) ? $arParams["SHOW_GALLERY_GOODS"] : "N",
		"SHOW_PROPS" => (CMax::GetFrontParametrValue("SHOW_PROPS_BLOCK") == "Y" ? "Y" : "N"),
		'SHOW_POPUP_PRICE' => (CMax::GetFrontParametrValue('SHOW_POPUP_PRICE') == 'Y'),
		"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
		"CURRENCY_ID" => $arParams["CURRENCY_ID"],
		"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
		'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
		"ADD_DETAIL_TO_SLIDER" => $arParams["ADD_DETAIL_TO_SLIDER"],
		"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
		"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
		"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
		"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
		"SHOW_ONE_CLICK_BUY" => $arParams["SHOW_ONE_CLICK_BUY"],
		"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
		//"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
		"SALE_STIKER" => $arParams["SALE_STIKER"],
		"STIKERS_PROP" => $arParams["STIKERS_PROP"],
		"SHOW_RATING" => $arParams["SHOW_RATING"],
		"ADD_PICT_PROP" => $arParams["ADD_PICT_PROP"],
		"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
		"LINKED_PROPERTY_CODE" => $arParams["LINKED_PROPERTY_CODE"],
		"SHOW_COUNT_ELEMENTS" => $arParams["SHOW_COUNT_ELEMENTS"],
	),
	$component
);?>


