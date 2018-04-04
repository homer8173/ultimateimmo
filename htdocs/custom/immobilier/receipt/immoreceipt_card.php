<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Philippe GRAND 	<philippe.grand@atoo-net.com>
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
 *   	\file       immoreceipt_card.php
 *		\ingroup    immobilier
 *		\brief      Page to create/edit/view immoreceipt
 */

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');		// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test done when option MAIN_SECURITY_CSRF_WITH_TOKEN is on.
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))              define('NOIPCHECK','1');				// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))      define('MAIN_LANG_DEFAULT','auto');
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
include_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
dol_include_once('/immobilier/class/immoreceipt.class.php');
dol_include_once('/immobilier/lib/immoreceipt.lib.php');
dol_include_once('/immobilier/core/modules/immobilier/modules_immobilier.php');
dol_include_once('/immobilier/class/immorent.class.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("immobilier@immobilier", "other", "compta", "bills"));

// Get parameters
$id			= GETPOST('id', 'int');
$rowid 		= GETPOST('rowid', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object=new ImmoReceipt($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->immobilier->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('immoreceiptcard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('immoreceipt');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Security check - Protection if external user
//if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
//$result = restrictedArea($user, 'immobilier', $id);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals



/*
 * Actions
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error=0;

	$permissiontoadd = $user->rights->immobilier->write;
	$permissiontodelete = $user->rights->immobilier->delete;
	$backurlforlist = dol_buildpath('/immobilier/receipt/immoreceipt_list.php',1);

	// Actions cancel, add, update or delete
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$trigger_name='MYOBJECT_SENTBYMAIL';
	$autocopy='MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid='immoreceipt'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * 	Classify paid
 */
if ($action == 'paid') 
{
	$receipt = new ImmoReceipt($db);
	$receipt->fetch($id);
	$result = $receipt->set_paid($user);
	Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
}

/*
 *	Delete rental
 */
if ($action == 'confirm_delete' && $_REQUEST["confirm"] == 'yes') 
{
	$receipt = new ImmoReceipt($db);
	$receipt->fetch($id);
	$result = $receipt->delete($user);
	if ($result > 0) 
	{
		header("Location: ".$backurlforlist);
		exit();
	} 
	else 
	{
		$mesg = '<div class="error">' . $receipt->error . '</div>';
	}
}

/*
 * Action generate quittance
 */
if ($action == 'quittance') {
	// Define output language
	$outputlangs = $langs;
	
	$file = 'quittance_' . $id . '.pdf';
	
	$result = immobilier_pdf_create($db, $id, '', 'quittance', $outputlangs, $file);
	
	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessages($receipt->error, $receipt->errors, 'errors');
	}
}

/*
 * Action generate charge locative
 */
if ($action == 'chargeloc') {
	// Define output language
	$outputlangs = $langs;
	
	$file = 'chargeloc_' . $id . '.pdf';
	
	$result = immobilier_pdf_create($db, $id, '', 'chargeloc', $outputlangs, $file);
	
	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessages($receipt->error, $receipt->errors, 'errors');
	}
}

/*
 * Add rental
 */
if ($action == 'add' && ! $cancel) 
{
	$error = 0;
	
	$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth"), GETPOST("datevday"), GETPOST("datevyear"));
	$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth"), GETPOST("datespday"), GETPOST("datespyear"));
	$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth"), GETPOST("dateepday"), GETPOST("dateepyear"));
	
	$object->nom = GETPOST("nom");
	$object->datesp = $datesp;
	$object->dateep = $dateep;
	$object->datev = $datev;
	
	if (empty($datev) || empty($datesp) || empty($dateep)) 
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), 'errors');
		$error ++;
	}
	
	if (! $error) 
	{
		$db->begin();
		
		$ret = $object->create($user);
		if ($ret > 0) {
			$db->commit();
			header("Location: index.php");
			exit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = "create";
		}
	}
	
	$action = 'create';
}

/*
 * Add all rental
 */

