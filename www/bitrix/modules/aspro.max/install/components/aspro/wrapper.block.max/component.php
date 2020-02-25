<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
?>
<?global $arTheme, $APPLICATION;?>
<?
$bError = false;

/*Prepare params*/
$arParams["ELEMENT_COUNT"] = ($arParams["ELEMENT_COUNT"] ? $arParams["ELEMENT_COUNT"] : 5);
$arParams['FILTER_NAME'] = ($arParams['FILTER_NAME'] ? $arParams['FILTER_NAME'] : 'arFilterWrapper');
/**/

$arParams["COMPONENT_NAME"] = $componentName;
$arParams["TEMPLATE"] = $componentTemplate;

$arParams["IS_AJAX"] = CMax::checkAjaxRequest();
$arParams["USE_FAST_VIEW"] = CMax::GetFrontParametrValue('USE_FAST_VIEW_PAGE_DETAIL');

$context=\Bitrix\Main\Application::getInstance()->getContext();
$request=$context->getRequest();

/*fix global filter in ajax*/
if($_SESSION['ASPRO_FILTER'][$arParams['FILTER_NAME']])
	$GLOBALS[$arParams['FILTER_NAME']] = $_SESSION['ASPRO_FILTER'][$arParams['FILTER_NAME']];

if($arParams['FILTER_NAME'] == 'arrPopularSections')
	$GLOBALS[$arParams['FILTER_NAME']] = array('UF_POPULAR' => 1);

/**/

if($arParams['IS_AJAX'] == 'Y')
{
	// $APPLICATION->ShowCss();
	// $APPLICATION->ShowHeadScripts();
	$APPLICATION->ShowAjaxHead();
}

if(!$bError && \Bitrix\Main\Loader::includeModule('aspro.max'))
{
	if($arParams["IS_AJAX"] != 'Y')
	{
		$this->IncludeComponentTemplate();
	}
	else
	{
		global $arRegion;
		$arRegion = CMaxRegionality::getCurrentRegion();

		if($arRegion)
		{
			if($arRegion['LIST_PRICES'])
			{
				if(reset($arRegion['LIST_PRICES']) != 'component')
					$arParams['PRICE_CODE'] = array_keys($arRegion['LIST_PRICES']);
			}
			if($arRegion['LIST_STORES'])
			{
				if(reset($arRegion['LIST_STORES']) != 'component')
					$arParams['STORES'] = $arRegion['LIST_STORES'];
			}

			if($arParams['FILTER_NAME'] == 'arRegionality' && $arParams['STORES'])
			{
				if(CMax::GetFrontParametrValue('STORES_SOURCE') != 'IBLOCK')
					$GLOBALS[$arParams['FILTER_NAME']] = array('ID' => $arRegion['LIST_STORES']);
				else
					$GLOBALS[$arParams['FILTER_NAME']] = array('PROPERTY_STORE_ID' => $arRegion['LIST_STORES']);
			}
		}
		$this->IncludeComponentTemplate('ajax');
	}
}
?>