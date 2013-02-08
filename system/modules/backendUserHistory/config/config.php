<?php 

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013 
 * @package    backendUserHistory
 * @license    GNU/LGPL 
 * @filesource
 */

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['outputBackendTemplate'][] = array('BackendUserHistory', 'trackUrl'); 
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('BackendUserHistory', 'showEditWarning'); 
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = array('BackendUserHistory', 'showWelcomeMessage'); 
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('BackendUserHistory', 'changeLoadCallbacks'); 
$GLOBALS['TL_HOOKS']['postLogout'][] = array('BackendUserHistory', 'logoutUser'); 

/**
 * Blacklist for child tables
 */
$GLOBALS['BE_USER_HISTORY']['ignore_ctables'] = array_merge( (array) $GLOBALS['BE_USER_HISTORY']['ignore_ctables'], array(    
    'tl_page',
));

/**
 * Blacklist tables for syncCto
 */
$GLOBALS['SYC_CONFIG']['table_hidden'] = array_merge( (array) $GLOBALS['SYC_CONFIG']['table_hidden'], array(    
    'tl_user_history',
));


if (TL_MODE == 'BE')
{
    $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/backendUserHistory/html/tooltip.js';
    $GLOBALS['TL_CSS'][] = 'system/modules/backendUserHistory/html/tooltip.css';
}

?>