if ($action == 'addall') 
{	
	$error=0;
	$dateech = dol_mktime(12,0,0, GETPOST("echmonth"), GETPOST("echday"), GETPOST("echyear"));
	$dateperiod = dol_mktime(12,0,0, GETPOST("periodmonth"), GETPOST("periodday"), GETPOST("periodyear"));
	$dateperiodend = dol_mktime(12,0,0, GETPOST("periodendmonth"), GETPOST("periodendday"), GETPOST("periodendyear"));
	
	if (empty($dateech)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateDue")), 'errors');
		$action = 'create';
	} elseif (empty($dateperiod)) {
		$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Period")) . '</div>';
		$action = 'create';
	} elseif (empty($dateperiodend)) {
		$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Periodend")) . '</div>';
		$action = 'create';
	} 
	else 
	{
		$mesLignesCochees = GETPOST('mesCasesCochees');
		if(is_array($mesLignesCochees) && !empty($mesLignesCochees))
        {	
			foreach ( $mesLignesCochees as $maLigneCochee ) 
			{
				
				$receipt = new ImmoReceipt($db);
				
				$maLigneCourante = split("_", $maLigneCochee);
				$monId = $maLigneCourante[0];
				$monLocal = $maLigneCourante[1];
				$monLocataire = $maLigneCourante[2];
				$monMontant = $maLigneCourante[3];
				$monLoyer = $maLigneCourante[4];
				$monCharges = $maLigneCourante[5];
				$monTVA = $maLigneCourante[7];
				$monOwner = $maLigneCourante[6];
				
				// main info loyer
				$receipt->name = GETPOST('name', 'alpha');
				$receipt->echeance = $dateech;
				$receipt->date_start = $dateperiod;
				$receipt->date_end = $dateperiodend;
				
				// main info contrat
				$receipt->fk_contract = $monId;
				$receipt->fk_property = $monLocal;
				$receipt->fk_renter = $monLocataire;
				$receipt->fk_owner = $monOwner;
				If ($monTVA == Oui) {
				$receipt->total_amount = $monMontant * 1.2;
				$receipt->vat = $monMontant * 0.2;}
				Else {
				$receipt->total_amount = $monMontant;}
				
				$receipt->rent = $monLoyer;
				$receipt->charges = $monCharges;
				$receipt->statut=0;
				$receipt->paye=0;
				
				$result = $receipt->create($user);
				if ($result < 0) {
					$error++;
					setEventMessages(null, $receipt->errors, 'errors');
					$action='createall';
				}
			}
		}
	}
	
	if (empty($error)) 
	{
		setEventMessages(null, $langs->trans("SocialContributionAdded"), 'mesgs');
		Header("Location: " .$backurlforlist);
		exit();
	}
}

/*
 * Edit Receipt
 */

if ($action == 'update')
{
	$dateech = @dol_mktime(12,0,0, GETPOST("echmonth"), GETPOST("echday"), GETPOST("echyear"));
	$dateperiod = @dol_mktime(12,0,0, GETPOST("periodmonth"), GETPOST("periodday"), GETPOST("periodyear"));
	$dateperiodend = @dol_mktime(12,0,0, GETPOST("periodendmonth"), GETPOST("periodendday"), GETPOST("periodendyear"));
	/*if (! $dateech)
	 {
	 $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DateDue")).'</div>';
	 $action = 'update';
	 }
	 elseif (! $dateperiod)
	 {
	 $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Period")).'</div>';
	 $action = 'update';
	 }
	 elseif (! $dateperiodend)
	 {
	 $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Periodend")).'</div>';
	 $action = 'update';
	 }
	 else
	 {
	 */
	$receipt = new ImmoReceipt($db);
	$result = $receipt->fetch($id);
	
	$receipt->label 			= GETPOST('label');
	If ($receipt->addtva != 0) {
	$receipt->total_amount 	= ($_POST["rentamount"] + $_POST["charges"])*1.2;}
	Else {
	$receipt->total_amount 	= $_POST["rentamount"] + $_POST["charges"];}
	$receipt->rentamount 			= $_POST["rentamount"];
	$receipt->charges 		= $_POST["charges"];
	If ($receipt->addtva != 0) {
	$receipt->vat 			= ($_POST["rentamount"]+$_POST["charges"])*0.2;}
	Else {
	$receipt->vat 			= 0;}
	
	$receipt->echeance 		= $dateech;
	$receipt->note_public 	= $_POST["note_public"];
	$receipt->status 		= $_POST["status"];
	$receipt->date_start 	= $dateperiod;
	$receipt->date_end 		= $dateperiodend;
	
	$result = $receipt->update($user);
	header("Location: " .$backurlforlist.'?id='.$receipt->id);
	if ($id > 0) 
	{
		// $mesg='<div class="ok">'.$langs->trans("SocialContributionAdded").'</div>';
	} else 
	{
		$mesg = '<div class="error">' . $receipt->error . '</div>';
	}
}


