<?
if(!defined('ASPRO_MAX_MODULE_ID'))
	define('ASPRO_MAX_MODULE_ID', 'aspro.max');

use \Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	\Bitrix\Main\Config\Option,
	Bitrix\Main\IO\File,
	Bitrix\Main\Page\Asset;
Loc::loadMessages(__FILE__);

class CMaxEvents{
	const moduleID = ASPRO_MAX_MODULE_ID;
	const partnerName	= "aspro";
    const solutionName	= "max";
    const wizardID		= "aspro:max";

	function ShowPanel(){
    }

	function BeforeSendEvent(\Bitrix\Main\Event $event){
		if(isset($_REQUEST["ONE_CLICK_BUY"]) && method_exists('\Bitrix\Sale\Compatible\EventCompatibility', 'setDisableMailSend')){
			\Bitrix\Sale\Compatible\EventCompatibility::setDisableMailSend(true);
			if(method_exists('\Bitrix\Sale\Notify', 'setNotifyDisable'))
				\Bitrix\Sale\Notify::setNotifyDisable(true);
		}
	}

	public static function OnBeforeEventAddHandler(&$event, &$lid, &$arFields, &$message_id){
		if(\Bitrix\Main\Loader::includeModule(self::moduleID))
		{
			if($arCurrentRegion = CMaxRegionality::getCurrentRegion())
			{
				$arFields['REGION_ID'] = $arCurrentRegion['ID'];
				$arFields['REGION_MAIN_DOMAIN'] = $arCurrentRegion['PROPERTY_MAIN_DOMAIN_VALUE'];
				$arFields['REGION_MAIN_DOMAIN_RAW'] = (CMain::IsHTTPS() ? 'https://' : 'http://').$arCurrentRegion['PROPERTY_MAIN_DOMAIN_VALUE'];
				$arFields['REGION_ADDRESS'] = $arCurrentRegion['PROPERTY_ADDRESS_VALUE']['TEXT'];
				$arFields['REGION_EMAIL'] = implode(', ', $arCurrentRegion['PROPERTY_EMAIL_VALUE']);
				$arFields['REGION_PHONE'] = implode(', ', $arCurrentRegion['PHONES']);

				$arTagSeoMarks = array();
				foreach($arCurrentRegion as $key => $value)
				{
					if(strpos($key, 'PROPERTY_REGION_TAG') !== false && strpos($key, '_VALUE_ID') === false)
					{
						$tag_name = str_replace(array('PROPERTY_', '_VALUE'), '', $key);
						$arTagSeoMarks['#'.$tag_name.'#'] = $key;
					}
				}

				if($arTagSeoMarks)
					CMaxRegionality::addSeoMarks($arTagSeoMarks);

				foreach(CMaxRegionality::$arSeoMarks as $mark => $field)
				{
					$mark = str_replace('#', '', $mark);
					if(is_array($arCurrentRegion[$field]))
						$arFields[$mark] = $arCurrentRegion[$field]['TEXT'];
					else
						$arFields[$mark] = $arCurrentRegion[$field];
				}
			}
		}
	}

	public static function fixRegionMailFields(&$arFields, $regionID = null){
		$arCurrentRegion = array();
	}

