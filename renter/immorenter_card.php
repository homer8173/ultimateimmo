<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2022 Philippe GRAND  <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       immorenter_card.php
 *		\ingroup    ultimateimmo
 *		\brief      Page to create/edit/view immorenter
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/ultimateimmo/class/immorenter.class.php');
dol_include_once('/ultimateimmo/class/immoowner.class.php');
dol_include_once('/ultimateimmo/lib/immorenter.lib.php');
dol_include_once('/ultimateimmo/class/immorent.class.php');
dol_include_once('/ultimateimmo/class/immoproperty.class.php');
dol_include_once('/ultimateimmo/class/immorentersoc.class.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("ultimateimmo@ultimateimmo", "other", "members", "users"));

// Get parameters
$id = GETPOST('id') ? GETPOST('id', 'int') : $rowid;
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$socid		= GETPOST('socid', 'int');

// Initialize technical objects
$object = new ImmoRenter($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->ultimateimmo->dir_output . '/temp/massgeneration/' . $user->id;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('immorentercard', 'globalcard'));     

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) $search[$key] = GETPOST('search_' . $key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'mymodule', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissiontoread = $user->hasRight('ultimateimmo', 'read');
$permissiontoadd = $user->hasRight('ultimateimmo', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('ultimateimmo', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->hasRight('ultimateimmo', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('ultimateimmo', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->ultimateimmo->multidir_output[isset($object->entity) ? $object->entity : 1];

/*
 * Actions
 *
 */

$parameters = array('id'=>$id, 'rowid'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;
	$backurlforlist = dol_buildpath('/ultimateimmo/renter/immorenter_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/ultimateimmo/renter/immorenter_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	$triggermodname = 'ULTIMATEIMMO_IMMORENTER_MODIFY';

	if ($action == 'setsocid') {
		$error = 0;
		if (!$error) {
			
			if ($socid != $object->socid) { // If link differs from currently in database
				$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "ultimateimmo_immorenter";
				$sql .= " WHERE fk_soc = '" . $socid . "'";
				$sql .= " AND entity = " . $conf->entity;
				$resql = $db->query($sql);
				
				if ($resql) {
					$obj = $db->fetch_object($resql);
					
					if ($obj && $obj->rowid > 0) {
						$otherrenter = new ImmoRenter($db);
						$otherrenter->fetch($obj->rowid);
						$thirdparty = new Societe($db);
						$thirdparty->fetch($socid);
						$error++;
						//var_dump($socid);exit;
						setEventMessages($langs->trans("ErrorRenterIsAlreadyLinkedToThisThirdParty", $otherrenter->getFullName($langs), $otherrenter->login, $thirdparty->name), null, 'errors');
					}
				}
				
				if (!$error) {
					$result = $object->setThirdPartyId($socid);
					if ($result < 0) {
						dol_print_error($object->db, $object->error);
					}
					$action = '';
				}
			}
		}
	}

	// Create third party from a renter
	if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
		if ($result > 0) {
			// Thirdparty creation
			$company = new RenterSoc($db);
			$result = $company->create_from_renter($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'));
			
			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans($company->error), null, 'errors');
				setEventMessages($company->error, $company->errors, 'errors');
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Auto-create thirdparty on renter creation
	if (!empty($conf->global->RENTER_DEFAULT_CREATE_THIRDPARTY)) {
		if ($result > 0) {
			// User creation
			$company = new RenterSoc($db);

			$companyalias = '';
			$fullname = $object->getFullName($langs);

			if ($object->morphy == 'mor') {
				$companyname = $object->company;
				if (!empty($fullname)) {
					$companyalias = $fullname;
				}
			} else {
				$companyname = $fullname;
				if (!empty($object->company)) {
					$companyalias = $object->company;
				}
			}

			$result = $company->create_from_renter($object, $companyname, $companyalias);

			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans($company->error), null, 'errors');
				setEventMessages($company->error, $company->errors, 'errors');
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Actions cancel, add, update or delete
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'IMMORENTER_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'IMMORENTER_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_IMMORENTER_TO';
	$trackid = 'immorenter'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 *
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$wikihelp = 'EN:Module_Ultimateimmo_EN#Owners|FR:Module_Ultimateimmo_FR#Création_des_locataires';
llxHeader('', $langs->trans('ImmoRenter'), $wikihelp);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("ImmoRenter")), '', 'object_' . $object->picto);

	if ($conf->use_javascript_ajax) {
		print "\n" . '<script type="text/javascript" language="javascript">';
		print 'jQuery(document).ready(function () {
				jQuery("#country_id").change(function() {
					document.formsoc.action.value="create";
					document.formsoc.submit();
				});
				function initfieldrequired() {
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\') {
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\') {
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				
				initfieldrequired();
			})';
		print '</script>' . "\n";
	}

	print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';
	}
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');

	foreach ($object->fields as $key => $val) {
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3) {
			continue;
		}
	
		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
			continue; // We don't want this field
		}

		print '<tr class="field_' . $key . '">';
		print '<td';
		print ' class="titlefieldcreate';
		if (isset($val['notnull']) && $val['notnull'] > 0) {
			print ' fieldrequired';
		}
		if ($val['type'] == 'text' || $val['type'] == 'html') {
			print ' tdtop';
		}
		print '"';
		print '>';
		if (!empty($val['help'])) {
			print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			print $langs->trans($val['label']);
		}
		print '</td>';
		print '<td class="valuefieldcreate">';
		if ($val['label'] == 'MorPhy') {
			$morphys["phy"] = $langs->trans("Physical");
			$morphys["mor"] = $langs->trans("Moral");
			print $form->selectarray("morphy", $morphys, (GETPOST('morphy', 'alpha') ? GETPOST('morphy', 'alpha') : $object->morphy), 1, 0, 0, '', 0, 0, 0, '', '', 1);
			//print $object->getmorphylib();
		}
		elseif ($val['label'] == 'Civility') {
			// We set civility_id, civility_code and civility for the selected civility
			$object->civility_id	= GETPOST("civility_id", 'int') ? GETPOST('civility_id', 'int') : $object->civility_id;

			if ($object->civility_id) {
				$tmparray = array();
				$tmparray = $object->getCivilityLabel($object->civility_id, 'all');
				if (in_array($tmparray['code'], $tmparray)) $object->civility_code = $tmparray['code'];
				if (in_array($tmparray['label'], $tmparray)) $object->civility = $tmparray['label'];
			}
			// civility
			print $object->select_civility(GETPOSTISSET("civility_id") != '' ? GETPOST("civility_id", 'int') : $object->civility_id, 'civility_id');
		} elseif ($val['label'] == 'BirthDay') {
			print $form->selectDate(($object->birth ? $object->birth : -1), 'birth', '', '', 1, 'formsoc');
		} elseif ($val['label'] == 'ImmoBirthCountry') {
			// We set country_id, country_code and country for the selected country
			$object->country_id = GETPOST('country_id', 'int') ? GETPOST('country_id', 'int') : $object->country_id;
			if ($object->country_id) {
				$tmparray = $object->getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country = $tmparray['label'];
			}
			// Country
			print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		} else {
			if (!empty($val['picto'])) {
				print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
			}
			if (in_array($val['type'], array('int', 'integer'))) {
				$value = GETPOST($key, 'int');
			} elseif ($val['type'] == 'double') {
				$value = price2num(GETPOST($key, 'alphanohtml'));
			} elseif ($val['type'] == 'text' || $val['type'] == 'html') {
				$value = GETPOST($key, 'restricthtml');
			} elseif ($val['type'] == 'date') {
				$value = dol_mktime(12, 0, 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
			} elseif ($val['type'] == 'datetime') {
				$value = dol_mktime(GETPOST($key.'hour', 'int'), GETPOST($key.'min', 'int'), 0, GETPOST($key.'month', 'int'), GETPOST($key.'day', 'int'), GETPOST($key.'year', 'int'));
			} elseif ($val['type'] == 'boolean') {
				$value = (GETPOST($key) == 'on' ? 1 : 0);
			} elseif ($val['type'] == 'price') {
				$value = price2num(GETPOST($key));
			} elseif ($key == 'lang') {
				$value = GETPOST($key, 'aZ09');
			} else {
				$value = GETPOST($key, 'alphanohtml');
			}
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key, $value, '', '', '', 0);
				}
			}
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>' . "\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="javascript:history.go(-1)"') . '>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("ImmoRenter"), '', 'object_'.$object->picto);

	if ($conf->use_javascript_ajax) {
		print "\n".'<script type="text/javascript">';
		print 'jQuery(document).ready(function () {
			jQuery("#country_id").change(function() {
				document.formsoc.action.value="edit";
				document.formsoc.submit();
			});
			function initfieldrequired() {
				jQuery("#tdcompany").removeClass("fieldrequired");
				jQuery("#tdlastname").removeClass("fieldrequired");
				jQuery("#tdfirstname").removeClass("fieldrequired");
				if (jQuery("#morphy").val() == \'mor\') {
					jQuery("#tdcompany").addClass("fieldrequired");
				}
				if (jQuery("#morphy").val() == \'phy\') {
					jQuery("#tdlastname").addClass("fieldrequired");
					jQuery("#tdfirstname").addClass("fieldrequired");
				}
			}
			jQuery("#morphy").change(function() {
				initfieldrequired();
			});
			initfieldrequired();
		})';
		print '</script>'."\n";
	}

	print '<form name="formsoc" action="' . $_SERVER["PHP_SELF"] . '" method="post" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
	}
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');

	foreach ($object->fields as $key => $val) {
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
			continue;
		}
	
		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
			continue; // We don't want this field
		}

		print '<tr class="field_' . $key . '"><td';
		print ' class="titlefieldcreate';
		if (isset($val['notnull']) && $val['notnull'] > 0) {
			print ' fieldrequired';
		}
		if (preg_match('/^(text|html)/', $val['type'])) {
			print ' tdtop';
		}
		print '">';
		if (!empty($val['help'])) {
			print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			print $langs->trans($val['label']);
		}
		print '</td>';
		print '<td class="valuefieldcreate">';

		if ($val['label'] == 'MorPhy') {
			$morphys["phy"] = $langs->trans("Physical");
			$morphys["mor"] = $langs->trans("Moral");
			print $form->selectarray("morphy", $morphys, (GETPOSTISSET("morphy") ? GETPOST("morphy", 'alpha') : $object->morphy), 0, 0, 0, '', 0, 0, 0, '', '', 1);
		}
		elseif ($val['label'] == 'Civility') {
			// We set civility_id, civility_code and civility for the selected civility
			$object->civility_id = GETPOST('civility_id', 'int') ? GETPOST('civility_id', 'int') : $object->civility_id;
			if ($object->civility_id) {
				$tmparray = $object->getCivilityLabel($object->civility_id, 'all');
				$object->civility_code = $tmparray['code'];
				$object->civility = $tmparray['label'];
			}
			print $object->select_civility(GETPOSTISSET("civility_id") != '' ? GETPOST("civility_id", 'int') : $object->civility_id);	
		} elseif ($val['label'] == 'BirthDay') {
			print $form->selectDate(($object->birth ? $object->birth : -1), 'birth', '', '', 1, 'formsoc');
		} elseif ($val['label'] == 'ImmoBirthCountry') {
			// We set country_id, country_code and country for the selected country
			$object->country_id = GETPOST('country_id', 'int') ? GETPOST('country_id', 'int') : $object->country_id;
			if ($object->country_id) {
				$tmparray = $object->getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country = $tmparray['label'];
			}
			// Country
			print $form->select_country((GETPOST('country_id') != '' ? GETPOST('country_id') : $object->country_id));
		} else {
			if (!empty($val['picto'])) {
				print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
			}
			if (in_array($val['type'], array('int', 'integer'))) {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'int') : $object->$key;
			} elseif ($val['type'] == 'double') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $object->$key;
			} elseif (preg_match('/^(text|html)/', $val['type'])) {
				$tmparray = explode(':', $val['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'restricthtml';
				}
				$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $object->$key;
			} elseif ($val['type'] == 'price') {
				$value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($object->$key);
			} elseif ($key == 'lang') {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'aZ09') : $object->lang;
			} else {
				$value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $object->$key;
			}
			//var_dump($val.' '.$key.' '.$value);
			if (!empty($val['noteditable'])) {
				print $object->showOutputField($val, $key, $value, '', '', '', 0);
			} else {
				if ($key == 'lang') {
					print img_picto('', 'language', 'class="pictofixedwidth"');
					print $formadmin->select_language($value, $key, 0, null, 1, 0, 0, 'minwidth300', 2);
				} else {
					print $object->showInputField($val, $key, $value, '', '', '', 0);
				}
			}
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Save', 'Cancel');

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch($id);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}
	$res = $object->fetch_optionals();
	if ($res < 0) {
		dol_print_error($db);
		exit;
	}

	/*
	 * Show tabs
	 */
	$head = immorenterPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("ImmoRenter"), -1, 'user');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteImmoRenter'), $langs->trans('ConfirmDeleteImmoRenter'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneImmoRenter'), $langs->trans('ConfirmCloneImmoRenter', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirm create third party
	if ($action == 'create_thirdparty') {
		$companyalias = '';
		$fullname = $object->getFullName($langs);

		if ($object->morphy == 'mor') {
			$companyname = $object->company;
			if (!empty($fullname)) {
				$companyalias = $fullname;
			}
		} else {
			$companyname = $fullname;
			if (!empty($object->company)) {
				$companyalias = $object->company;
			}
		}
		// Create a form array
		$formquestion = array(
			array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'),
			array('label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"')
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . "?id=" . $object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 'yes');
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/ultimateimmo/renter/immorenter_list.php', 1) . '?restore_lastsearch_values=0' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="' . dol_buildpath('/ultimateimmo/rent/immorent_list.php', 1) . '?socid=' . $object->thirdparty->id . '&search_fk_soc=' . urlencode($object->thirdparty->id) . '">' . $langs->trans("OtherRents") . '</a>)';

	// Project
	if (isModEnabled('projet')) {
		$langs->load("projects");
		$morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
		if ($permissiontoadd) {
			if ($action != 'classify') $morehtmlref .= '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
			$morehtmlref .= ' : ';
			if ($action == 'classify') {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="' . newToken() . '">';
				//$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				//$morehtmlref .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= ': ' . $proj->getNomUrl();
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');
	$keyforbreak='civility_id'; // We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	foreach ($object->fields as $key => $val) {
		if (!empty($keyforbreak) && $key == $keyforbreak) break; // key used for break on second column

		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) continue;

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) continue;	// We don't want this field
		if (in_array($key, array('ref', 'status'))) continue;	// Ref and status are already in dol_banner

		$value = $object->$key;

		print '<tr><td';
		print ' class="titlefield fieldname_' . $key;
		//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
		if ($val['type'] == 'text' || $val['type'] == 'html'
		) print ' tdtop';
		print '">';
		if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		else print $langs->trans($val['label']);
		print '<td>';
		print '<td class="valuefield fieldname_' . $key;
		if ($val['type'] == 'text') print ' wordbreak';
		print '">';
		print '<td>';

		if ($val['label'] == 'LinkedToDolibarrThirdParty') {
			// Third party Dolibarr
			if (isModEnabled('societe')) {

				if ($object->fk_soc) {

					$company = new Societe($db);
					$result = $company->fetch($object->fk_soc);
					print $company->getNomUrl(1);
					// Show link to invoices
					$tmparray = $company->getOutstandingBills('customer');
					if (!empty($tmparray['refs'])) {
						print ' - ' . img_picto($langs->trans("Invoices"), 'bill', 'class="paddingright"') . '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->fk_soc . '">' . $langs->trans("Invoices") . ' (' . count($tmparray['refs']) . ')';
						// TODO Add alert if warning on at least one invoice late
						print '</a>';
					}
				} else {
					print '<span class="opacitymedium">' . $langs->trans("NoThirdPartyAssociatedToRenter") . '</span>';
				}
			}
		} elseif ($val['label'] == 'MorPhy') {
			print $object->getmorphylib();
		} elseif ($val['label'] == 'Owner') {
			$staticowner = new ImmoOwner($db);
			$staticowner->fetch($object->fk_owner);
			if ($staticowner->ref) {
				$staticowner->ref =  $staticowner->getNomUrl(0)/* . ' - ' . $staticowner->getFullName($langs, 0)*/;
			}
			print $staticowner->ref;
		} elseif ($val['label'] == 'ImmoRent') {
			$staticrent = new ImmoRent($db);
			$staticrent->fetch($object->fk_rent);
			$staticproperty = new ImmoProperty($db);
			$staticproperty->fetch($staticrent->fk_property);
			if ($staticrent->ref) {
				$staticrent->ref = $staticrent->getNomUrl(0)/* . ' - ' . $staticproperty->label*/;
			}
			print $staticrent->ref;
		} else {
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}
	
		//print dol_escape_htmltag($object->$key, 1, 1);
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	// We close div and reopen for second column
	print '</div>';
	print '<div class="fichehalfright">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	$alreadyoutput = 1;
	foreach ($object->fields as $key => $val) {
		if ($alreadyoutput) {
			if (!empty($keyforbreak) && $key == $keyforbreak) {
				$alreadyoutput = 0; // key used for break on second column
			} else {
				continue;
			}
		}

		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) continue;

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled']) continue;	// We don't want this field
		if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner

		$value = $object->$key;

		print '<tr><td';
		print ' class="titlefield fieldname_' . $key;
		//if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired in the view output
		if ($val['label'] == 'Civility') {
			// We set civility_id, civility_code and civility for the selected civility	
			if ($object->civility_id) {
				$tmparray = $object->getCivilityLabel($object->civility_id, 'all');
				$object->civility_code = $tmparray['code'];
				$object->civility = $tmparray['label'];
			}
			print '<tr><td width="25%">' . $langs->trans('Civility') . '</td><td>';
			print $object->civility;
		} elseif ($val['label'] == 'ImmoBirthCountry') {
			if ($object->country_id) {
				include_once(DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
				$tmparray = getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country = $tmparray['label'];
			}
			print '<tr><td width="25%">' . $langs->trans('Country') . '</td><td>';
			print $object->country;
		} else {
			if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
			print '">';
			if (!empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
			else print $langs->trans($val['label']);
			print '</td>';
			print '<td>';
			print $object->showOutputField($val, $key, $value, '', '', '', 0);
		}
		//print dol_escape_htmltag($object->$key, 1, 1);
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	print dol_get_fiche_end();

	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
    	// Show object lines
    	$result = $object->getLinesArray();

    	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
    	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'].'">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
    	<input type="hidden" name="mode" value="">
    	<input type="hidden" name="id" value="' . $object->id.'">
    	';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) 
		{
    	    include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
    	}

    	print '<div class="div-table-responsive-no-min">';
    	if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
    	{
    	    print '<table id="tablelines" class="noborder noshadow" width="100%">';
    	}

    	if (!empty($object->lines))
    	{
    		$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
    	}

    	// Form to add new line
    	if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
    	{
    	    if ($action != 'editline')
    	    {
    	        // Add products/services form
    	        $object->formAddObjectLine(1, $mysoc, $soc);

    	        $parameters = array();
    	        $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    	    }
    	}

    	if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
    	{
    	    print '</table>';
    	}
    	print '</div>';

    	print "</form>\n";
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') 
	{
    	print '<div class="tabsAction">'."\n";
    	$parameters = array();
    	$reshook = $hookmanager->executeHooks('addMoreActionsButtons',$parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    	if (empty($reshook))
    	{
    	    // Send
            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";

    		if ($permissiontoadd)
    		{
    			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
    		}
    		else
    		{
    			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
    		}

			// Create third party
			if (isModEnabled('societe') && !$object->fk_soc) {
				if ($user->rights->societe->creer) {
					if (ImmoRenter::STATUS_DRAFT != $object->status) {
						print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=create_thirdparty" title="' . dol_escape_htmltag($langs->trans("CreateDolibarrThirdPartyDesc")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
					}
				} else {
					print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</span>' . "\n";
				}
			}

			// Clone
    		if ($permissiontoadd)
    		{
    			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&object=myobject">'.$langs->trans("ToClone").'</a>'."\n";
    		}

    		if ($permissiontodelete )
    		{
    			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>'."\n";
    		}
    		else
    		{
    			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
    		}
    	}
    	print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) 
	{
	    $action = 'presend';
	}

	if ($action != 'presend')
	{
	    print '<div class="fichecenter"><div class="fichehalfleft">';
	    print '<a name="builddoc"></a>'; // ancre

	    // Documents
	    $relativepath = '/renter/' . dol_sanitizeFileName($object->ref);
	    $filedir = $conf->ultimateimmo->dir_output . $relativepath;
	    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	    $genallowed = $permissiontoread;	// If you can read, you can build the PDF to read content
	    $delallowed = $permissiontodelete;	// If you can create/edit, you can remove a file on card
	    print $formfile->showdocuments('ultimateimmo', $relativepath, $filedir, $urlsource, 0, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $object->default_lang, '', $object);

	    // Show links to link elements
	    $linktoelem = $form->showLinkToObjectBlock($object, null, array('immorenter'));
	    $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.dol_buildpath('/ultimateimmo/renter/immorenter_agenda.php', 1).'?id='.$object->id.'">';
	    $morehtmlright.= $langs->trans("SeeAll");
	    $morehtmlright.= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    $somethingshown = $formactions->showactions($object, 'immorenter', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

	    print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	// Presend form
	$modelmail = 'immorenter';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->ultimateimmo->dir_output.'/renter';
	$trackid = 'immo'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

}

// End of page
llxFooter();
$db->close();