/*
 * View
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('','ImmoReceipt','');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to create
if ($action == 'create')
{	
	$year_current = strftime("%Y", dol_now());
	$pastmonth = strftime("%m", dol_now());
	$pastmonthyear = $year_current;
	if ($pastmonth == 0) {
		$pastmonth = 12;
		$pastmonthyear --;
	}
	
	$datesp = dol_mktime(0, 0, 0, $datespmonth, $datespday, $datespyear);
	$dateep = dol_mktime(23, 59, 59, $dateepmonth, $dateepday, $dateepyear);
	
	if (empty($datesp) || empty($dateep)) // We define date_start and date_end
	{
		$datesp = dol_get_first_day($pastmonthyear, $pastmonth, false);
		$dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
	}
	
	print load_fiche_titre($langs->trans("NewReceipt", $langs->transnoentitiesnoconv("ImmoReceipt")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';
	/*print "<tr>";
	print '<td class="fieldrequired"><label for="rent">' . $langs->trans("Rent") . '</label></td><td>';
	print '<input name="rent" id="rent" size="30" value="' . $object->label . '"</td>';
	print '</td></tr>';
	
	print '<tr><td><label for="datev">' . $langs->trans("DateValue") . '</label></td><td>';
	print $form->select_date((empty($datev) ? - 1 : $datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';
	
	print "<tr>";
	print '<td class="fieldrequired"><label for="datesp">' . $langs->trans("DateStartPeriod") . '</label></td><td>';
	print $form->select_date($datesp, "datesp", '', '', '', 'add');
	print '</td></tr>';
	
	print '<tr><td class="fieldrequired"><label for="dateep">' . $langs->trans("DateEndPeriod") . '</label></td><td>';
	print $form->select_date($dateep, "dateep", '', '', '', 'add');
	print '</td></tr>';*/

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}
/* *************************************************************************** */
/*                                                                             */
/* Mode add all contract                                                       */
/*                                                                             */
/* *************************************************************************** */

