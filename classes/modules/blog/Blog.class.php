<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

/**
 * Модуль для работы с блогами
 *
 * @package modules.blog
 * @since 1.0
 */
class PluginSecretblog_ModuleBlog extends PluginSecretblog_Inherit_ModuleBlog {

	/**
	 * Получает список блогов по рейтингу
	 *
	 * @param int $iCurrPage	Номер текущей страницы
	 * @param int $iPerPage		Количество элементов на одну страницу
	 * @return array('collection'=>array,'count'=>int)
	 */
	public function GetBlogsRating($iCurrPage,$iPerPage) {
		return $this->GetBlogsByFilter(array('exclude_type'=>array('personal','secret')),array('blog_rating'=>'desc'),$iCurrPage,$iPerPage);
	}

	/**
	 * Список своих блогов по рейтингу
	 *
	 * @param int $sUserId	ID пользователя
	 * @param int $iLimit	Ограничение на количество в ответе
	 * @return array
	 */
	public function GetBlogsRatingSelf($sUserId,$iLimit) {
		$aResult=$this->GetBlogsByFilter(array('exclude_type'=>array('personal','secret'),'user_owner_id'=>$sUserId),array('blog_rating'=>'desc'),1,$iLimit);
		return $aResult['collection'];
	}
  
	/**
	 * Получаем массив идентификаторов блогов, которые являются закрытыми для пользователя
	 *
	 * @param  ModuleUser_EntityUser|null $oUser	Пользователь
	 * @return array
	 */
	public function GetInaccessibleBlogsByUser($oUser=null) {
		if ($oUser&&$oUser->isAdministrator()) {
			return array();
		}
		$sUserId=$oUser ? $oUser->getId() : 'guest';
		if (false === ($aCloseBlogs = $this->Cache_Get("blog_inaccessible_user_{$sUserId}"))) {
			$aCloseBlogs = $this->oMapperBlog->GetCloseBlogs();
			if($oUser) {
				/**
				 * Получаем массив идентификаторов блогов,
				 * которые являются откытыми для данного пользователя
				 */
				$aOpenBlogs=$this->GetBlogUsersByUserId($oUser->getId(),null,true);
				/**
				 * Получаем закрытые блоги, где пользователь является автором
				 */
				$aOwnerBlogs=$this->GetBlogsByFilter(array('type'=>array('close', 'secret'),'user_owner_id'=>$oUser->getId()),array(),1,100,array());
				$aOwnerBlogs=array_keys($aOwnerBlogs['collection']);
				$aCloseBlogs=array_diff($aCloseBlogs,$aOpenBlogs,$aOwnerBlogs);
			}
			/**
			 * Сохраняем в кеш
			 */
			if ($oUser) {
				$this->Cache_Set($aCloseBlogs, "blog_inaccessible_user_{$sUserId}", array('blog_new','blog_update',"blog_relation_change_{$oUser->getId()}"), 60*60*24);
			} else {
				$this->Cache_Set($aCloseBlogs, "blog_inaccessible_user_{$sUserId}", array('blog_new','blog_update'), 60*60*24*3);
			}
		}
		return $aCloseBlogs;
	}
	
	
}
?>