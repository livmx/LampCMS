<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms\Controllers;

use Lampcms\SubmittedProfileWWW;
use Lampcms\WebPage;
use Lampcms\ProfileParser;

class Editprofile extends WebPage
{
	/**
	 * Pre-check to deny non-logged in user
	 * access to this page
	 *
	 * @var bool
	 */
	protected $membersOnly = true;


	/**
	 *
	 * Viewer must have edit_profile
	 * permission to access this page
	 * @var string
	 */
	protected $permission = 'edit_profile';


	/**
	 * $layoutID 1 means no side-column on page
	 *
	 * @var int
	 */
	protected $layoutID = 1;


	/**
	 * Form object
	 *
	 * @var object of type \Lampcms\Forms\Form
	 */
	protected $oForm;


	/**
	 * User object of user whose profile
	 * is being edited
	 *
	 * @var object of type User
	 */
	protected $oUser;


	protected function main(){
		$this->getUser();
		$this->oForm = new \Lampcms\Forms\Profile($this->oRegistry);
		$this->oForm->formTitle = $this->aPageVars['title'] = $this->_('Edit Profile');

		if($this->oForm->isSubmitted() && $this->oForm->validate()){
			$this->oRegistry->Dispatcher->post($this->oForm, 'onBeforeProfileUpdate');
			//try{
				$this->saveProfile();
				$this->oRegistry->Dispatcher->post($this->oForm, 'onProfileUpdate');
				$this->aPageVars['body'] = \tplProfileSuccess::parse(array('Profile has been updated', $this->oUser->getProfileUrl(), 'View the new profile'), false);
			/*} catch (\Lampcms\Exception $e){
				$this->oForm->setFormError($e->getMessage());
				$this->setForm();
				$this->aPageVars['body'] = $this->oForm->getForm();
			}*/
		} else {
			$this->setForm();
			$this->aPageVars['body'] = $this->oForm->getForm();
		}
	}


	/**
	 * Create $this->oUser User object for user whose
	 * profile is being edited
	 *
	 * @todo unfinished. IT will be possible to
	 * edit user other than Viewer when Viewer has
	 * permission to edit_other_profile
	 * For now this is a Viewe object
	 *
	 * @return object $this
	 */
	protected function getUser(){
		$uid = $this->oRequest->get('uid', 'i', null);
		if($uid && ($uid !== $this->oRegistry->Viewer->getUid())){
			/**
			 * This is edit profile for another user
			 * check Viewer permission here
			 */
			$this->checkAccessPermission('edit_any_profile');
			$this->oUser = \Lampcms\User::factory($this->oRegistry)->by_id($uid);
		} else {
			$this->oUser = $this->oRegistry->Viewer;
		}

		return $this;
	}


	/**
	 * Populate form elements with
	 * values from current user profile
	 *
	 * @return object $this
	 */
	protected function setForm(){

		$this->oForm->username = $this->oUser['username'];
		$this->oForm->usernameLabel = 'Username';
		$this->oForm->fn = $this->oUser['fn'];
		$this->oForm->mn = $this->oUser['mn'];
		$this->oForm->ln = $this->oUser['ln'];
		$this->oForm->gender = $this->getGenderOptions();
		$this->oForm->dob = $this->oUser['dob'];
		$this->oForm->cc = $this->getCountryOptions();
		$this->oForm->state = $this->oUser['state'];
		$this->oForm->city = $this->oUser['city'];
		$this->oForm->url = $this->oUser['url'];
		$this->oForm->zip = $this->oUser['zip'];
		$this->oForm->description = $this->oUser['description'];
		$this->oForm->avatarSrc = $this->oUser->getAvatarSrc();
		$this->oForm->width = $this->oRegistry->Ini->AVATAR_SQUARE_SIZE;
		$this->oForm->uid = $this->oUser->getUid();
		$this->oForm->maxAvatarSize = $this->oRegistry->Ini->MAX_AVATAR_UPLOAD_SIZE;
		/**
		 * @todo translate string
		 */
		$this->oForm->avatarTos = sprintf('Upload Image. Maximum size of %sMb<br><span class="smaller">By uploading a file you certify that you have the right to distribute this picture and that it does not violate the Terms of Service.</span>', floor($this->oRegistry->Ini->MAX_AVATAR_UPLOAD_SIZE / 1000000) );

		/**
		 * Add '  hide' class to avatar upload
		 * if php does not have gd of support for jpeg
		 * inside gd
		 */
		if(!\extension_loaded('gd') || !\function_exists('imagecreatefromjpeg')){
			$this->oForm->hideAvatar = ' hide';
		}

		return $this;
	}


	/**
	 * Save changed to user profile
	 * using ProfileParser class
	 *
	 * @return object $this
	 */
	protected function saveProfile(){

		ProfileParser::factory($this->oRegistry)->save($this->oUser, new SubmittedProfileWWW($this->oForm));

		/**
		 * Should unset 'welcome' from session
		 * because it contains user display name in the
		 * link and display name may have changed as a
		 * result of editing first, middle and last names
		 */
		unset($_SESSION['welcome']);

		return $this;
	}


	/**
	 * Generates string with <option></option> html elements
	 * that will be used as html of the <select> dropdown
	 * menu for Country selection
	 * 
	 * @todo use 'cc' key from USER as value
	 * and country name only as label! Don NOT
	 * set 'country' key at all!
	 * For this we need an array that translates
	 * 2-letter country code 'cc' to the full country name
	 *
	 * @return string html string
	 */
	protected function getCountryOptions(){
		$s = '';
		$current = \strtoupper($this->oRegistry->Viewer['cc']);
		$tpl = '<option value="%1$s"%2$s>%3$s</option>';
		$aCountries = \array_combine(\Lampcms\Geo\Location::getCodes(), \Lampcms\Geo\Location::getNames());
		
		foreach($aCountries as $key => $val){
			$selected = ($current == $key) ? ' selected' : '';
			$name = (empty($val)) ? 'Select country' : $val;

			$s .= \vsprintf($tpl, array($key, $selected, $name));
		}

		return $s;
	}


	/**
	 * Generates string with 3 <option></option> html elements
	 * for the value of "Gender" drop-down menu
	 * it also sets the "selected" value of an option
	 * that matches the current value in Viewer object
	 *
	 * @todo translate string Male and Female but not the values "M" and "F"
	 *
	 * @return string html string
	 */
	protected function getGenderOptions(){
		$current = $this->oRegistry->Viewer['gender'];
		$s = '';
		$a = array('' => 'Select Gender', 'M' => 'Male', 'F' => 'Female');
		$tpl = '<option value="%1$s"%2$s>%3$s</option>';
		foreach($a as $key => $val){
			$selected = ($key === $current) ? ' selected' : '';
			$s .= vsprintf($tpl, array($key, $selected, $val));
		}

		return $s;
	}

}