elseif ($action == 'createall') 
{
	print load_fiche_titre($langs->trans("newrental", $langs->transnoentitiesnoconv("ImmoReceipt")));
	
	print '<form name="fiche_loyer" method="post" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="addall">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	
	dol_fiche_head();
	
	print '<table class="border" width="100%">';
	
	print "<tr class=\"liste_titre\">";
	
	print '<td align="left">';
	print $langs->trans("NomLoyer");
	print '</td><td align="center">';
	print $langs->trans("Echeance");
	print '</td><td align="center">';
	print $langs->trans("Periode_du");
	print '</td><td align="center">';
	print $langs->trans("Periode_au");
	print '</td><td align="left">';
	print '&nbsp;';
	print '</td>';
	print "</tr>\n";
	
	print '<tr class="oddeven" valign="top">';
	
	/*
	 * Nom du loyer
	 */
	print '<td><input name="name" size="30" value="' . GETPOST('name') . '"</td>';
	
	// Due date
	print '<td align="center">';
	print $form->select_date(! empty($dateech) ? $dateech : '-1', 'ech', 0, 0, 0, 'fiche_loyer', 1);
	print '</td>';
	print '<td align="center">';
	print $form->select_date(! empty($dateperiod) ? $dateperiod : '-1', 'period', 0, 0, 0, 'fiche_loyer', 1);
	print '</td>';
	print '<td align="center">';
	print $form->select_date(! empty($dateperiodend) ? $dateperiodend : '-1', 'periodend', 0, 0, 0, 'fiche_loyer', 1);
	print '</td>';
	
	print '<td align="center"><input type="submit" class="button" value="' . $langs->trans("Add") . '"></td></tr>';
	
	print '</table>';
	
	
	/*
	 * List agreement
	 */
	$sql = "SELECT c.rowid as reference, loc.lastname as nom, l.address, l.label as local, loc.status as status, c.montant_tot as total,";
	$sql .= "c.loyer , c.charges, c.fk_renter as reflocataire, c.fk_property as reflocal, c.preavis as preavis, c.tva, l.fk_owner";
	$sql .= " FROM " . MAIN_DB_PREFIX . "immobilier_immorenter loc";
	$sql .= " , " . MAIN_DB_PREFIX . "immobilier_immocontrat as c";
	$sql .= " , " . MAIN_DB_PREFIX . "immobilier_immoproperty as l";
	$sql .= " WHERE preavis = 0 AND loc.rowid = c.fk_renter and l.rowid = c.fk_property  ";
	$resql = $db->query($sql);
	if ($resql) 
	{
		$num = $db->num_rows($resql);
		
		$i = 0;
		$total = 0;
		
		print '<br><table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans('Contract') . '</td>';
		print '<td>' . $langs->trans('Property') . '</td>';
		print '<td>' . $langs->trans('Nomlocal') . '</td>';
		print '<td>' . $langs->trans('Renter') . '</td>';
		print '<td>' . $langs->trans('NameRenter') . '</td>';
		print '<td align="right">' . $langs->trans('AmountTC') . '</td>';
		print '<td align="right">' . $langs->trans('Rent') . '</td>';
		print '<td align="right">' . $langs->trans('Charges') . '</td>';
		print '<td align="right">' . $langs->trans('VATIsUsed') . '</td>';
		print '<td align="right">' . $langs->trans('nameowner') . '</td>';
		print '<td align="right">' . $langs->trans('Select') . '</td>';
		print "</tr>\n";
		
		if ($num > 0) 
		{
			
			while ( $i < $num ) 
			{
				$objp = $db->fetch_object($resql);
				print '<tr class="oddeven">';				
				print '<td>' . $objp->reference . '</td>';
				print '<td>' . $objp->reflocal . '</td>';
				print '<td>' . $objp->local . '</td>';
				print '<td>' . $objp->reflocataire . '</td>';
				print '<td>' . $objp->nom . '</td>';
				
				print '<td align="right">' . price($objp->total) . '</td>';
				print '<td align="right">' . price($objp->loyer) . '</td>';
				print '<td align="right">' . price($objp->charges) . '</td>';
				print '<td align="right">' . yn($objp->tva) . '</td>';
				print '<td align="right">' . $objp->fk_owner . '</td>';
				
				// Colonne choix contrat
				print '<td align="center">';
				
				print '<input type="checkbox" name="mesCasesCochees[]" value="' . $objp->reference . '_' . $objp->reflocal . '_' . $objp->reflocataire . '_' . $objp->total . '_' . $objp->loyer . '_' . $objp->charges . '_' . $objp->fk_owner . '"' . ($objp->reflocal ? ' checked="checked"' : "") . '/>';
				print '</td>';
				print '</tr>';
				
				$i ++;
			}
		}
		
		print "</table>\n";
		dol_fiche_end();
		$db->free($resql);
	} 

	else 
	{
		dol_print_error($db);
	}
	print '</form>';
}
/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

