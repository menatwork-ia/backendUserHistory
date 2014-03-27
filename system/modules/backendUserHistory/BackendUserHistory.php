<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2014
 * @package    backendUserHistory
 * @license    GNU/LGPL 
 * @filesource
 */

class BackendUserHistory extends Backend
{
    private $arrDCAs = array
    (
        'tl_article',
        'tl_calendar',
        'tl_calendar_events',
        'tl_content',
        'tl_form',
        'tl_form_field',
        'tl_inserttags',
        'tl_layout',
        'tl_member',
        'tl_member_group',
        'tl_module',
        'tl_news',
        'tl_news_archive',
        'tl_page',
        'tl_style',
        'tl_style_sheet',
        'tl_theme',
        'tl_user',
        'tl_user_group'
    );
    
    /**
	 * Current object instance (Singleton)
	 * @var Session
	 */
	protected static $objInstance;
    
    /**
	 * Load the user object
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
    
	/**
	 * Return the current object instance (Singleton)
	 * @return Session
	 */
	public static function getInstance()
	{
		if (!is_object(self::$objInstance))
		{
			self::$objInstance = new self();
		}

		return self::$objInstance;
	}
    
    /**
     * Track the current URL and save it to the tl_user_history table
     * 
     * @param type $strContent
     * @param type $strTemplate
     * @return type
     */
    public function trackUrl($strContent, $strTemplate)
    {
        if(!$this->Database->tableExists('tl_user_history'))
        {
            return $strContent;
        }
        
        $this->import('BackendUser', 'User');
        if ($strTemplate == 'be_main' && $this->User->id)
        {
            $arrData = array(
                'tstamp'    => time(),
                'sessionId' => session_id(),
                'userid'    => $this->User->id,
                'url'       => serialize($_GET)
            );
            $this->Database->prepare('INSERT INTO tl_user_history %s ON DUPLICATE KEY UPDATE tstamp = ?, sessionId = ?, url = ?')
                    ->set($arrData)
                    ->executeUncached($arrData['tstamp'], $arrData['sessionId'], $arrData['url']);
        }
        return $strContent;
    }
    
    
    /**
     * 
     * @param type $strContent
     * @param type $strTemplate
     * @return type
     */
    public function showEditWarning($strName)
    {
        if ($this->Input->get('act') && $this->Input->get('id') && $this->Database->tableExists('tl_user_history'))
        {
            if (!is_array($_SESSION["TL_INFO"]))
            {
                $_SESSION["TL_INFO"] = array();
            }
            
            //get the users, that might edit this element
            $strEditType  = ($this->Input->get('table') && $this->Input->get('table') != 'tl_'.$this->Input->get('do')) ? 'table' : 'do';
            $strEditTable = $this->Input->get($strEditType);
            $arrUrlParams = array('%' . $strEditTable . '%', '%edit%', '%' .  $this->Input->get('id') . '%');
            $objUsers = $this->searchUser($arrUrlParams);
            
            while ($objUsers->next())
            {
                //check if the user is really editing the given element
                $arrUrl = deserialize($objUsers->url);
                if ($arrUrl['act'] == 'edit' && $arrUrl['id'] == $this->Input->get('id') && $arrUrl[$strEditType] == $strEditTable)
                {
                    //add a notice
                    $_SESSION["TL_INFO"][$objUsers->username . ' _ ' . $objUsers->tstamp] = sprintf($GLOBALS['TL_LANG']['MSC']['editWarning'], $objUsers->username, date($GLOBALS['TL_CONFIG']['timeFormat'], $objUsers->tstamp),$arrUrl['id']);
                }
            }
        }
        
        return $strContent;
    }
    
    public function showWelcomeMessage()
    {
        if (!is_array($_SESSION["TL_INFO"]))
        {
            $_SESSION["TL_INFO"] = array();
        }

        //get the users, that might edit this element
        $arrUrlParams = array('%edit%', '%' . $this->Input->get('id') . '%');
        $objUsers = $this->searchUser($arrUrlParams);
        
        if($objUsers === false ) 
        {
            return '';
        }
        
        if ($objUsers->numRows >0) $strReturn = '<h2>'.$GLOBALS['TL_LANG']['MSC']['editHeadline'].'</h2>';
        
        while ($objUsers->next())
        {
            //check if the user is really editing the given element
            $arrUrl = deserialize($objUsers->url);
            //add a notice
            $strReturn .= '<p class="tl_info">'.sprintf($GLOBALS['TL_LANG']['MSC']['editWarning'], $objUsers->username, date($GLOBALS['TL_CONFIG']['timeFormat'], $objUsers->tstamp), $arrUrl['id'] ).'</p>';
 
        }
        return $strReturn;
    }
    
    
    /**
     * Change the edit button callback
     * @param type $strName
     */
    public function changeLoadCallbacks($strName)
    {
        if (in_array($strName, $this->arrDCAs))
        {
            //save the old button callback
            if (isset($GLOBALS['TL_DCA'][$strName]['list']['operations']['edit']['button_callback']))
            {
                $GLOBALS['TL_DCA'][$strName]['list']['operations']['edit']['backendUserHistory_button_callback'] = $GLOBALS['TL_DCA'][$strName]['list']['operations']['edit']['button_callback'];
            }

            //set new button callback
            $GLOBALS['TL_DCA'][$strName]['list']['operations']['edit']['button_callback'] = array('BackendUserHistory', 'editElement');
        }
    }