	function OnFindSocialservicesUserHandler($arFields){
		// check for user with email
		if($arFields['EMAIL'])
		{
			$arUser = CUser::GetList($by = 'ID', $ord = 'ASC', array('EMAIL' => $arFields['EMAIL'], 'ACTIVE' => 'Y'), array('NAV_PARAMS' => array("nTopCount" => "1")))->fetch();
			if($arUser)
			{
				if($arFields['PERSONAL_PHOTO'])
				{

					/*if(!$arUser['PERSONAL_PHOTO'])
					{
						$arUpdateFields = Array(
							'PERSONAL_PHOTO' => $arFields['PERSONAL_PHOTO'],
						);
						$user->Update($arUser['ID'], $arUpdateFields);
					}
					else
					{*/
						$code = 'UF_'.strtoupper($arFields['EXTERNAL_AUTH_ID']);
						$arUserFieldUserImg = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => $code))->Fetch();
						if(!$arUserFieldUserImg)
						{
							$arFieldsUser = array(
								"FIELD_NAME" => $code,
								"USER_TYPE_ID" => "file",
								"XML_ID" => $code,
								"SORT" => 100,
								"MULTIPLE" => "N",
								"MANDATORY" => "N",
								"SHOW_FILTER" => "N",
								"SHOW_IN_LIST" => "Y",
								"EDIT_IN_LIST" => "Y",
								"IS_SEARCHABLE" => "N",
								"SETTINGS" => array(
									"DISPLAY" => "LIST",
									"LIST_HEIGHT" => 5,
								)
							);
							$arLangs = array(
								"EDIT_FORM_LABEL" => array(
									"ru" => $code,
									"en" => $code,
								),
								"LIST_COLUMN_LABEL" => array(
									"ru" => $code,
									"en" => $code,
								)
							);

							$ob = new CUserTypeEntity();
							$FIELD_ID = $ob->Add(array_merge($arFieldsUser, array('ENTITY_ID' => 'USER'), $arLangs));

						}
						$user = new CUser;
						$arUpdateFields = Array(
							$code => $arFields['PERSONAL_PHOTO'],
						);
						$user->Update($arUser['ID'], $arUpdateFields);
					//}
				}
				return $arUser['ID'];
			}
		}
		return false;
	}

	function OnAfterSocServUserAddHandler( $arFields ){
		if($arFields["EMAIL"]){
			global $USER;
			$userEmail=$USER->GetEmail();
			$email=(is_null($userEmail) ? $arFields["EMAIL"] : $userEmail );
			//$resUser = CUser::GetList(($by="ID"), ($order="asc"), array("=EMAIL" => $arFields["EMAIL"]), array("FIELDS" => array("ID")));
			$resUser = CUser::GetList(($by="ID"), ($order="asc"), array("=EMAIL" => $email), array("FIELDS" => array("ID")));
			$arUserAlreadyExist = $resUser->Fetch();

			if($arUserAlreadyExist["ID"]){
				\Bitrix\Main\Loader::includeModule('socialservices');
				global $USER;
				if($resUser->SelectedRowsCount()>1){
					CSocServAuthDB::Update($arFields["ID"], array("USER_ID" => $arUserAlreadyExist["ID"], "CAN_DELETE" => "Y"));
					CUser::Delete($arFields["USER_ID"]);
					$USER->Authorize($arUserAlreadyExist["ID"]);
				}else{
					$def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");
					if($def_group!=""){
						$GROUP_ID = explode(",", $def_group);
						$arPolicy = $USER->GetGroupPolicy($GROUP_ID);
					}else{
						$arPolicy = $USER->GetGroupPolicy(array());
					}
					$password_min_length = (int)$arPolicy["PASSWORD_LENGTH"];
					if($password_min_length <= 0)
						$password_min_length = 6;
					$password_chars = array(
						"abcdefghijklnmopqrstuvwxyz",
						"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
						"0123456789",
					);
					if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
						$password_chars[] = ",.<>/?;:'\"[]{}\|`~!@#\$%^&*()-_+=";
					$NEW_PASSWORD = $NEW_PASSWORD_CONFIRM = randString($password_min_length+2, $password_chars);

					$user = new CUser;
					$arFieldsUser = Array(
					  "NAME"              => $arFields["NAME"],
					  "LAST_NAME"         => $arFields["LAST_NAME"],
					  "EMAIL"             => $arFields["EMAIL"],
					  "LOGIN"             => $arFields["EMAIL"],
					  "GROUP_ID"          => $GROUP_ID,
					  "PASSWORD"          => $NEW_PASSWORD,
					  "CONFIRM_PASSWORD"  => $NEW_PASSWORD_CONFIRM,
					);
					unset($arFields["LOGIN"]);
					unset($arFields["PASSWORD"]);
					unset($arFields["EXTERNAL_AUTH_ID"]);
					unset($arFields["XML_ID"]);
					$arAddFields = array();
					$arAddFields = array_merge($arFieldsUser, $arFields);
					if(isset($arAddFields["PERSONAL_PHOTO"]) && $arAddFields["PERSONAL_PHOTO"])
					{
						$arPic = CFile::MakeFileArray($arFields["PERSONAL_PHOTO"]);
						$arAddFields["PERSONAL_PHOTO"] = $arPic;
					}

					//if($arUserAlreadyExist["ID"]!=$arFields["USER_ID"]){
						$ID = $user->Add($arAddFields);
						//$ID = $user->Add($arFieldsUser);
						CSocServAuthDB::Update($arFields["ID"], array("USER_ID" => $ID, "CAN_DELETE" => "Y"));
						CUser::Delete($arFields["USER_ID"]);
						$USER->Authorize($ID);
					//}
				}
			}
		}
	}

	function OnSaleComponentOrderProperties(&$arUserResult, $arRequest, $arParams, $arResult){
		if($arUserResult['ORDER_PROP'])
		{
			$arPhoneProp = CSaleOrderProps::GetList(
				array('SORT' => 'ASC'),
				array(
						'PERSON_TYPE_ID' => $arUserResult['PERSON_TYPE_ID'],
						'IS_PHONE' => 'Y',
					),
				false,
				false,
				array()
			)->fetch(); // get phone prop
			if($arPhoneProp && $arParams['USE_PHONE_NORMALIZATION'] !='N')
			{
				global $USER;
				if($arUserResult['ORDER_PROP'][$arPhoneProp['ID']])
				{
					if($_REQUEST['order']['ORDER_PROP_'.$arPhoneProp['ID']])
					{
						$arUserResult['ORDER_PROP'][$arPhoneProp['ID']] = $_REQUEST['order']['ORDER_PROP_'.$arPhoneProp['ID']];
					}
					else
					{
						if($arUserResult['PROFILE_ID']) //get phone from user profile
						{
							$arUserPropValue = CSaleOrderUserPropsValue::GetList(
								array('ID' => 'ASC'),
								array('USER_PROPS_ID' => $arUserResult['PROFILE_ID'], 'ORDER_PROPS_ID' => $arPhoneProp['ID'])
							)->fetch();
							if($arUserPropValue['VALUE'])
							{
								$arUserResult['ORDER_PROP'][$arPhoneProp['ID']] = $arUserPropValue['VALUE'];
							}
						}
						elseif($USER->isAuthorized()) //get phone from user field
						{
							$rsUser = CUser::GetByID($USER->GetID());
							if($arUser = $rsUser->Fetch())
							{
								if(!empty($arUser['PERSONAL_PHONE']))
								{
									$value = $arUser['PERSONAL_PHONE'];
								}
								elseif(!empty($arUser['PERSONAL_MOBILE']))
								{
									$value = $arUser['PERSONAL_MOBILE'];
								}
							}
							if($value)
								$arUserResult['ORDER_PROP'][$arPhoneProp['ID']] = $value;
						}
						if($arUserResult['ORDER_PROP'][$arPhoneProp['ID']]) // add + mark for correct mask
						{
							$mask = \Bitrix\Main\Config\Option::get('aspro.max', 'PHONE_MASK', '+7 (999) 999-99-99');
							if(strpos($arUserResult['ORDER_PROP'][$arPhoneProp['ID']], '+') === false && strpos($mask, '+') !== false)
							{
								$arUserResult['ORDER_PROP'][$arPhoneProp['ID']] = '+'.$arUserResult['ORDER_PROP'][$arPhoneProp['ID']];
							}
						}
					}
				}
			}
		}
	}

	function OnSaleComponentOrderOneStepComplete($ID, $arOrder, $arParams){
		$arOrderProps = array();
		$resOrder = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $ID));
		while($item = $resOrder->fetch())
		{
			$arOrderProps[$item['CODE']] = $item;
		}
		$arPhoneProp = CSaleOrderProps::GetList(
			array('SORT' => 'ASC'),
			array(
					'PERSON_TYPE_ID' => $arOrder['PERSON_TYPE_ID'],
					'IS_PHONE' => 'Y',
				),
			false,
			false,
			array()
		)->fetch(); // get phone prop
		if($arPhoneProp && $arParams['USE_PHONE_NORMALIZATION'] !='N')
		{
			if($arOrderProps[$arPhoneProp['CODE']])
			{
				if($arOrderProps[$arPhoneProp['CODE']]['VALUE'])
				{
					if($_REQUEST['ORDER_PROP_'.$arOrderProps[$arPhoneProp['CODE']]['ORDER_PROPS_ID']])
					{
						CSaleOrderPropsValue::Update($arOrderProps[$arPhoneProp['CODE']]['ID'], array('VALUE'=>$_REQUEST['ORDER_PROP_'.$arOrderProps[$arPhoneProp['CODE']]['ORDER_PROPS_ID']])); // set phone order prop
						$arUserProps = CSaleOrderUserProps::GetList(
							array('DATE_UPDATE' => 'DESC'),
							array('USER_ID' => $arOrder['USER_ID'], 'PERSON_TYPE_ID' => $arOrder['PERSON_TYPE_ID'])
						)->fetch(); // get user profile info

						if($arUserProps)
						{
							$arUserPropValue = CSaleOrderUserPropsValue::GetList(
								array('ID' => 'ASC'),
								array('USER_PROPS_ID' => $arUserProps['ID'], 'ORDER_PROPS_ID' => $arOrderProps[$arPhoneProp['CODE']]['ORDER_PROPS_ID'])
							)->fetch(); // get phone from user prop
							if($arUserPropValue['VALUE'])
							{
								CSaleOrderUserPropsValue::Update($arUserPropValue['ID'], array('VALUE'=>$_REQUEST['ORDER_PROP_'.$arOrderProps[$arPhoneProp['CODE']]['ORDER_PROPS_ID']])); //set phone in user profile
							}
						}
					}
				}
			}
		}
	}

	function correctInstall(){
		if(COption::GetOptionString(self::moduleID, "WIZARD_DEMO_INSTALLED") == "Y"){
			if(CModule::IncludeModule("main")){
				require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/wizard.php");
				@set_time_limit(0);
				/*if(!CWizardUtil::DeleteWizard(self::wizardID)){if(!DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".self::partnerName."/".self::solutionName."/")){self::removeDirectory($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".self::partnerName."/".self::solutionName."/");}}*/
				UnRegisterModuleDependences("main", "OnBeforeProlog", self::moduleID, get_class(), "correctInstall");
				COption::SetOptionString(self::moduleID, "WIZARD_DEMO_INSTALLED", "N");
			}
		}
	}

	function OnBeforeUserUpdateHandler(&$arFields){
		$bTmpUser = false;
		$bAdminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);

		if(strlen($arFields["NAME"]))
			$arFields["NAME"] = trim($arFields["NAME"]);

		$siteID = SITE_ID;

		if($bAdminSection)
	    {
	    	// include CMainPage
	        require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
	        // get site_id by host
	        $CMainPage = new \CMainPage();
	        $siteID = $CMainPage->GetSiteByHost();
	        if(!$siteID)
	            $siteID = "s1";

			$sOneFIO = COption::GetOptionString(ASPRO_MAX_MODULE_ID, 'PERSONAL_ONEFIO', 'Y', $siteID);
			$sChangeLogin = COption::GetOptionString(ASPRO_MAX_MODULE_ID, 'LOGIN_EQUAL_EMAIL', 'Y', $siteID);
        }
		else
		{
			$arFrontParametrs = CMax::GetFrontParametrsValues($siteID);
			$sOneFIO = $arFrontParametrs['PERSONAL_ONEFIO'];
			$sChangeLogin = $arFrontParametrs['LOGIN_EQUAL_EMAIL'];
		}

		if(isset($arFields["NAME"])){
			$arFields["NAME"] = trim($arFields["NAME"]);
		}
		if(isset($arFields["LAST_NAME"])){
			$arFields["LAST_NAME"] = trim($arFields["LAST_NAME"]);
		}
		if(isset($arFields["SECOND_NAME"])){
			$arFields["SECOND_NAME"] = trim($arFields["SECOND_NAME"]);
		}

		if(strlen($arFields["NAME"]) && !strlen($arFields["LAST_NAME"]) && !strlen($arFields["SECOND_NAME"])){
			if($sOneFIO !== 'N')
			{
				$arName = explode(' ', $arFields["NAME"]);

				if($arName){
					$arFields["NAME"] = "";
					$arFields["SECOND_NAME"] = "";
					foreach($arName as $i => $name){
						if(!$i){
							$arFields["LAST_NAME"] = $name;
						}
						else{
							if(!strlen($arFields["NAME"])){
								$arFields["NAME"] = $name;
							}
							elseif(!strlen($arFields["SECOND_NAME"])){
								$arFields["SECOND_NAME"] = $name;
							}
						}
					}
				}
			}
		}
		if($_REQUEST["confirmorder"]=="Y"  && !strlen($arFields["SECOND_NAME"]) && $_REQUEST["ORDER_PROP_1"]){
			$arNames = explode(' ', $_REQUEST["ORDER_PROP_1"]);
			if($arNames[2]){
				$arFields["SECOND_NAME"]=$arNames[2];
			}
		}

		if(isset($_REQUEST["soa-action"]) && $_REQUEST["soa-action"] == "saveOrderAjax") // set correct phone in user field
		{
			$arPhoneProp = CSaleOrderProps::GetList(
				array('SORT' => 'ASC'),
				array(
						'PERSON_TYPE_ID' => $_REQUEST['PERSON_TYPE'],
						'IS_PHONE' => 'Y',
					),
				false,
				false,
				array()
			)->fetch();
			if($arPhoneProp)
			{
				if($_REQUEST['ORDER_PROP_'.$arPhoneProp['ID']])
				{
					$arFields["PERSONAL_PHONE"] = $_REQUEST['ORDER_PROP_'.$arPhoneProp['ID']];
				}
			}
		}

		if(strlen($arFields["EMAIL"]))
		{
			if($sChangeLogin != "N")
			{
				$bEmailError = false;

				if(\Bitrix\Main\Config\Option::get('main', 'new_user_email_uniq_check', 'N') == 'Y')
				{
					$rsUser = CUser::GetList($by = "ID", $order = "ASC", array("=EMAIL" => $arFields["EMAIL"], "!ID" => $arFields["ID"]));
					if(!$bEmailError = intval($rsUser->SelectedRowsCount()) > 0)
					{
						$rsUser = CUser::GetList($by = "ID", $order = "ASC", array("LOGIN_EQUAL" => $arFields["EMAIL"], "!ID" => $arFields["ID"]));
						$bEmailError = intval($rsUser->SelectedRowsCount()) > 0;
					}
				}

				if($bEmailError){
					global $APPLICATION;
					$APPLICATION->throwException(Loc::getMessage("EMAIL_IS_ALREADY_EXISTS", array("#EMAIL#" => $arFields["EMAIL"])));
					return false;
				}
				else{
					// !admin
					if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"])){
						$bTmpUser = True;
						$GLOBALS["USER"] = new \CUser;
					}

					if($bAdminSection)
					{
						if(isset($arFields['ID']) && $arFields['ID'])
						{
							if(!in_array(1, CUser::GetUserGroup($arFields['ID'])))
								$arFields['LOGIN'] = $arFields['EMAIL'];
						}
						elseif(isset($arFields['GROUP_ID']) && $arFields['GROUP_ID'])
						{
							$arUserGroups = array();
							$arTmpGroups = (array)$arFields['GROUP_ID'];
							foreach($arTmpGroups as $arGroup)
							{
								if(is_array($arGroup))
									$arUserGroups[] = $arGroup['GROUP_ID'];
								else
									$arUserGroups[] = $arGroup;
							}

							if(count(array_intersect($arUserGroups, array(1)))<=0)
								$arFields['LOGIN'] = $arFields['EMAIL'];
						}
						else
							$arFields['LOGIN'] = $arFields['EMAIL'];
					}
					else
					{
						if(!$GLOBALS['USER']->IsAdmin())
							$arFields["LOGIN"] = $arFields["EMAIL"];
					}
				}
			}
			else
			{
				if(!$arFields["LOGIN"] || $arFields["LOGIN"] == 1)
				{
					$newLogin = $arFields['EMAIL'];
					$pos = strpos($newLogin, '@');
					if ($pos !== false)
						$newLogin = substr($newLogin, 0, $pos);

					if (strlen($newLogin) > 47)
						$newLogin = substr($newLogin, 0, 47);

					if (strlen($newLogin) < 3)
						$newLogin .= '_';

					if (strlen($newLogin) < 3)
						$newLogin .= '_';
					$arFields["LOGIN"] = $newLogin;
				}
			}
		}

		if ($bTmpUser)
			unset($GLOBALS["USER"]);

		return $arFields;
	}

	static function InsertCounters(&$html){
	}

	static function clearBasketCacheHandler($orderID, $arFields, $arParams = array()){
		CMaxCache::ClearCacheByTag('sale_basket');
		unset($_SESSION['ASPRO_BASKET_COUNTERS']);
		if(isset($arFields) && $arFields)
		{
			if(isset($arFields["ID"]) && $arFields["ID"])
			{
				\Bitrix\Main\Loader::includeModule("sale");
				global $USER;
				$USER_ID = ($USER_ID = $USER->GetID()) ? $USER_ID : 0;
				$arUser = $arUser = CMaxCache::CUser_GetList(array("SORT" => "ASC", "CACHE" => array("MULTI" => "N", "TAG" => CMaxCache::GetUserCacheTag($USER_ID))), array("ID" => $USER_ID), array("FIELDS" => array("ID", "PERSONAL_PHONE")));
				if(!$arUser["PERSONAL_PHONE"])
				{
					$rsOrder = CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $arFields["ID"]));
					$arOrderProps = array();
					while($item = $rsOrder->Fetch())
					{
						$arOrderProps[$item["CODE"]] = $item;
					}
					if(isset($arOrderProps["PHONE"]) && $arOrderProps["PHONE"] && (isset($arOrderProps["PHONE"]["VALUE"]) && $arOrderProps["PHONE"]["VALUE"]))
					{
						$user = new CUser;
						$fields = Array(
							"PERSONAL_PHONE" => $arOrderProps["PHONE"]["VALUE"],
						);
						$user->Update($arUser["ID"], $fields);
					}

				}
			}
		}
	}

	static function DoIBlockAfterSave($arg1, $arg2 = false){
		$ELEMENT_ID = false;
		$IBLOCK_ID = false;
		$OFFERS_IBLOCK_ID = false;
		$OFFERS_PROPERTY_ID = false;
		if (CModule::IncludeModule('currency'))
			$strDefaultCurrency = CCurrency::GetBaseCurrency();

		//Check for catalog event
		if(is_array($arg2) && $arg2["PRODUCT_ID"] > 0){
			//Get iblock element
			$rsPriceElement = CIBlockElement::GetList(
				array(),
				array(
					"ID" => $arg2["PRODUCT_ID"],
				),
				false,
				false,
				array("ID", "IBLOCK_ID")
			);
			if($arPriceElement = $rsPriceElement->Fetch()){
				$arCatalog = CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);
				if(is_array($arCatalog)){
					//Check if it is offers iblock
					if($arCatalog["OFFERS"] == "Y"){
						//Find product element
						$rsElement = CIBlockElement::GetProperty(
							$arPriceElement["IBLOCK_ID"],
							$arPriceElement["ID"],
							"sort",
							"asc",
							array("ID" => $arCatalog["SKU_PROPERTY_ID"])
						);
						$arElement = $rsElement->Fetch();
						if($arElement && $arElement["VALUE"] > 0)
						{
							$ELEMENT_ID = $arElement["VALUE"];
							$IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
							$OFFERS_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
							$OFFERS_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
						}
					}
					//or iblock which has offers
					elseif($arCatalog["OFFERS_IBLOCK_ID"] > 0){
						$ELEMENT_ID = $arPriceElement["ID"];
						$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
						$OFFERS_IBLOCK_ID = $arCatalog["OFFERS_IBLOCK_ID"];
						$OFFERS_PROPERTY_ID = $arCatalog["OFFERS_PROPERTY_ID"];
					}
					//or it's regular catalog
					else{
						$ELEMENT_ID = $arPriceElement["ID"];
						$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
						$OFFERS_IBLOCK_ID = false;
						$OFFERS_PROPERTY_ID = false;
					}
				}
			}
		}
		//Check for iblock event
		elseif(is_array($arg1) && $arg1["ID"] > 0 && $arg1["IBLOCK_ID"] > 0){
			$IBLOCK_ID = $arg1["IBLOCK_ID"];

			//Check if iblock has offers
			$arOffers = CIBlockPriceTools::GetOffersIBlock($arg1["IBLOCK_ID"]);
			if(is_array($arOffers)){
				$ELEMENT_ID = $arg1["ID"];
				$OFFERS_IBLOCK_ID = $arOffers["OFFERS_IBLOCK_ID"];
				$OFFERS_PROPERTY_ID = $arOffers["OFFERS_PROPERTY_ID"];
			}
			else{
				if(\Aspro\Max\SearchQuery::isLandingSearchIblock($IBLOCK_ID)){
					$arLandingSearchMetaHash =
					$arLandingSearchMetaData =
					$arLandingSearchQuery = array();
					$urlCondition = $queryReplacement = $queryExample = '';

					$dbRes = CIBlockElement::GetProperty(
						$IBLOCK_ID,
						$arg1['ID'],
						array('id' => 'asc'),
						array('CODE' => 'QUERY')
					);
					while($arSeoSearchElementQuery = $dbRes->Fetch()){
						if(strlen($query = trim($arSeoSearchElementQuery['VALUE']))){
							list($query, $hash, $arData) = \Aspro\Max\SearchQuery::getSentenceMeta($query);
							$arLandingSearchQuery[] = $query;
							$arLandingSearchMetaHash[] = $hash;
							$arLandingSearchMetaData[] = serialize($arData);
						}
					}

					// get value of property QUERY_REPLACEMENT
					$dbRes = CIBlockElement::GetProperty(
						$IBLOCK_ID,
						$arg1['ID'],
						array('id' => 'asc'),
						array('CODE' => 'QUERY_REPLACEMENT')
					);
					$arPropertyQueryReplacement = $dbRes->Fetch();
					$queryReplacement = trim($arPropertyQueryReplacement['VALUE']);

					if($arLandingSearchQuery){
						if(strlen($queryExample = \Aspro\Max\SearchQuery::getSentenceExampleQuery(reset($arLandingSearchQuery), LANG))){
							// check value of property URL_CONDITION
							$dbRes = CIBlockElement::GetProperty(
								$IBLOCK_ID,
								$arg1['ID'],
								array('id' => 'asc'),
								array('CODE' => 'URL_CONDITION')
							);
							if($arPropertyUrlCondition = $dbRes->Fetch()){
								$urlCondition = ltrim(trim($arPropertyUrlCondition['VALUE']), '/');
							}
						}
					}

					$arUpdateFields = array(
						'QUERY' => $arLandingSearchQuery,
						'META_HASH' => $arLandingSearchMetaHash,
						'META_DATA' => $arLandingSearchMetaData,
						'URL_CONDITION' => strlen($urlCondition) ? '/'.$urlCondition : '',
						'QUERY_REPLACEMENT' => $queryReplacement,
					);

					// clear multiple properties values for correct values order
					CIBlockElement::SetPropertyValuesEx(
						$arg1['ID'],
						$IBLOCK_ID,
						array(
							'QUERY' => false,
							'META_HASH' => false,
							'META_DATA' => false,
						)
					);

					// update values
					CIBlockElement::SetPropertyValuesEx(
						$arg1['ID'],
						$IBLOCK_ID,
						$arUpdateFields
					);

					if(CMaxCache::$arIBlocksInfo[$IBLOCK_ID]){
						$arSitesLids = CMaxCache::$arIBlocksInfo[$IBLOCK_ID]['LID'];

						// search and remove urlrewrite item
						$searchRule = 'ls='.$arg1['ID'];
						$searchCondition = strlen($urlCondition) ? '#^/'.$urlCondition.'#' : false;
						foreach($arSitesLids as $siteId){
							if($arUrlRewrites = \Bitrix\Main\UrlRewriter::getList($siteId, array('ID' => ''))){
								foreach($arUrlRewrites as $arUrlRewrite){
									if($arUrlRewrite['RULE'] && strpos($arUrlRewrite['RULE'], $searchRule) !== false){
										\Bitrix\Main\UrlRewriter::delete($siteId, array('CONDITION' => $arUrlRewrite['CONDITION']));
									}

									if($searchCondition && $arUrlRewrite['CONDITION'] === $searchCondition){
										\Bitrix\Main\UrlRewriter::delete($siteId, array('CONDITION' => $arUrlRewrite['CONDITION']));
									}
								}
							}
						}

						// add new urlrewrite condition item
						if(strlen($urlCondition)){
							$cntActive = CIBlockElement::GetList(
								array(),
								array(
									'ID' => $arg1['ID'],
									'ACTIVE' => 'Y',
								),
								array()
							);

							if($cntActive){
								static $arCacheSites;
								if(!isset($arCacheSites)){
									$arCacheSites = array();
								}

								foreach($arSitesLids as $siteId){
									$arSite = $arCacheSites[$siteId];
									if(!isset($arSite)){
										$dbSite = CSite::GetByID($siteId);
										$arCacheSites[$siteId] = $arSite = $dbSite->Fetch();
									}

									if($arSite){
										$siteDir = $arSite['DIR'];

										// catalog page
										$catalogPage = trim(CMax::GetFrontParametrValue("CATALOG_PAGE_URL", $siteId, false));
										if(!strlen($catalogPage)){
											// catalog iblock id
											if(defined('URLREWRITE_SEARCH_LANDING_CONDITION_CATALOG_IBLOCK_ID_'.$siteId)){
												$catalogIblockId = constant('URLREWRITE_SEARCH_LANDING_CONDITION_CATALOG_IBLOCK_ID_'.$siteId);
											}
											if(!$catalogIblockId){
												$catalogIblockId = \Bitrix\Main\Config\Option::get(
													self::moduleID,
													'CATALOG_IBLOCK_ID',
													CMaxCache::$arIBlocks[$siteId]['aspro_max_catalog']['aspro_max_catalog'][0],
													$siteId
												);
											}
											if($catalogIblockId && isset(CMaxCache::$arIBlocksInfo[$catalogIblockId])){
												$catalogPage = CMaxCache::$arIBlocksInfo[$catalogIblockId]['LIST_PAGE_URL'];
											}
										}

										// catalog page script
										$catalogScriptConst = 'ASPRO_CATALOG_SCRIPT_'.$siteId;
										$catalogScript = defined($catalogScriptConst) && strlen(constant($catalogScriptConst)) ? constant($catalogScriptConst) : 'index.php';

										// catalog full url
										$pathFile = str_replace(array('#SITE_DIR#', $catalogScript), array($siteDir, ''), $catalogPage).$catalogScript;
										\Bitrix\Main\UrlRewriter::add(
											$siteId,
											array(
												'CONDITION' => '#^/'.$urlCondition.'#',
												'ID' => '',
												'PATH' => $pathFile,
												'RULE' => 'ls='.$arg1['ID'],
											)
										);
									}
								}
							}
						}
					}
				}
			}
		}

		if($ELEMENT_ID){
			static $arPropCache = array();
			static $arPropArray=array();

			if(!array_key_exists($IBLOCK_ID, $arPropCache)){
				//Check for MINIMAL_PRICE property
				$rsProperty = CIBlockProperty::GetByID("MINIMUM_PRICE", $IBLOCK_ID);
				$arProperty = $rsProperty->Fetch();
				if($arProperty){
					$arPropCache[$IBLOCK_ID] = $arProperty["ID"];
					$arPropArray["MINIMUM_PRICE"]=$arProperty["ID"];
				}else{
					$arPropCache[$IBLOCK_ID] = false;
				}
				$rsProperty = CIBlockProperty::GetByID("IN_STOCK", $IBLOCK_ID);
				$arProperty = $rsProperty->Fetch();
				if($arProperty){
					$arPropCache[$IBLOCK_ID] = $arProperty["ID"];
					$arPropArray["IN_STOCK"]=$arProperty["ID"];
				}else{
					if(!$arPropCache[$IBLOCK_ID])
						$arPropCache[$IBLOCK_ID] = false;
				}
			}

			if($arPropCache[$IBLOCK_ID]){
				//Compose elements filter
				if($OFFERS_IBLOCK_ID){
					$rsOffers = CIBlockElement::GetList(
						array(),
						array(
							"IBLOCK_ID" => $OFFERS_IBLOCK_ID,
							"PROPERTY_".$OFFERS_PROPERTY_ID => $ELEMENT_ID,
							"ACTIVE" => "Y"
						),
						false,
						false,
						array("ID")
					);
					while($arOffer = $rsOffers->Fetch())
						$arProductID[] = $arOffer["ID"];

					if (!is_array($arProductID))
						$arProductID = array($ELEMENT_ID);
				}
				else
					$arProductID = array($ELEMENT_ID);

				if($arPropArray["MINIMUM_PRICE"]){
					$minPrice = false;
					$maxPrice = false;
					//Get prices
					$rsPrices = CPrice::GetList(
						array(),
						array(
							"PRODUCT_ID" => $arProductID,
						)
					);
					while($arPrice = $rsPrices->Fetch()){
						if (CModule::IncludeModule('currency') && $strDefaultCurrency != $arPrice['CURRENCY'])
							$arPrice["PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["PRICE"], $arPrice["CURRENCY"], $strDefaultCurrency);

						$PRICE = $arPrice["PRICE"];

						if($minPrice === false || $minPrice > $PRICE)
							$minPrice = $PRICE;

						if($maxPrice === false || $maxPrice < $PRICE)
							$maxPrice = $PRICE;
					}

					//Save found minimal price into property
					if($minPrice !== false){
						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"MINIMUM_PRICE" => $minPrice,
								"MAXIMUM_PRICE" => $maxPrice,
							)
						);
					}
				}
				if($arPropArray["IN_STOCK"]){
					$quantity=0;
					$rsQuantity = CCatalogProduct::GetList(
				        array("QUANTITY" => "DESC"),
				        array("ID" => $arProductID),
				        false,
				        false,
				        array("QUANTITY")
				    );
					while($arQuantity = $rsQuantity->Fetch()){
						if($arQuantity["QUANTITY"]>0)
							$quantity+=$arQuantity["QUANTITY"];
					}
					if($quantity>0){
						$rsPropStock = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"IN_STOCK"));
						if($arPropStock=$rsPropStock->Fetch()){
							$idProp=$arPropStock["ID"];
						}

						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"IN_STOCK" => $idProp,
							)
						);
					}else{
						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"IN_STOCK" => "",
							)
						);
					}
					if(class_exists('\Bitrix\Iblock\PropertyIndex\Manager')){
						\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($IBLOCK_ID, $ELEMENT_ID);
					}
				}
			}
		}
	}

	static function DoIBlockElementAfterDelete($arFields){
		$IBLOCK_ID = $arFields['IBLOCK_ID'];

		if(\Aspro\Max\SearchQuery::isLandingSearchIblock($IBLOCK_ID)){
			$ID = $arFields['ID'];

			if(CMaxCache::$arIBlocksInfo[$IBLOCK_ID]){
				$arSitesLids = CMaxCache::$arIBlocksInfo[$IBLOCK_ID]['LID'];

				// search and remove urlrewrite item
				$searchRule = 'ls='.$ID;
				foreach($arSitesLids as $siteId){
					if($arUrlRewrites = \Bitrix\Main\UrlRewriter::getList($siteId, array('ID' => 'bitrix:catalog'))){
						foreach($arUrlRewrites as $arUrlRewrite){
							if($arUrlRewrite['RULE'] && strpos($arUrlRewrite['RULE'], $searchRule) !== false){
								\Bitrix\Main\UrlRewriter::delete($siteId, array('CONDITION' => $arUrlRewrite['CONDITION']));
							}
						}
					}
				}
			}
		}
	}

	static public function isLandingSearchIblock($IBLOCK_ID){
		return isset(CMaxCache::$arIBlocksInfo[$IBLOCK_ID]) && strpos(CMaxCache::$arIBlocksInfo[$IBLOCK_ID]['CODE'], 'aspro_max_search') !== false;
	}

	protected static $handlerDisallow = 0;

	public static function disableHandler()
	{
	  self::$handlerDisallow--;
	}

	public static function enableHandler()
	{
	  self::$handlerDisallow++;
	}

	public static function isEnabledHandler()
	{
	  return (self::$handlerDisallow >= 0);
	}

	static function setStoreProductHandler($ID, $arFields){
		static $stores_quantity_product, $updateFromCatalog;
		$arProduct = CCatalogStoreProduct::GetList(array(), array('ID' => $ID), false, false, array('PRODUCT_ID'))->Fetch();
		if($arProduct['PRODUCT_ID'] && \Bitrix\Main\Config\Option::get(self::moduleID, "EVENT_SYNC", "N") == "Y")
		{
			if(isset($arFields['AMOUNT']) && $arFields['AMOUNT'])
				$stores_quantity_product += $arFields['AMOUNT'];

			if($updateFromCatalog !== NULL)
			{
				/*set flag*/
    	   		self::disableHandler();
    	   	}

			CCatalogProduct::Update($arProduct['PRODUCT_ID'], array("QUANTITY" => $stores_quantity_product));

			if($updateFromCatalog !== NULL)
			{
				/*unset flag*/
				self::enableHandler();
			}
		}
	}

	static function setStockProduct($ID, $arFields){
		/*check flag*/
		if (!self::isEnabledHandler())
           return;

       	/*set flag*/
       	self::disableHandler();

		//Get iblock element
		$rsPriceElement = CIBlockElement::GetList(
			array(),
			array(
				"ID" => $ID,
			),
			false,
			false,
			array("ID", "IBLOCK_ID")
		);

		if($arPriceElement = $rsPriceElement->Fetch()){
			$arCatalog = CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);
			if(is_array($arCatalog)){
				//Check if it is offers iblock
				if($arCatalog["OFFERS"] == "Y"){
					//Find product element
					$rsElement = CIBlockElement::GetProperty(
						$arPriceElement["IBLOCK_ID"],
						$arPriceElement["ID"],
						"sort",
						"asc",
						array("ID" => $arCatalog["SKU_PROPERTY_ID"])
					);
					$arElement = $rsElement->Fetch();
					if($arElement && $arElement["VALUE"] > 0)
					{
						$ELEMENT_ID = $arElement["VALUE"];
						$IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
						$OFFERS_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
						$OFFERS_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
					}
				}
				//or iblock which has offers
				elseif($arCatalog["OFFERS_IBLOCK_ID"] > 0){
					$ELEMENT_ID = $arPriceElement["ID"];
					$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
					$OFFERS_IBLOCK_ID = $arCatalog["OFFERS_IBLOCK_ID"];
					$OFFERS_PROPERTY_ID = $arCatalog["OFFERS_PROPERTY_ID"];
				}
				//or it's regular catalog
				else{
					$ELEMENT_ID = $arPriceElement["ID"];
					$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
					$OFFERS_IBLOCK_ID = false;
					$OFFERS_PROPERTY_ID = false;
				}
			}
		}
		if($ELEMENT_ID){
			static $arPropCache = array();
			static $arPropArray=array();

			if(!array_key_exists($IBLOCK_ID, $arPropCache)){
				//Check for IN_STOCK property
				$rsProperty = CIBlockProperty::GetByID("IN_STOCK", $IBLOCK_ID);
				$arProperty = $rsProperty->Fetch();
				if($arProperty){
					$arPropCache[$IBLOCK_ID] = $arProperty["ID"];
					$arPropArray["IN_STOCK"]=$arProperty["ID"];
				}else{
					if(!$arPropCache[$IBLOCK_ID])
						$arPropCache[$IBLOCK_ID] = false;
				}
			}
			if($arPropCache[$IBLOCK_ID]){
				//Compose elements filter
				$arProductID = array();
				if($OFFERS_IBLOCK_ID){
					$rsOffers = CIBlockElement::GetList(
						array(),
						array(
							"IBLOCK_ID" => $OFFERS_IBLOCK_ID,
							"PROPERTY_".$OFFERS_PROPERTY_ID => $ELEMENT_ID,
							"ACTIVE" => "Y"
						),
						false,
						false,
						array("ID")
					);
					while($arOffer = $rsOffers->Fetch())
						$arProductID[] = $arOffer["ID"];

					if (!$arProductID)
						$arProductID = array($ELEMENT_ID);
				}
				else
					$arProductID = array($ELEMENT_ID);


				if($arPropArray["IN_STOCK"]){
					/* sync quantity product by stores start */
					if($arProductID /*&& \Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control', 'N') == 'N'*/ && ($_SESSION['CUSTOM_UPDATE'] == 'Y' || \Bitrix\Main\Config\Option::get(self::moduleID, "EVENT_SYNC", "N") == "Y"))
					{
						static $bStores;
						if(class_exists('CCatalogStore')){
							if(!$bStores)
							{
								$dbRes = CCatalogStore::GetList(array(), array(), false, false, array());
								if($c = $dbRes->SelectedRowsCount()){
									$bStores = true;
								}
							}
						}
						if($bStores)
						{
							static $updateFromCatalog;
							$updateFromCatalog = true;

							foreach($arProductID as $id)
							{
								$quantity_stores = 0;
								$rsStore = CCatalogStore::GetList(array(), array('PRODUCT_ID' => $id), false, false, array('ID', 'PRODUCT_AMOUNT'));
								while($arStore = $rsStore->Fetch())
								{
									$quantity_stores += $arStore['PRODUCT_AMOUNT'];
								}
								CCatalogProduct::Update($id, array("QUANTITY" => $quantity_stores));
							}
						}
					}
					/* sync quantity product by stores end */

					$quantity=0;
					$rsQuantity = CCatalogProduct::GetList(
				        array("QUANTITY" => "DESC"),
				        array("ID" => $arProductID),
				        false,
				        false,
				        array("QUANTITY")
				    );
					while($arQuantity = $rsQuantity->Fetch()){
						if($arQuantity["QUANTITY"]>0)
							$quantity+=$arQuantity["QUANTITY"];
					}
					if($quantity>0){
						$rsPropStock = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>"IN_STOCK"));
						if($arPropStock=$rsPropStock->Fetch()){
							$idProp=$arPropStock["ID"];
						}

						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"IN_STOCK" => $idProp,
							)
						);
					}else{
						CIBlockElement::SetPropertyValuesEx(
							$ELEMENT_ID,
							$IBLOCK_ID,
							array(
								"IN_STOCK" => "",
							)
						);
					}
					if(class_exists('\Bitrix\Iblock\PropertyIndex\Manager')){
						\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($IBLOCK_ID, $ELEMENT_ID);
					}
				}
			}
		}

		/*unset flag*/
		self::enableHandler();
	}

	static function CurrencyFormatHandler($price, $currency){
		if(!defined('ADMIN_SECTION') && !CSite::inDir(SITE_DIR.'personal/orders'))
		{
			$arCurFormat = CCurrencyLang::GetFormatDescription($currency);

			$intDecimals = $arCurFormat['DECIMALS'];
		    if (CCurrencyLang::isAllowUseHideZero() && $arCurFormat['HIDE_ZERO'] == 'Y')
		    {
		        if (round($price, $arCurFormat["DECIMALS"]) == round($price, 0))
		            $intDecimals = 0;
		    }
		    $price = number_format($price, $intDecimals, $arCurFormat['DEC_POINT'], $arCurFormat['THOUSANDS_SEP']);
		    if ($arCurFormat['THOUSANDS_VARIANT'] == CCurrencyLang::SEP_NBSPACE)
		        $price = str_replace(' ', '&nbsp;', $price);
		    $arFormatString = explode('#', $arCurFormat['FORMAT_STRING']);
		    $arFormatString[1] = '<span class=\'price_currency\'>'.$arFormatString[1].'</span>';
			$arCurFormat['FORMAT_STRING'] = '#'.$arFormatString[1];

		    return preg_replace('/(^|[^&])#/', '${1}'.'<span class=\'price_value\'>'.$price.'</span>', $arCurFormat['FORMAT_STRING']);
		}
	}

	public static function OnBeforeChangeFileHandler($path, $content){
		return true;
	}

	public static function OnChangeFileHandler($path, $site){
		return true;
	}

	public function OnAfterUpdateSitemapHandler(){
		$arId = func_get_arg(0);
		$arFields = func_get_arg(2);

		if(
			$arId &&
			$arFields &&
			is_array($arId) &&
			is_array($arFields) &&
			($SITEMAP_ID = intval($arId['ID'])) > 0 &&
			$arFields['DATE_RUN']
		){
			$dbSitemap = \Bitrix\Seo\SitemapTable::getById($SITEMAP_ID);
			if($arSitemap = $dbSitemap->fetch()){
				$arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);
			}

			$dbSitemap = \Bitrix\Seo\SitemapTable::getById($SITEMAP_ID);
			if($arSitemap = $dbSitemap->fetch()){
				$arSitemap['SETTINGS'] = unserialize($arSitemap['SETTINGS']);

				$arLandingSearchIblocksIds = array();
				if(\CMaxCache::$arIBlocks && \CMaxCache::$arIBlocks[$arSitemap['SITE_ID']] && \CMaxCache::$arIBlocks[$arSitemap['SITE_ID']]['aspro_max_catalog']['aspro_max_search']){
					$arLandingSearchIblocksIds = \CMaxCache::$arIBlocks[$arSitemap['SITE_ID']]['aspro_max_catalog']['aspro_max_search'];
				}

				if($arLandingSearchIblocksIds && $arSitemap['SETTINGS']['IBLOCK_ACTIVE'] && $arSitemap['SETTINGS']['IBLOCK_ELEMENT']){
					$siteDocRoot = \Bitrix\Main\SiteTable::getDocumentRoot($arSitemap['SITE_ID']);
					$regionSitemapPath = $siteDocRoot.'/aspro_regions/sitemap';
					if(!is_dir($regionSitemapPath)){
						@mkdir($regionSitemapPath, BX_DIR_PERMISSIONS, true);
					}

					$bUseRegionality = \Bitrix\Main\Config\Option::get(self::moduleID, 'USE_REGIONALITY', 'N', $arSitemap['SITE_ID']) === 'Y';
					$bUseOneDomain = \Bitrix\Main\Config\Option::get(self::moduleID, 'REGIONALITY_TYPE', 'ONE_DOMAIN', $arSitemap['SITE_ID']) !== 'SUBDOMAIN';

					$arRegions = array();
					$dbRes = CIBlockElement::GetList(
						array(),
						array(
							'ACTIVE' => 'Y',
							'LID' => $arSitemap['SITE_ID'],
							'IBLOCK_CODE' => 'aspro_max_regions',
						),
						false,
						false,
						array(
							'ID',
							'IBLOCK_ID',
							'PROPERTY_MAIN_DOMAIN',
						)
					);
					while($arRegion = $dbRes->Fetch()){
						$arRegions[] = $arRegion;
					}

					foreach($arSitemap['SETTINGS']['IBLOCK_ELEMENT'] as $iblockId => $val){
						if(in_array($iblockId, $arLandingSearchIblocksIds) && $val === 'Y' && $arSitemap['SETTINGS']['IBLOCK_ACTIVE'][$iblockId] === 'Y'){
							if($fileName = str_replace(
								array('#IBLOCK_ID#', '#IBLOCK_CODE#', '#IBLOCK_XML_ID#'),
								array($iblockId, $arLandingSearchIBlock['CODE'], $arLandingSearchIBlock['XML_ID']),
								$arSitemap['SETTINGS']['FILENAME_IBLOCK']
							)){
								$fileName = $siteDocRoot.'/'.trim($fileName, '/');
								if(file_exists($fileName)){
									$content = @file_get_contents($fileName);

									// get landings items
									$arLandings = $arLandingsIDs = array();
									if($content && preg_match_all('/<url>\s*<loc>(([^<]*)=(\d*))<\/loc>\s*<lastmod>([^<]*)<\/lastmod>\s*<\/url>/i', $content, $arLandingsMatches)){
										$arLandingsIDs = $arLandingsMatches[3];
										$arLandings = \CMaxCache::CIBLockElement_GetList(
											array(
												'ID' => 'ASC',
												'CACHE' => array(
													'MULTI' => 'N',
													'TAG' => \CMaxCache::GetIBlockCacheTag($iblockId),
													'GROUP' => array('ID'),
												),
											),
											array(
												'ID' => $arLandingsIDs,
												'ACTIVE' => 'Y',
											),
											false,
											false,
											array(
												'ID',
												'IBLOCK_ID',
												'NAME',
												'DETAIL_PAGE_URL',
												'PROPERTY_IS_INDEX',
												'PROPERTY_URL_CONDITION',
												'PROPERTY_REDIRECT_URL',
												'PROPERTY_QUERY',
												'PROPERTY_LINK_REGION',
											)
										);

										// get enum id of property IS_INDEX with XML_ID = Y
										$arEnumID_IS_INDEX = \CIBlockPropertyEnum::GetList(
											array(),
											array(
												'IBLOCK_ID' => $iblockId,
												'CODE' => 'IS_INDEX',
												'XML_ID' => 'Y',
											)
										)->GetNext();

										$arRegionFiles = array($fileName);
										if($bUseRegionality && !$bUseOneDomain){
											foreach($arRegions as $arRegion){
												$arRegionFiles[$arRegion['ID']] = $regionSitemapPath.'/'.basename($fileName, '.xml').'_'.$arRegion['PROPERTY_MAIN_DOMAIN_VALUE'].'.xml';
											}
										}
									}

									foreach($arRegionFiles as $regionId => $fileNameTo){
										$newContent = $content;

										foreach($arLandingsMatches[0] as $i => $match){
											$LID = $arLandingsMatches[3][$i];
											if($arLandings[$LID]){
												$arLandings[$LID]['PROPERTY_LINK_REGION_VALUE'] = (array)$arLandings[$LID]['PROPERTY_LINK_REGION_VALUE'];
												if((!$arEnumID_IS_INDEX || ($arEnumID_IS_INDEX && $arLandings[$LID]['PROPERTY_IS_INDEX_ENUM_ID'] == $arEnumID_IS_INDEX['ID']))){
													if(
														!$bUseRegionality ||
														!$regionId ||
														!$arLandings[$LID]['PROPERTY_LINK_REGION_VALUE'] ||
														(
															!$bUseOneDomain && in_array($regionId, $arLandings[$LID]['PROPERTY_LINK_REGION_VALUE'])
														)
													){
														$catalogDir = preg_replace('/[\?].*/', '', $arLandings[$LID]['DETAIL_PAGE_URL']);
														$url = \Aspro\Max\SearchQuery::getLandingUrl(
															$catalogDir,
															$arLandings[$LID]['PROPERTY_URL_CONDITION_VALUE'],
															$arLandings[$LID]['PROPERTY_REDIRECT_URL_VALUE'],
															$arLandings[$LID]['PROPERTY_QUERY_VALUE'],
															$arLandings[$LID]['ID']
														);

														$url = str_replace('&', '&amp;', $url);

														if(strpos($url, 'http') === false){
															$url = (\CMain::isHTTPS() ? 'https://' : 'http://').str_replace('//', '/', $arSitemap['SETTINGS']['DOMAIN'].$url);
														}
														$newContent = str_replace($arLandingsMatches[1][$i], $url, $newContent);

														continue;
													}
												}
											}

											// delete if not IS_INDEX
											$newContent = str_replace($match, '', $newContent);
										}
										@file_put_contents($fileNameTo, $newContent);
									}
									unset($newContent);
								}
							}
						}
					}
				}
			}
		}
	}

	static function OnEndBufferContentHandler(&$content)
	{
		$bIndexBot = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Lighthouse') !== false); // is indexed yandex/google bot

		$bCompSaleOrderAjaxPost = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['soa-action']);
		if(!defined('ADMIN_SECTION') && !defined('WIZARD_SITE_ID') && !defined('CUSTOM_CONTENT') || $bCompSaleOrderAjaxPost)
		{
			global $SECTION_BNR_CONTENT, $arRegion, $APPLICATION;

			if($bCompSaleOrderAjaxPost){
				if($arRegion){
					$arTagSeoMarks = array();
					foreach($arRegion as $key => $value){
						if(strpos($key, 'PROPERTY_REGION_TAG') !== false && strpos($key, '_VALUE_ID') === false){
							$tag_name = str_replace(array('PROPERTY_', '_VALUE'), '', $key);
							$arTagSeoMarks['#'.$tag_name.'#'] = $key;
						}
					}

					if($arTagSeoMarks){
						CMaxRegionality::addSeoMarks($arTagSeoMarks);
					}
				}
			}

			// if((strpos($APPLICATION->GetCurPage(), 'ajax') === false && strpos($APPLICATION->GetCurPage(), 'bitrix') === false))
			// {
				foreach(CMaxRegionality::$arSeoMarks as $mark => $field)
				{
					if(strpos($content, $mark) !== false)
					{
						if($arRegion)
						{
							if(is_array($arRegion[$field])){
								$value = $bCompSaleOrderAjaxPost ? trim(\Bitrix\Main\Web\Json::encode($arRegion[$field]['TEXT']), '"') : $arRegion[$field]['TEXT'];
								$content = str_replace(array($mark, str_replace('#REGION_TAG_', '#REGION_STRIP_TAG_', $mark)), array($value, strip_tags($value)), $content);
							}
							else{
								$value = $bCompSaleOrderAjaxPost ? trim(\Bitrix\Main\Web\Json::encode($arRegion[$field]), '"') : $arRegion[$field];
								$content = str_replace(array($mark, str_replace('#REGION_TAG_', '#REGION_STRIP_TAG_', $mark)), array($value, strip_tags($value)), $content);
							}
						}
						else{
							$content = str_replace(array($mark, str_replace('#REGION_TAG_', '#REGION_STRIP_TAG_', $mark)), '', $content);
						}
					}
				}
			// }

			// replace canonical|next|prev to <head>
			if(preg_match_all('/<\s*link\s+[^\>]*rel\s*=\s*[\'"](canonical|next|prev)[\'"][^\>]*>/i'.BX_UTF_PCRE_MODIFIER, $content, $arMatches)){
				$links = implode(
					'',
					array_map(
						function($match){
							if(preg_match('/href\s*=\s*[\'"]([^\'"]*)[\'"]/i'.BX_UTF_PCRE_MODIFIER, $match, $arMatch)){
								return preg_replace('/href\s*=\s*[\'"]([^\'"]*)[\'"]/i'.BX_UTF_PCRE_MODIFIER, 'href="'.(preg_replace('/(http[s]*:\/\/|^)([^\/]*[\/]?)(.*)/i'.BX_UTF_PCRE_MODIFIER, (CMain::IsHTTPS() ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].'/${3}', $arMatch[1])).'"', $match);
							}

							return $match;
						},
						array_values($arMatches[0])
					)
				);

				$content = preg_replace(
					array(
						'/<\s*link\s+[^\>]*rel\s*=\s*[\'"](canonical|next|prev)[\'"][^\>]*>/i'.BX_UTF_PCRE_MODIFIER,
						'/<\s*head(\s+[^\>]*|)>/i'.BX_UTF_PCRE_MODIFIER,
					),
					array(
						'',
						'${0}'.$links,
					),
					$content
				);
			}

			// lazyload
			if($GLOBALS['_USE_LAZY_LOAD_']){

				// add lazyload attribyte for each <img> that does not contain data-src
				$tmpContent = preg_replace('/<img ((?![^>]*\bdata-src\b)[^>]*>)/i'.BX_UTF_PCRE_MODIFIER, '<img data-lazyload ${1}', $content);
				if(isset($tmpContent) && strpos($_SERVER['REQUEST_URI'], '/bitrix/components/') === false){
					$content = $tmpContent;
					$content = preg_replace('/(<img data-lazyload [^>]*)src=/i'.BX_UTF_PCRE_MODIFIER, '${1}src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src=', $content);
				}

				$tmpContent = preg_replace('/<((?!img)\w*)([^>]*background\-image\:\s*url\s*\()/i'.BX_UTF_PCRE_MODIFIER, '<${1} data-lazyload ${2}', $content);
				if(isset($tmpContent)){
					$content = $tmpContent;
					$content = preg_replace('/(<(?!img)\w* data-lazyload [^>]*data-src=["\']{1}([^\"\']*)["\']{1})([^>]*)/i'.BX_UTF_PCRE_MODIFIER, '${1} data-bg=${2} ${3}', $content);
				}

				$tmpContent = preg_replace('/<((?!img)\w*)([^>]*\bbackground\:.*?url\s*\([^>]*>)/i'.BX_UTF_PCRE_MODIFIER, '<${1} data-lazyload ${2}', $content);
				if(isset($tmpContent)){
					$content = $tmpContent;
					$content = preg_replace('/(<(?!img)\w* data-lazyload [^>]*data-src=["\']{1}([^\"\']*)["\']{1})([^>]*)/i'.BX_UTF_PCRE_MODIFIER, '${1} data-bg=${2} ${3}', $content);
				}

				if(isset($tmpContent)){
					$content = preg_replace('/(<\w* data-lazyload .*?)class=([\'"])(?![^>]*\blazy\b)/i'.BX_UTF_PCRE_MODIFIER, '${1}class=${2}lazy ', $content);
					$content = preg_replace('/<\w* data-lazyload (?![^>]*\bclass\s*=\s*[\'\"]\b)(?![^>]*\blazy\b)/i'.BX_UTF_PCRE_MODIFIER, '${0}class="lazy" ', $content);
				}
			}

			if($bIndexBot) {
				$pattern = '/<iframe.*?<\/iframe>/is';
				$content = preg_replace($pattern, '', $content);

				$pattern = '/<script.*?<\/script>/is';
				$content = preg_replace($pattern, '', $content);
			}

			$pattern = '/\n(\s*?)\n/is';
			$content = preg_replace($pattern, PHP_EOL, $content);

$content = str_replace('
<!DOCTYPE html>', '<!DOCTYPE html>', $content);

			//replace text/javascript for html5 validation w3c
			$content = str_replace(' type="text/javascript"', '', $content);
			$content = str_replace(' type=\'text/javascript\'', '', $content);
			$content = str_replace(' type="text/css"', '', $content);
			$content = str_replace(' type=\'text/css\'', '', $content);
			$content = str_replace(' charset="utf-8"', '', $content);
			$content = str_replace(' data-charset="utf-8"', ' charset="utf-8"', $content);

			if($SECTION_BNR_CONTENT)
			{
				$start = strpos($content, '<!--title_content-->');
				if($start>0)
				{
					$end = strpos($content, '<!--end-title_content-->');

					if(($end>0) && ($end>$start))
					{
						if(defined("BX_UTF") && BX_UTF === true)
							$content = CMax::utf8_substr_replace($content, "", $start, $end-$start);
						else
							$content = substr_replace($content, "", $start, $end-$start);
					}
				}
				$content = str_replace("body class=\"", "body class=\"with_banners ", $content);
			}

			if($bShowHeaderSimple)
			{
				$content = str_replace("body class=\"", "body class=\"simple_basket_mode ", $content);
			}

			//process recaptcha
			if(\Aspro\Functions\CAsproMaxReCaptcha::checkRecaptchaActive())
			{
				$content = str_replace(Loc::getMessage('FORM_ERROR_RECAPTCHA_FROM'), Loc::getMessage('FORM_ERROR_RECAPTCHA_TO'), $content);

				$count = 0;
				$contentReplace = preg_replace_callback(
					'!(<img\s[^>]*?src[^>]*?=[^>]*?)(\/bitrix\/tools\/captcha\.php\?(captcha_code|captcha_sid)=[0-9a-z]+)([^>]*?>)!',
					function ($arImage)
					{
						//replace src and style
						$arImage = array(
							'tag' => $arImage[1],
							'src' => $arImage[2],
							'tail' => $arImage[4],
						);

						return \Aspro\Functions\CAsproMaxReCaptcha::callbackReplaceImage($arImage);
					},
					$content,
					-1,
					$count
				);

				if($count <= 0 || !$contentReplace)
					return;

				$content = $contentReplace;
				unset($contentReplace);

				$captcha_public_key = \Aspro\Functions\CAsproMaxReCaptcha::getPublicKey();

				$ind = 0;
				while ($ind++ <= $count)
				{
					$uniqueId = randString(4);
					$content = preg_replace(
						'!<input\s[^>]*?name[^>]*?=[^>]*?captcha_word[^>]*?>!',
						"<div id='recaptcha-$uniqueId'
						class='g-recaptcha'
						data-sitekey='$captcha_public_key'></div>
					<script type='text/javascript' data-skip-moving='true'>
						if(typeof renderRecaptchaById !== 'undefined')
							renderRecaptchaById('recaptcha-$uniqueId');
					</script>", $content, 1
					);
				}

				$arSearchMessages = array(
					\Bitrix\Main\Localization\Loc::getMessage('FORM_CAPRCHE_TITLE_RECAPTCHA'),
					\Bitrix\Main\Localization\Loc::getMessage('FORM_CAPRCHE_TITLE_RECAPTCHA2'),
					\Bitrix\Main\Localization\Loc::getMessage('FORM_CAPRCHE_TITLE_RECAPTCHA3'),
				);

				$content = str_replace($arSearchMessages, \Bitrix\Main\Localization\Loc::getMessage('FORM_GENERAL_RECAPTCHA'), $content);
			}
		}
	}

	public static function OnPageStartHandler(){
		if(defined("ADMIN_SECTION")){
			return;
		}

		global $arRegion;

		if(!$arRegion)
			$arRegion = CMaxRegionality::getCurrentRegion();

		// check search landing with url condition
		if(
			isset($_REQUEST['ls']) &&
			(
				$_SERVER['SCRIPT_NAME'] === '/bitrix/urlrewrite.php' ||
				(
					$_SERVER['SCRIPT_NAME'] &&
					strpos($_SERVER['SCRIPT_FILENAME'], '/bitrix/urlrewrite.php') !== false
				)
			)
		){
			if($bLandingWithUrlCondition = ($landingID = intval($_REQUEST['ls'])) > 0){
				if($_GET){
					unset($_GET['q'], $_GET['ls']);
				}

				$_REQUEST['q'] = '';
				if($_SESSION && array_key_exists('q_'.$landingID, $_SESSION)){
					$_REQUEST['q'] = $_SESSION['q_'.$landingID];
					$_SESSION['q_'.$landingID] = '';
					unset($_SESSION['q_'.$landingID]);
				}

				$context = \Bitrix\Main\Context::getCurrent();
				$server = $context->getServer();
				$server_array = $server->toArray();
				$server_array['REQUEST_URI'] = $_SERVER['REQUEST_URI'] = str_replace('index.php', '', $_SERVER['REAL_FILE_PATH']).'?'.str_replace(urlencode(urlencode($_REQUEST['q'])), urlencode($_REQUEST['q']), $_SERVER['QUERY_STRING']);
				$server->set($server_array);
				$context->initialize(new \Bitrix\Main\HttpRequest($server, $_GET, $_POST, $_FILES, $_COOKIE), $context->getResponse(), $server);
				$GLOBALS['APPLICATION']->SetCurPage($GLOBALS['APPLICATION']->GetCurPage(), str_replace($GLOBALS['APPLICATION']->GetCurPage(), '', $GLOBALS['APPLICATION']->GetCurPageParam('', array('q', 'ls'))));
			}
		}

		if(!\Aspro\Functions\CAsproMaxReCaptcha::checkRecaptchaActive()){
			return;
		}

		// remove captcha_word from request
		if(isset($_REQUEST['captcha_word'])){
			$_REQUEST['captcha_word'] = $_POST['captcha_word'] = '';
		}

		$captcha_public_key = \Aspro\Functions\CAsproMaxReCaptcha::getPublicKey();
		$captcha_version = \Aspro\Functions\CAsproMaxReCaptcha::getVersion();
		$assets = Asset::getInstance();

		if($captcha_version == 3){
			$arCaptchaProp = array(
				'recaptchaColor' => '',
				'recaptchaLogoShow' => '',
				'recaptchaSize' => '',
				'recaptchaBadge' => '',
				'recaptchaLang' => LANGUAGE_ID,
			);
		}
		else{
			$arCaptchaProp = array(
				'recaptchaColor' => strtolower(Option::get(self::moduleID, 'GOOGLE_RECAPTCHA_COLOR', 'LIGHT')),
				'recaptchaLogoShow' => strtolower(Option::get(self::moduleID, 'GOOGLE_RECAPTCHA_SHOW_LOGO', 'Y')),
				'recaptchaSize' => strtolower(Option::get(self::moduleID, 'GOOGLE_RECAPTCHA_SIZE', 'NORMAL')),
				'recaptchaBadge' => strtolower(Option::get(self::moduleID, 'GOOGLE_RECAPTCHA_BADGE', 'BOTTOMRIGHT')),
				'recaptchaLang' => LANGUAGE_ID,
			);
		}

		//add global object asproRecaptcha
		$scripts = "<script type='text/javascript' data-skip-moving='true'>";
		$scripts .= "window['asproRecaptcha'] = {params: ".\CUtil::PhpToJsObject($arCaptchaProp).",key: '".$captcha_public_key."',ver: '".$captcha_version."'};";
		$scripts .= "</script>";
		$assets->addString($scripts);

		//add scripts
		$scriptsDir = $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.self::moduleID.'/captcha/';
		$scriptsPath = File::isFileExists($scriptsDir.'recaptcha.min.js')? $scriptsDir.'recaptcha.min.js' : $scriptsDir.'recaptcha.js';
		$scriptCode = File::getFileContents($scriptsPath);
		$scripts = "<script type='text/javascript' data-skip-moving='true'>".$scriptCode."</script>";
		$assets->addString($scripts);

		$scriptsPath = File::isFileExists($scriptsDir . 'replacescript.min.js') ? $scriptsDir . 'replacescript.min.js' : $scriptsDir . 'replacescript.js';
		$scriptCode = File::getFileContents($scriptsPath);
		$scripts = "<script type='text/javascript' data-skip-moving='true'>".$scriptCode."</script>";
		$assets->addString($scripts);

		//process post request
		$application = Application::getInstance();
		$request = $application->getContext()->getRequest();
		$arPostData = $request->getPostList()->toArray();

		$needReInit = false;

		if($arPostData['g-recaptcha-response'])
		{
			if($code = \Aspro\Functions\CAsproMaxReCaptcha::getCodeByPostList($arPostData))
			{
				$_REQUEST['captcha_word'] = $_POST['captcha_word'] = $code;
				$needReInit = true;
			}
		}

		foreach($arPostData as $key => $arPost)
		{
			if(is_array($arPost) && $arPost['g-recaptcha-response'])
			{
				if($code = \Aspro\Functions\CAsproMaxReCaptcha::getCodeByPostList($arPost))
				{
					$_REQUEST[$key]['captcha_word'] = $_POST[$key]['captcha_word'] = $code;
					$needReInit = true;
				}
			}
		}

		if($needReInit)
		{
			\Aspro\Functions\CAsproMaxReCaptcha::reInitContext($application, $request);
		}
	}

	public static function OnSearchGetURL($arFields){
    	if($arFields['MODULE_ID'] === 'iblock'){
    		if(($iblockId = intval($arFields['PARAM2'])) > 0){
    			if(($id = intval($arFields['ITEM_ID'])) > 0){
			    	if(strpos($arFields["URL"], "#YEAR#") !== false){
						$arElement = \CMaxCache::CIBlockElement_GetList(
							array(
								'CACHE' => array(
									'TAG' => \CMaxCache::GetIBlockCacheTag($iblockId),
									'MULTI' => 'N'
								)
							),
							array('ID' => $id),
							false,
							false,
							array(
								'ID',
								'ACTIVE_FROM',
							)
						);

				    	if($arElement['ACTIVE_FROM']){
				    		if($arDateTime = ParseDateTime($arElement['ACTIVE_FROM'], FORMAT_DATETIME)){
						        return str_replace("#YEAR#", $arDateTime['YYYY'], $arFields['URL']);
				    		}
				    	}
			    	}
			    	elseif(\Aspro\Max\SearchQuery::isLandingSearchIblock($iblockId)){
			    		$dbRes = \CIBlockElement::GetProperty($iblockId, $id, array('sort' => 'asc'), array('CODE' => 'IS_SEARCH_TITLE'));
	      				if($arValue = $dbRes->Fetch()){
							if($arValue['VALUE_XML_ID'] === \Aspro\Max\SearchQuery::IS_SEARCH_TITLE_BY_NAME_XML_ID){
					    		$arElement = \CMaxCache::CIBlockElement_GetList(
									array(
										'CACHE' => array(
											'TAG' => \CMaxCache::GetIBlockCacheTag($iblockId),
											'MULTI' => 'N'
										)
									),
									array('ID' => $id),
									false,
									false,
									array(
										'ID',
										'IBLOCK_ID',
										'PROPERTY_URL_CONDITION',
										'PROPERTY_REDIRECT_URL',
										'PROPERTY_QUERY',
									)
								);

					    		$catalogDir = preg_replace('/[\?].*/', '', $arFields['URL']);
					    		return $url = \Aspro\Max\SearchQuery::getLandingUrl(
									$catalogDir,
									$arElement['PROPERTY_URL_CONDITION_VALUE'],
									$arElement['PROPERTY_REDIRECT_URL_VALUE'],
									$arElement['PROPERTY_QUERY_VALUE'],
									$arElement['ID']
								);
							}
						}

						return false;

			    	}
			    }
		    }
	    }

		return $arFields["URL"];
		
    }

	function OnBeforeBasketUpdateHandler($ID, &$arFields){
		//if((int)$arFields["ORDER_ID"] <= 0)
		//{
		//
		//}
	}

	function OnGetOptimalPriceHandler($intProductID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $priceList = array(), $siteID = false, $arDiscountCoupons = false){
		global $APPLICATION, $arRegion;
		static $priceTypeCache = array();
		if(!$arRegion)
		{
			if(\Bitrix\Main\Loader::includeModule('aspro.max'))
			{
				$arRegion = CMaxRegionality::getCurrentRegion(); //get current region from regionality module
			}
		}

		if($arRegion)
		{
			static $resultCurrency, $arPricesID;

			$intProductID = (int)$intProductID;
			if ($intProductID <= 0)
			{
				$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
				return false;
			}

			$quantity = (float)$quantity;
			if ($quantity <= 0)
			{
				$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
				return false;
			}

			$intIBlockID = (int)CIBlockElement::GetIBlockByID($intProductID);
			if($intIBlockID <= 0)
			{
				$APPLICATION->ThrowException(
					Loc::getMessage(
						'BT_MOD_CATALOG_PROD_ERR_ELEMENT_ID_NOT_FOUND',
						array('#ID#' => $intProductID)
					),
					'NO_ELEMENT'
				);
				return false;
			}

			if(class_exists('\Bitrix\Sale\Internals\SiteCurrencyTable'))
				$resultCurrency = \Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency(($siteID ? $siteID : SITE_ID));

			if($resultCurrency === NULL)
				$resultCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();

			if(empty($resultCurrency))
			{
				$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY"), "NO_BASE_CURRENCY");
				return false;
			}

			if($arPricesID === NULL)
			{
				$arPricesID = array();
				if($arRegion['LIST_PRICES'])
				{
					foreach($arRegion['LIST_PRICES'] as $arPrice)
					{
						if(is_array($arPrice))
						{
							if($arPrice['CAN_BUY'] == 'Y')
								$arPricesID[] = $arPrice['ID'];
						}
					}
				}
				$strRegionPrices = reset($arRegion['LIST_PRICES']);
				if(!$arPricesID && ($strRegionPrices == 'component' || $strRegionPrices == ''))
				{
					 if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
			            $arUserGroups = array((int)$arUserGroups);

			        if (!is_array($arUserGroups))
			            $arUserGroups = array();

			        if (!in_array(2, $arUserGroups))
			            $arUserGroups[] = 2;
			        \Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups);

					$cacheKey = 'U'.implode('_', $arUserGroups);
		            if (!isset($priceTypeCache[$cacheKey]))
		            {
		                $priceTypeCache[$cacheKey] = array();
		                $priceIterator = \Bitrix\Catalog\GroupAccessTable::getList(array(
		                    'select' => array('CATALOG_GROUP_ID'),
		                    'filter' => array('@GROUP_ID' => $arUserGroups, '=ACCESS' => \Bitrix\Catalog\GroupAccessTable::ACCESS_BUY),
		                    'order' => array('CATALOG_GROUP_ID' => 'ASC')
		                ));
		                while ($priceType = $priceIterator->fetch())
		                {
		                    $priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];
		                    $priceTypeCache[$cacheKey][$priceTypeId] = $priceTypeId;
		                    unset($priceTypeId);
		                }
		                unset($priceType, $priceIterator);
		            }
		            if (empty($priceTypeCache[$cacheKey]))
		                return false;
		            $arPricesID = $priceTypeCache[$cacheKey];
				}
			}
			if($arPricesID)
			{
				if(!isset($priceList) || !is_array($priceList))
					$priceList = array();

				/*if($arRegion['LIST_STORES'] && reset($arRegion['LIST_STORES']) != 'component') // check product quantity
				{
					$quantity_stores = 0;
					$arSelect = array('ID', 'PRODUCT_AMOUNT');
					$arFilter = array(
						'ID' => $arRegion['LIST_STORES'],
						'PRODUCT_ID' => $intProductID,
					);
					$rsStore = CCatalogStore::GetList(array(), $arFilter, false, false, $arSelect);
					while($arStore = $rsStore->Fetch())
					{
						$quantity_stores += $arStore['PRODUCT_AMOUNT'];
					}
					if(!$quantity_stores)
						return false;
				}*/

				$arSelect = array('ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY');
				$arFilter = array(
					'=PRODUCT_ID' => $intProductID,
					'@CATALOG_GROUP_ID' => $arPricesID,
					array(
						'LOGIC' => 'OR',
						'<=QUANTITY_FROM' => $quantity,
						'=QUANTITY_FROM' => null
					),
					array(
						'LOGIC' => 'OR',
						'>=QUANTITY_TO' => $quantity,
						'=QUANTITY_TO' => null
					)
				);
				if(empty($priceList))
				{
					if(class_exists('\Bitrix\Catalog\PriceTable'))
					{
						$iterator = \Bitrix\Catalog\PriceTable::getList(array(
							'select' => $arSelect,
							'filter' => $arFilter
						));
					}
					else
					{
						$iterator = CPrice::GetList(array(), $arFilter, false, false, $arSelect);
					}
					while($row = $iterator->fetch())
					{
						$row['ELEMENT_IBLOCK_ID'] = $intIBlockID;
						$priceList[] = $row;
					}
					unset($row);
				}
				else
				{
					foreach(array_keys($priceList) as $priceIndex)
						$priceList[$priceIndex]['ELEMENT_IBLOCK_ID'] = $intIBlockID;
					unset($priceIndex);
				}

				$iterator = CCatalogProduct::GetVATInfo($intProductID);
				if($vat = $iterator->Fetch())
					$vat['RATE'] = (float)$vat['RATE'] * 0.01;
				else
					$vat = array('RATE' => 0.0, 'VAT_INCLUDED' => 'N');
				unset($iterator);

				if (\CCatalogProduct::getUseDiscount())
				{
					if ($arDiscountCoupons === false)
						$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();
				}

				$boolDiscountVat = true;
				$isNeedDiscounts = \CCatalogProduct::getUseDiscount();

				foreach($priceList as $priceData)
				{
					$priceData['VAT_RATE'] = $vat['RATE'];
					$priceData['VAT_INCLUDED'] = $vat['VAT_INCLUDED'];

					$currentPrice = $priceData['PRICE'];
					if($boolDiscountVat)
					{
						if($priceData['VAT_INCLUDED'] == 'N')
							$currentPrice *= (1 + $priceData['VAT_RATE']);
					}
					else
					{
						if($priceData['VAT_INCLUDED'] == 'Y')
							$currentPrice /= (1 + $priceData['VAT_RATE']);
					}

					if($priceData['CURRENCY'] != $resultCurrency)
						$currentPrice = CCurrencyRates::ConvertCurrency($currentPrice, $priceData['CURRENCY'], $resultCurrency);
					$currentPrice = roundEx($currentPrice, CATALOG_VALUE_PRECISION);

					$result = array(
						'BASE_PRICE' => $currentPrice,
						'COMPARE_PRICE' => $currentPrice,
						'PRICE' => $currentPrice,
						'CURRENCY' => $resultCurrency,
						'DISCOUNT_LIST' => array(),
						'USE_ROUND' => true,
						'RAW_PRICE' => $priceData
					);
					if($isNeedDiscounts) // discount operation
					{
						$arDiscounts = CCatalogDiscount::GetDiscount(
							$intProductID,
							$intIBlockID,
							$priceData['CATALOG_GROUP_ID'],
							$arUserGroups,
							$renewal,
							$siteID,
							$arDiscountCoupons
						);

						$discountResult = CCatalogDiscount::applyDiscountList($currentPrice, $resultCurrency, $arDiscounts);
						unset($arDiscounts);
						if ($discountResult === false)
							return false;
						$result['PRICE'] = $discountResult['PRICE'];
						$result['COMPARE_PRICE'] = $discountResult['PRICE'];
						$result['DISCOUNT_LIST'] = $discountResult['DISCOUNT_LIST'];
						unset($discountResult);
					}

					if($boolDiscountVat)
					{
						if('N' == $priceData['VAT_INCLUDED'])
						{
							$result['PRICE'] /= (1 + $priceData['VAT_RATE']);
							$result['COMPARE_PRICE'] /= (1 + $priceData['VAT_RATE']);
							$result['BASE_PRICE'] /= (1 + $priceData['VAT_RATE']);
						}
					}
					else
					{
						if ('Y' == $priceData['VAT_INCLUDED'])
						{
							$result['PRICE'] *= (1 + $priceData['VAT_RATE']);
							$result['COMPARE_PRICE'] *= (1 + $priceData['VAT_RATE']);
							$result['BASE_PRICE'] *= (1 + $priceData['VAT_RATE']);
						}
					}

					$result['UNROUND_PRICE'] = $result['PRICE'];
					if ($result['USE_ROUND'])
					{
						if(class_exists('\Bitrix\Catalog\Product\Price') && method_exists('\Bitrix\Catalog\Product\Price', 'roundPrice'))
						{
							$result['PRICE'] = \Bitrix\Catalog\Product\Price::roundPrice(
								$priceData['CATALOG_GROUP_ID'],
								$result['PRICE'],
								$resultCurrency
							);
						}
						$result['COMPARE_PRICE'] = $result['PRICE'];
					}

					if(empty($result['DISCOUNT_LIST']))
					{
						$result['BASE_PRICE'] = $result['PRICE'];
					}
					elseif(roundEx($result['BASE_PRICE'], 2) - roundEx($result['PRICE'], 2) < 0.01)
					{
						$result['BASE_PRICE'] = $result['PRICE'];
						$result['DISCOUNT_PRICE'] = array();
					}

					if(empty($minimalPrice) || $minimalPrice['COMPARE_PRICE'] > $result['COMPARE_PRICE'])
					{
						$minimalPrice = $result;
					}

					unset($currentPrice, $result);
				}
				unset($priceData);
				unset($vat);

				$discountValue = ($minimalPrice['BASE_PRICE'] > $minimalPrice['PRICE'] ? $minimalPrice['BASE_PRICE'] - $minimalPrice['PRICE'] : 0);

				$arResult = array(
					'PRICE' => $minimalPrice['RAW_PRICE'],
					'RESULT_PRICE' => array(
						'PRICE_TYPE_ID' => $minimalPrice['RAW_PRICE']['CATALOG_GROUP_ID'],
						'BASE_PRICE' => $minimalPrice['BASE_PRICE'],
						'DISCOUNT_PRICE' => $minimalPrice['PRICE'],
						'UNROUND_DISCOUNT_PRICE' => $minimalPrice['UNROUND_PRICE'],
						'CURRENCY' => $resultCurrency,
						'DISCOUNT' => $discountValue,
						'PERCENT' => (
							$minimalPrice['BASE_PRICE'] > 0 && $discountValue > 0
							? roundEx((100*$discountValue)/$minimalPrice['BASE_PRICE'], CATALOG_VALUE_PRECISION)
							: 0
						),
						'VAT_RATE' => $minimalPrice['RAW_PRICE']['VAT_RATE'],
						'VAT_INCLUDED' => $minimalPrice['RAW_PRICE']['VAT_INCLUDED']
					),
					'DISCOUNT_PRICE' => $minimalPrice['PRICE'],
					'DISCOUNT' => array(),
					'DISCOUNT_LIST' => array(),
					'PRODUCT_ID' => $intProductID
				);
				if(!empty($minimalPrice['DISCOUNT_LIST']))
				{
					reset($minimalPrice['DISCOUNT_LIST']);
					$arResult['DISCOUNT'] = current($minimalPrice['DISCOUNT_LIST']);
					$arResult['DISCOUNT_LIST'] = $minimalPrice['DISCOUNT_LIST'];
				}
				unset($minimalPrice);

				return $arResult;
			}
			else
				return false;
		}
		else
			return true;
	}

	static function OnRegionUpdateHandler($arFields){
		$arIBlock = CIBlock::GetList(array(), array("ID" => $arFields["IBLOCK_ID"]))->Fetch();
		if(isset(CMaxCache::$arIBlocks[$arIBlock['LID']]['aspro_max_regionality']['aspro_max_regions'][0]) && CMaxCache::$arIBlocks[$arIBlock['LID']]['aspro_max_regionality']['aspro_max_regions'][0])
			$iRegionIBlockID = CMaxCache::$arIBlocks[$arIBlock['LID']]['aspro_max_regionality']['aspro_max_regions'][0];
		else
			return;
		if($iRegionIBlockID == $arFields['IBLOCK_ID'])
		{
			$arSite = CSite::GetList($by, $sort, array("ACTIVE"=>"Y", "ID" =>  $arIBlock['LID']))->Fetch();
			$arSite['DIR'] = str_replace('//', '/', '/'.$arSite['DIR']);
			if(!strlen($arSite['DOC_ROOT'])){
				$arSite['DOC_ROOT'] = $_SERVER['DOCUMENT_ROOT'];
			}
			$arSite['DOC_ROOT'] = str_replace('//', '/', $arSite['DOC_ROOT'].'/');
			$siteDir = str_replace('//', '/', $arSite['DOC_ROOT'].$arSite['DIR']);

			$arProperty = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $arFields["ID"], "sort", "asc", array("CODE" => "MAIN_DOMAIN"))->Fetch();
			$xml_file = (isset($arFields["SITE_MAP"]) && $arFields["SITE_MAP"] ? $arFields["SITE_MAP"] : "sitemap.xml");
			if($arProperty["VALUE"])
			{
				if(file_exists($siteDir.'robots.txt'))
				{
					copy($siteDir.'robots.txt', $siteDir.'aspro_regions/robots/robots_'.$arProperty["VALUE"].'.txt' );
					$arFile = file($siteDir.'aspro_regions/robots/robots_'.$arProperty["VALUE"].'.txt');
					foreach($arFile as $key => $str)
					{
						if(strpos($str, "Host" ) !== false)
							$arFile[$key] = "Host: ".(CMain::isHTTPS() ? "https://" : "http://").$arProperty["VALUE"]."\r\n";
						if(strpos($str, "Sitemap" ) !== false)
							$arFile[$key] = "Sitemap: ".(CMain::isHTTPS() ? "https://" : "http://").$arProperty["VALUE"]."/".$xml_file."\r\n";
					}
					$strr = implode("", $arFile);
					file_put_contents($siteDir.'aspro_regions/robots/robots_'.$arProperty["VALUE"].'.txt', $strr);
				}
			}
		}
	}

	static function onBeforeResultAddHandler($WEB_FORM_ID, &$arFields, &$arrVALUES){
		if(!defined('ADMIN_SECTION'))
		{
			global $APPLICATION;
			$arTheme = CMax::GetFrontParametrsValues(SITE_ID);

			if($arTheme['HIDDEN_CAPTCHA'] == 'Y' && $arrVALUES['nspm'] && !isset($arrVALUES['captcha_sid']))
		    	$APPLICATION->ThrowException(Loc::getMessage('ERROR_FORM_CAPTCHA'));

		  	if($arTheme['SHOW_LICENCE'] == 'Y' && ((!isset($arrVALUES['licenses_popup']) || !$arrVALUES['licenses_popup']) && (!isset($arrVALUES['licenses_inline']) || !$arrVALUES['licenses_inline'])))
		    	$APPLICATION->ThrowException(Loc::getMessage('ERROR_FORM_LICENSE'));
		}
	}

	static function OnSaleComponentOrderPropertiesHandler(&$arFields){
		global $arRegion;

		if(!$arRegion){
			$arRegion = CMaxRegionality::getCurrentRegion();
		}

		if($arRegion){
			if($_SERVER['REQUEST_METHOD'] != 'POST'){
				if($arRegion['LOCATION']){
		    		$arLocationProp = CSaleOrderProps::GetList(
				        array('SORT' => 'ASC'),
				        array(
			                'PERSON_TYPE_ID' => $arFields['PERSON_TYPE_ID'],
			                'TYPE' => 'LOCATION',
			                'IS_LOCATION' => 'Y',
			                'ACTIVE' => 'Y',
				        ),
				        false,
				        false,
				        array('ID')
				    )->Fetch();
				    if($arLocationProp){
						$arFields['ORDER_PROP'][$arLocationProp['ID']] = CSaleLocation::getLocationCODEbyID($arRegion['LOCATION']);
				    }

					$arLocationZipProp = CSaleOrderProps::GetList(
				        array('SORT' => 'ASC'),
				        array(
			                'PERSON_TYPE_ID' => $arFields['PERSON_TYPE_ID'],
			                'CODE' => 'ZIP',
			                'ACTIVE' => 'Y',
				        ),
				        false,
				        false,
				        array('ID')
				    )->Fetch();
				    if($arLocationZipProp){
						$rsLocaction = CSaleLocation::GetLocationZIP($arRegion['LOCATION']);
		    			$arLocation = $rsLocaction->Fetch();
		    			if($arLocation['ZIP']){
		    				$arFields['ORDER_PROP'][$arLocationZipProp['ID']] = $arLocation['ZIP'];
		    			}
		    		}
				}
			}
			else{
				if(isset($arFields['PERSON_TYPE_ID']) && isset($arFields['PERSON_TYPE_OLD'])){
					if($arFields['PROFILE_CHANGE'] === 'Y' || ($arFields['PERSON_TYPE_ID'] && $arFields['PERSON_TYPE_OLD'] && ($arFields['PERSON_TYPE_ID'] != $arFields['PERSON_TYPE_OLD']))){
						$arLocationProps = $arZipProps = array();

						$dbRes = CSaleOrderProps::GetList(
					        array('SORT' => 'ASC'),
					        array(
				                'PERSON_TYPE_ID' => array($arFields['PERSON_TYPE_ID'], $arFields['PERSON_TYPE_OLD']),
				                'TYPE' => 'LOCATION',
				                'IS_LOCATION' => 'Y',
				                'ACTIVE' => 'Y',
					        ),
					        false,
					        false,
					        array(
					        	'ID',
					        	'PERSON_TYPE_ID'
					        )
					    );
					    while($arLocationProp = $dbRes->Fetch()){
					    	$arLocationProps[$arLocationProp['PERSON_TYPE_ID']] = $arLocationProp['ID'];
					    }

					    if($arLocationProps){
					    	$arFields['ORDER_PROP'][$arLocationProps[$arFields['PERSON_TYPE_ID']]] = $_POST['order']['ORDER_PROP_'.$arLocationProps[$arFields['PERSON_TYPE_OLD']]];
					    }

					    $dbRes = CSaleOrderProps::GetList(
					        array('SORT' => 'ASC'),
					        array(
				                'PERSON_TYPE_ID' => array($arFields['PERSON_TYPE_ID'], $arFields['PERSON_TYPE_OLD']),
				                'CODE' => 'ZIP',
				                'ACTIVE' => 'Y',
					        ),
					        false,
					        false,
					        array(
					        	'ID',
					        	'PERSON_TYPE_ID'
					        )
					    );
					    while($arZipProp = $dbRes->Fetch()){
					    	$arZipProps[$arZipProp['PERSON_TYPE_ID']] = $arZipProp['ID'];
					    }

					    if($arZipProps){
					    	$arFields['ORDER_PROP'][$arZipProps[$arFields['PERSON_TYPE_ID']]] = $_POST['order']['ORDER_PROP_'.$arZipProps[$arFields['PERSON_TYPE_OLD']]];
						}
					}
				}
			}
		}
	}

	static function OnCatalogDeliveryComponentInitUserResult(&$arResult, &$arParams, $request){
		global $arRegion;

		if(!$arResult['LOCATION']){
			if(!$arRegion){
				$arRegion = CMaxRegionality::getCurrentRegion();
			}

			if($arRegion && $arRegion['LOCATION']){
				$arResult['LOCATION'] = CAsproCatalogDeliveryMax::getLocationByCode(CSaleLocation::getLocationCODEbyID($arRegion['LOCATION']), LANGUAGE_ID);
			}
		}
	}

	static function OnBeforeSubscriptionAddHandler(&$arFields){
		if(!defined('ADMIN_SECTION'))
		{
			global $APPLICATION;
			$arTheme = CMax::GetFrontParametrsValues(SITE_ID);
			if($arTheme['SHOW_LICENCE'] == 'Y' && (isset($_REQUEST['check_condition']) && $_REQUEST['check_condition'] == 'YES') && !isset($_REQUEST['licenses_subscribe']))
			{
				$APPLICATION->ThrowException(Loc::getMessage('ERROR_FORM_LICENSE'));
				return false;
			}
		}
	}

	static function onAfterResultAddHandler($WEB_FORM_ID, $RESULT_ID){
		if(Option::get(self::moduleID, 'AUTOMATE_SEND_FLOWLU', 'Y') == 'Y')
			\Aspro\Functions\CAsproMax::sendLeadCrmFromForm($WEB_FORM_ID, $RESULT_ID, 'FLOWLU');
		if(Option::get(self::moduleID, 'AUTOMATE_SEND_AMO_CRM', 'Y') == 'Y')
			\Aspro\Functions\CAsproMax::sendLeadCrmFromForm($WEB_FORM_ID, $RESULT_ID, 'AMO_CRM');

		\Aspro\Functions\CAsproMax::sendResultToIBlock($WEB_FORM_ID, $RESULT_ID);
	}

	public static function OnBeforeCommentAddHandler(&$arFields){
		if(isset($_REQUEST['rating'])) {
			$arFields['UF_ASPRO_COM_RATING'] = $_REQUEST['rating'];
		}

		if( isset($arFields['POST_TEXT']) ) {
			$arFields['POST_TEXT'] = strip_tags($arFields['POST_TEXT'], '<virtues><limitations><comment>');
		}
	}

	public static function OnCommentAddHandler($commentID, &$arFields){
		if($_FILES['comment_images']) {
			$maxSize = $_SESSION['BLOG_MAX_IMAGE_SIZE'] * 1024 * 1024;

			foreach ($_FILES['comment_images']['name'] as $key => $imgName) {
				if($maxSize && $_FILES['comment_images']['size'][$key] > $maxSize) {
					$notAdded[] = $imgName;
					continue;
				}
				$fileArray = Array(
    				"name" => $imgName,
					"size" => $_FILES['comment_images']['size'][$key],
    				"tmp_name" => $_FILES['comment_images']['tmp_name'][$key],
    				"type" => $_FILES['comment_images']['type'][$key],
				    "MODULE_ID" => "blog",
				);
				$fileId = CFile::SaveFile($fileArray, '/blog/comment/');
				if($fileId) {
					$filesToAttach[$key] = $fileId;
				}
			}
			
			unset($_FILES['comment_images']);
		}

		if($filesToAttach) {
			foreach ($filesToAttach as $imageKey => $imageId) {
				CBlogImage::Add(array(
					'FILE_ID' => $imageId,
					"POST_ID" => $arFields["POST_ID"],
					"BLOG_ID" => $arFields["BLOG_ID"],
					"COMMENT_ID" => IntVal($commentID),
					'IMAGE_SIZE' => $_FILES['comment_images']['size'][$imageKey],
				));
			}
		}

		if($notAdded) {
			$_SESSION['NOT_ADDED_FILES']['ID'] = $commentID;
			$_SESSION['NOT_ADDED_FILES']['FILES'] = $notAdded;
		}

		CMax::updateExtendedReviewsProps($commentID);

	}

	public static function OnBeforeCommentUpdateHandler($id, &$arFields){
		if( isset($_REQUEST['rating']) ) {
			global $USER_FIELD_MANAGER; 
			$USER_FIELD_MANAGER->Update("BLOG_COMMENT", $id, array('UF_ASPRO_COM_RATING' => $_REQUEST['rating']));
		}

		if( isset($arFields['POST_TEXT']) ) {
			$arFields['POST_TEXT'] = strip_tags($arFields['POST_TEXT'], '<virtues><limitations><comment>');
		}
	}

	public static function OnCommentUpdateHandler($commentID, &$arFields){
		if( isset($_REQUEST['rating']) ) {
			$resImages = CBlogImage::GetList(array("ID"=>"DESC"), array('COMMENT_ID' => $commentID));
			while($arImage = $resImages->Fetch()) {
				CFile::Delete($arImage['FILE_ID']);
				CBlogImage::Delete($arImage['ID']);
			}
	
			if(isset($_FILES['comment_images']['name'][0]) && $_FILES['comment_images']['name'][0]) {
	
				$maxSize = $_SESSION['BLOG_MAX_IMAGE_SIZE'] * 1024 * 1024;
	
				foreach ($_FILES['comment_images']['name'] as $key => $imgName) {
					if($maxSize && $_FILES['comment_images']['size'][$key] > $maxSize)
						continue;
					$fileArray = Array(
						"name" => $imgName,
						"size" => $_FILES['comment_images']['size'][$key],
						"tmp_name" => $_FILES['comment_images']['tmp_name'][$key],
						"type" => $_FILES['comment_images']['type'][$key],
						"MODULE_ID" => "blog",
					);
					$fileId = CFile::SaveFile($fileArray, '/blog/comment/');
					if($fileId) {
						$filesToAttach[$key] = $fileId;
					}
				}
	
				unset($_FILES['comment_images']);
			}
	
			if($filesToAttach) {
				foreach ($filesToAttach as $imageKey => $imageId) {
					CBlogImage::Add(array(
						'FILE_ID' => $imageId,
						"POST_ID" => $arFields["POST_ID"],
						"BLOG_ID" => $arFields["BLOG_ID"],
						"COMMENT_ID" => IntVal($commentID),
						'IMAGE_SIZE' => $_FILES['comment_images']['size'][$imageKey],
					));
				}
			}
		}

		CMax::updateExtendedReviewsProps($commentID);

	}

	public static function OnCommentDeleteHandler($ID){
		$resImages = CBlogImage::GetList(array("ID"=>"DESC"), array('COMMENT_ID' => $ID));
		while($arImage = $resImages->Fetch()) {
			CFile::Delete($arImage['FILE_ID']);
		}

		CMax::updateExtendedReviewsProps($ID, 'delete');
	}
}