// Part to edit record
else
{
	if (($id || $ref) && $action == 'edit')
	{
		print load_fiche_titre($langs->trans("ImmoReceipt"));
		
		$receipt = new ImmoReceipt($db);
		$result = $receipt->fetch($id);

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';

		$head = immoreceiptPrepareHead($receipt);
		dol_fiche_head($head, 'card', $langs->trans("ReceiptCard"), 0, 'rent@immobilier');

		print '<table class="border centpercent">'."\n";

		// Common attributes
		// Nom du loyer
		print '<tr><td class="titlefield">' . $langs->trans("NomLoyer") . '</td>';
		print '<td><input name="nom" size="20" value="' . $receipt->label . '"</td></tr>';
		
		// Contract
		print '<tr><td>' . $langs->trans("Contract") . '</td>';
		print '<td>' . $receipt->fk_contract . '</td></tr>';
		print '<tr><td>';
		print $langs->trans('VATIsUsed');
		print '</td><td>';
		print yn($receipt->addtva);
		print '</td>';
		print '</tr>';

		// Bien
		print '<tr><td>' . $langs->trans("Property") . ' </td>';
		print '<td>' . $receipt->nomlocal . '</td></tr>';

		// Nom locataire
		print '<tr><td>' . $langs->trans("Renter") . '</td>';
		print '<td>' . $receipt->nomlocataire . '</td></tr>';
		
		// Amount
		print '<tr><td>' . $langs->trans("AmountTC") . '</td>';
		print '<td>' . $receipt->total_amount . '</td></tr>';
		print '<tr><td>' . $langs->trans("Rent") . '</td>';
		print '<td><input name="rent" size="10" value="' . $receipt->rentamount . '"</td></tr>';
		print '<tr><td>' . $langs->trans("Charges") . '</td>';
		print '<td><input name="charges" size="10" value="' . $receipt->chargesamount . '"</td>';
		print '<tr><td>' . $langs->trans("VAT") . '</td>';
		print '<td>' . $receipt->vat . '</td>';
		$rowspan = 5;
		print '<td rowspan="' . $rowspan . '" valign="top">';

		/*
		 * Paiements
		 */
		$sql = "SELECT p.rowid, p.fk_receipt, date_payment as dp, p.amount, p.note_public as type, il.total_amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "immobilier_immopayment as p";
		$sql .= ", " . MAIN_DB_PREFIX . "immobilier_immoreceipt as il ";
		$sql .= " WHERE p.fk_receipt = ".$id;
		$sql .= " AND p.fk_receipt = il.rowid";
		$sql .= " ORDER BY dp DESC";

		$resql = $db->query($sql);
		if ($resql) 
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$total = 0;
			echo '<table class="nobordernopadding" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Date") . '</td><td>' . $langs->trans("Type") . '</td>';
			print '<td align="right">' . $langs->trans("Amount") . '</td><td></td><td>X</td></tr>';

			while ( $i < $num ) 
			{
				$objp = $db->fetch_object($resql);
				print '<tr class="oddeven"><td>';
				print "<a href=".dol_buildpath('/immobilier/payment/immopayment_card.php', 1).'?action=update&id='.$objp->rowid."&amp;receipt=".$id.">".img_object($langs->trans("Payment"), "payment")."</a>";
				print dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				print "<td>".$objp->type."</td>\n";
				print '<td align="right">' . price($objp->amount) . "</td><td>&nbsp;" . $langs->trans("Currency" . $conf->currency) . "</td>\n";
				
				print '<td>';
				if ($user->admin) 
				{
					print "<a href=".dol_buildpath('/immobilier/payment/immopayment_card.php', 1).'?id='.$objp->rowid."&amp;action=delete&amp;receipt=".$id.">";
					print img_delete();
					print '</a>';
				}
				print '</td>';
				print "</tr>";
				$totalpaye += $objp->amount;
				$i ++;
			}

			if ($receipt->paye == 0) 
			{
				print "<tr><td colspan=\"2\" align=\"right\">" . $langs->trans("AlreadyPaid") . " :</td><td align=\"right\"><b>" . price($totalpaye) . "</b></td><td>&nbsp;" . $langs->trans("Currency" . $conf->currency) . "</td></tr>\n";
				print "<tr><td colspan=\"2\" align=\"right\">" . $langs->trans("AmountExpected") . " :</td><td align=\"right\" bgcolor=\"#d0d0d0\">" . price($receipt->amount_total) . "</td><td bgcolor=\"#d0d0d0\">&nbsp;" . $langs->trans("Currency" . $conf->currency) . "</td></tr>\n";
				
				$resteapayer = $receipt->amount_total - $totalpaye;
				
				print "<tr><td colspan=\"2\" align=\"right\">" . $langs->trans("RemainderToPay") . " :</td>";
				print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>" . price($resteapayer) . "</b></td><td bgcolor=\"#f0f0f0\">&nbsp;" . $langs->trans("Currency" . $conf->currency) . "</td></tr>\n";
			}
			print "</table>";
			$db->free($resql);
		} 
		else 
		{
			dol_print_error($db);
		}
		print "</td>";
		
		print "</tr>";

		// Due date
		print '<tr><td>' . $langs->trans("Echeance") . '</td>';
		print '<td align="left">';
		print $form->select_date($receipt->echeance, 'ech', 0, 0, 0, 'fiche_loyer', 1);
		print '</td>';
		print '<tr><td>' . $langs->trans("Periode_du") . '</td>';
		print '<td align="left">';
		print $form->select_date($receipt->date_start, 'period', 0, 0, 0, 'fiche_loyer', 1);
		print '</td>';
		print '<tr><td>' . $langs->trans("Periode_au") . '</td>';
		print '<td align="left">';
		print $form->select_date($receipt->date_end, 'periodend', 0, 0, 0, 'fiche_loyer', 1);
		print '</td>';
		print '<tr><td>' . $langs->trans("Comment") . '</td>';
		print '<td><input name="commentaire" size="70" value="'.$receipt->note_public. '"</td></tr>';
		
		// Status loyer
		print '<tr><td>statut</td>';
		print '<td align="left" nowrap="nowrap">';
		print $receipt->LibStatut($receipt->paye, 5);
		print "</td></tr>";
		
		print '<tr><td colspan="2">&nbsp;</td></tr>';
		
		print '</table>';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

		print '</table>';

		dol_fiche_end();

		print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print '</form>';
	}
	// Part to show record
	if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
	{
		$res = $object->fetch_optionals();
		$receipt = new ImmoReceipt($db);
		$result = $receipt->fetch($id);

		$head = immoreceiptPrepareHead($object);
		dol_fiche_head($head, 'card', $langs->trans("ImmoReceipt"), -1, 'immoreceipt@immobilier');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete')
		{
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteImmoReceipt'), $langs->trans('ConfirmDeleteImmoReceipt'), 'confirm_delete', '', 0, 1);
		}

		if (! $formconfirm) {
			$parameters = array('lineid' => $lineid);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
			elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;		
			
		// Display receipt card						
		print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="delete">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		
		$linkback = '<a href="' .dol_buildpath('/immobilier/receipt/immoreceipt_list.php',1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref='<div class="refidno">';
		/*
		// Ref bis
		$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->immobilier->creer, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->immobilier->creer, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled))
		{
			$langs->load("projects");
			$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
			if ($user->rights->immobilier->creer)
			{
				if ($action != 'classify')
				{
					$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
					if ($action == 'classify') {
						//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
						$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
						$morehtmlref.='<input type="hidden" name="action" value="classin">';
						$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
						$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
						$morehtmlref.='</form>';
					} else {
						$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
					}
				}
			} else {
				if (! empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
					$morehtmlref.=$proj->ref;
					$morehtmlref.='</a>';
				} else {
					$morehtmlref.='';
				}
			}
		}
		*/
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">'."\n";

		// Ref
		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
		print $form->showrefnav($receipt, 'id', $linkback, 1, 'id', 'id', '');
		print '</td>';
		print '</tr>';

		// Receipt name
		print '<tr><td>' . $langs->trans("NomLoyer") . '</td>';
		print '<td>' . $receipt->label . '</td></tr>';
		
		// Contract
		print '<tr><td>' . $langs->trans("Contract") . '</td>';
		print '<td>' . $receipt->fk_contract . '</td></tr>';
		
		// VAT
		print '<tr><td>';
		print $langs->trans('VATIsUsed');
		print '</td><td>';
		print yn($receipt->addtva);
		print '</td>';
		print '</tr>';

		// Property
		print '<tr><td>' . $langs->trans("Property") . ' </td>';
		print '<td>' . $receipt->nomlocal . '</td></tr>';

		// Renter
		print '<tr><td>' . $langs->trans("Renter") . '</td>';
		print '<td>' . $receipt->nomlocataire . '</td></tr>';
		
		// Amount
		print '<tr><td>' . $langs->trans("AmountTC") . '</td>';
		print '<td>' . price($receipt->total_amount) . '</td></tr>';
		print '<tr><td>' . $langs->trans("AmountHC") . '</td>';
		print '<td>' . price($receipt->rentamount) . '</td></tr>';
		print '<tr><td>' . $langs->trans("Charges") . '</td>';
		print '<td>' . price($receipt->chargesamount) . '</td>';
		print '<tr><td>' . $langs->trans("VAT") . '</td>';
		print '<td>' . price($receipt->vat) . '</td>';
		print "</tr>";

		// Due date
		print '<tr><td>' . $langs->trans("Echeance") . '</td>';
		print '<td>';
		print dol_print_date($receipt->echeance,"day");
		print '</td>';
		print '<tr><td>' . $langs->trans("Periode_du") . '</td>';
		print '<td>';
		print dol_print_date($receipt->date_start,"day");
		print '</td>';
		print '<tr><td>' . $langs->trans("Periode_au") . '</td>';
		print '<td>';
		print dol_print_date($receipt->date_end,"day");
		print '</td>';
		print '<tr><td>' . $langs->trans("Comment") . '</td>';
		print '<td>' . $receipt->note_public . '</td></tr>';
		
		// Status loyer
		print '<tr><td>'.$langs->trans("Status").'</td>';
		print '<td>';
		print $receipt->LibStatut($receipt->paye, 5);
		print '</td></tr>';
		
		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';

		// List of payments
		$sql = "SELECT p.rowid, p.fk_receipt, date_payment as dp, p.amount, p.fk_typepayment, pp.libelle as typepayment_label, il.total_amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "immobilier_immopayment as p";
		$sql .= ", " . MAIN_DB_PREFIX . "immobilier_immoreceipt as il ";
		$sql .= ", " . MAIN_DB_PREFIX . "c_paiement as pp";
		$sql .= " WHERE p.fk_receipt = " . $id;
		$sql .= " AND p.fk_receipt = il.rowid";
		$sql .= " AND p.fk_typepayment = pp.id";
		$sql .= " ORDER BY dp DESC";

		$resql = $db->query($sql);
		if ($resql) 
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$total = 0;
			print '<table class="nobordernopadding" width="100%">';
			
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td align="right">'.$langs->trans("Amount").'</td>';
			if ($user->admin) print '<td>&nbsp;</td>';
			print '</tr>';

			while ( $i < $num ) 
			{
				$objp = $db->fetch_object($resql);
				print '<tr class="oddeven"><td>';
				print '<a href="'.dol_buildpath('/immobilier/payment/immopayment_card.php', 1).'?action=update&amp;id='.$objp->rowid.'&amp;receipt='.$id.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a>';
				print '<td>' . dol_print_date($db->jdate($objp->dp), 'day') . '</td>';
				print '<td>' . $objp->typepayment_label . '</td>';
				print '<td align="right">' . price($objp->amount) . "&nbsp;" . $langs->trans("Currency" . $conf->currency) . "</td>\n";
				
				print '<td align="right">';
				if ($user->admin) 
				{
					print '<a href="'.dol_buildpath('/immobilier/payment/immopayment_card.php', 1).'?id='.$objp->rowid.'&amp;action=delete&amp;receipt='.$id.'">';
					print img_delete();
					print '</a>';
				}
				print '</td>';
				print "</tr>";
				$totalpaye += $objp->amount;
				$i ++;
			}

			if ($receipt->paye == 0) 
			{
				print "<tr><td colspan=\"3\" align=\"right\">" . $langs->trans("AlreadyPaid") . " :</td><td align=\"right\"><b>" . price($totalpaye) . "</b></td></tr>\n";
				print "<tr><td colspan=\"3\" align=\"right\">" . $langs->trans("AmountExpected") . " :</td><td align=\"right\">" . price($receipt->amount_total) . "</td></tr>\n";
				
				$remaintopay = $receipt->amount_total - $totalpaye;
				
				print "<tr><td colspan=\"3\" align=\"right\">" . $langs->trans("RemainderToPay") . " :</td>";
				print '<td align="right"'.($remaintopay?' class="amountremaintopay"':'').'>'.price($remaintopay)."</td></tr>\n";
			}
			print "</table>";
			$db->free($resql);
		} 
		else 
		{
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		dol_fiche_end();

		if (is_file($conf->immobilier->dir_output . '/quittance_' . $id . '.pdf')) {
			print '&nbsp';
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan=3>' . $langs->trans("LinkedDocuments") . '</td></tr>';
			// afficher
			$legende = $langs->trans("Ouvrir");
			print '<tr><td width="200" align="center">' . $langs->trans("Quittance") . '</td><td> ';
			print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=immobilier&file=quittance_' . $id . '.pdf" alt="' . $legende . '" title="' . $legende . '">';
			print '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
			print '</td></tr></table>';
		}
		
		print '</div>';
		
		/*
		 * Actions bar
		 */

		print '<div class="tabsAction">';

		if ($action != 'create' && $action != 'edit')
		{		
			// Edit
			print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/immobilier/receipt/immoreceipt_card.php', 1).'?id=' . $receipt->id . '&amp;action=edit">' . $langs->trans("Modify") . '</a></div>';

			// Create payment
			if ($receipt->paye == 0 && $user->rights->immobilier->rent->write)
			{
				if ($remaintopay == 0)
				{
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseRemainderToPayIsZero") . '">' . $langs->trans('DoPayment') . '</span></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/immobilier/payment/immopayment_card.php', 1).'?id=' . $id . '&amp;action=create">' . $langs->trans('DoPayment') . '</a></div>';
				}
			}

			// Classify 'paid'
			if ($receipt->paye == 0 && round($remaintopay) <= 0) {
				print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=paid&id=' . $id . '">' . $langs->trans('ClassifyPaid') . '</a></div>';
			}
			
			// Delete
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . dol_buildpath('/immobilier/receipt/immoreceipt_card.php', 1).'?id=' . $id . '&amp;action=delete">' . $langs->trans("Delete") . '</a></div>';
			
			// Generate receipt
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=quittance&id=' . $id . '">' . $langs->trans('GenererQuittance') . '</a></div>';
			
			// Send receipt
			print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/immobilier/receipt/immoreceipt_mails.php', 1).'?id=' . $id . '">' . $langs->trans('SendMail') . '</a></div>';
		}

		print '</div>';

		print '<table width="100%"><tr><td width="50%" valign="top">';
			
		/*
		 * Documents generes
		 */
		$filename	=	dol_sanitizeFileName($receipt->id);
		$filedir	=	$conf->immobilier->dir_output . "/" . dol_sanitizeFileName($receipt->id);
		$urlsource	=	$_SERVER['PHP_SELF'].'?id='.$receipt->id;
		$genallowed	=	$user->rights->immobilier->rent->write;
		$delallowed	=	$user->rights->immobilier->rent->delete;

		print '<br>';
		//$formfile->show_documents('immobilier',$filename,$filedir,$urlsource,$genallowed,$delallowed,$receipt->modelpdf);

		print '</td><td>&nbsp;</td>';

		print '</tr></table>';
	}
}

// End of page
llxFooter();
$db->close();