    /**
     * Change the edit button if someone is editing the elements or an child element
     * @param type $row
     * @param type $href
     * @param type $label
     * @param type $title
     * @param type $icon
     * @param type $attributes
     * @param type $strTable
     * @return type
     */
    public function editElement($row, $href, $label, $title, $icon, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext)
    {     
        //get the original button
        $strFunction = 'edit';
        $strButton =  $this->callParentFunction($row, $href, $label, $title, $icon, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $strFunction);
        
        if (!$this->Database->tableExists('tl_user_history'))
        {
            return $strButton;
        }

        //do nothting if this element is disabled
        if ($strButton == '' || stripos($strButton, '_.gif') !==false) return $strButton;

        $arrNotices = array();
 
        //check the child elements if not blacklisted
        if (!in_array('tl_' . $this->Input->get('do'), $GLOBALS['BE_USER_HISTORY']['ignore_ctables']))
        {
            //get the user which are editing child records
            foreach ((array) $GLOBALS['TL_DCA'][$strTable]['config']['ctable'] as $cTable)
            {
                $arrChildIds = $this->Database->prepare('SELECT id FROM ' . $cTable . ' WHERE pid = ?')->execute($row['id'])->fetchEach('id');

                //get the users, that might edit this element
                $arrUrlParams = array('%' . $cTable . '%', '%edit%', '%id%');
                $objUsers = $this->searchUser($arrUrlParams);

                while ($objUsers->next())
                {
                    //check if the user is reall editing the given element
                    $arrUrl = deserialize($objUsers->url);
                    if ($arrUrl['act'] == 'edit' && in_array($arrUrl['id'], $arrChildIds) && $arrUrl['table'] == $cTable)
                    {
                        //add a notice
                        $arrNotices[] = sprintf($GLOBALS['TL_LANG']['MSC']['editChildWarning'], $objUsers->username, date($GLOBALS['TL_CONFIG']['timeFormat'], $objUsers->tstamp), $arrUrl['id']);
                    }
                }
            }
        }
        
        //get the users, that might edit this element
        $strEditType = ($this->Input->get('table') && $this->Input->get('table') == $strTable) ? 'table' : 'do';
        $strEditTable = $this->Input->get($strEditType);
        $arrUrlParams = array('%'.$strEditTable.'%', '%edit%', '%'.$row['id'].'%');        
        $objUsers = $this->searchUser($arrUrlParams);

        while ($objUsers->next())
        {
            //check if the user is really editing the given element
            $arrUrl = deserialize($objUsers->url);
            if ($arrUrl['act'] == 'edit' && $arrUrl['id'] == $row['id'] && $arrUrl[$strEditType] == $strEditTable && ($arrUrl['table'] == $this->Input->get('table')) )
            {                
                //add a notice
                $arrNotices[] = sprintf($GLOBALS['TL_LANG']['MSC']['editWarning'], $objUsers->username, date($GLOBALS['TL_CONFIG']['timeFormat'], $objUsers->tstamp), $arrUrl['id']);
            }
        }

        //if notices are present, edit the icon and title
        if (count($arrNotices) > 0)
        {
            //$arrNotices[] = 'Die aktuelle Uhrzeit ist: '.date($GLOBALS['TL_CONFIG']['timeFormat']);
            $title      = implode("<br />", $arrNotices);
            $icon = 'system/modules/backendUserHistory/assets/edit.gif';
            $attributes = (stripos($attributes, 'class="') !==false) ? str_replace('class="', 'class="user-history ', $attributes) : $attributes.' class="user-history"';
            return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
        }

        //nothing to change, just call the parentfunction
        return $strButton;
    }    
    
    /**
     * Call the original button_callback function or generate the default button
     * @param type $row
     * @param type $href
     * @param type $label
     * @param type $title
     * @param type $icon
     * @param type $attributes
     * @param type $strTable
     * @param type $strFunction
     * @return string
     */
    protected function callParentFunction($row, $href, $label, $title, $icon, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext, $strFunction)
    {
        $arrParent = $GLOBALS['TL_DCA'][$strTable]['list']['operations'][$strFunction]['backendUserHistory_button_callback'];
        if (!is_array($arrParent))
        {   
            return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';    
        }

        $this->import($arrParent[0]);
        return $this->$arrParent[0]->$arrParent[1]($row, $href, $label, $title, $icon, $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, $strPrevious, $strNext);
    }

    /**
     * Search a user from tl_user_history with the give URL parameter
     * @param type $arrUrlParams
     * @return boolean
     */
    public function searchUser($arrUrlParams)
    {
        if(!$this->Database->tableExists('tl_user_history'))
        {
            return false;
        }
        
        if (!is_array($arrUrlParams) || empty($arrUrlParams)) return false;

        $objUser = $this->Database->prepare('SELECT uh.*, u.username FROM tl_user_history as uh JOIN tl_user as u ON (uh.userId = u.id) WHERE '.implode(' AND ', array_fill(0, count($arrUrlParams), 'url LIKE ?')).' AND userId != ? AND uh.tstamp > ?');
        return $objUser->execute(array_merge($arrUrlParams, array($this->User->id, (time() - $GLOBALS['TL_CONFIG']['sessionTimeout']))));
    }
    
    /**
     * Delete the tracked url from the DB
     * @param FrontendUser $objUser
     */
    public function logoutUser($objUser) 
    { 
        if ($objUser instanceof BackendUser) 
        {
            $this->Database->prepare('DELETE FROM tl_user_history WHERE userId = ?')->execute($objUser->id);
        }
    } 
}
