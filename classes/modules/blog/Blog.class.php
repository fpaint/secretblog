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
	
	/**
	 * Получает отношения юзера к блогам(состоит в блоге или нет)
	 *
	 * @param int $sUserId	ID пользователя
	 * @param int|null $iRole	Роль пользователя в блоге
	 * @param bool $bReturnIdOnly	Возвращать только ID блогов или полные объекты
	 * @return array
	 */
	public function GetBlogUsersByUserId($sUserId,$iRole=null,$bReturnIdOnly=false) {
		$aFilter=array(
			'user_id'=> $sUserId
		);
		if($iRole!==null) {
			$aFilter['user_role']=$iRole;
		}
		$s=serialize($aFilter);
		if (false === ($data = $this->Cache_Get("blog_relation_user_by_filter_$s"))) {
			$data = $this->oMapperBlog->GetBlogUsers($aFilter);
			$this->Cache_Set($data, "blog_relation_user_by_filter_$s", array("blog_update", "blog_relation_change_{$sUserId}"), 60*60*24*3);
		}
		/**
		 * Достаем дополнительные данные, для этого формируем список блогов и делаем мульти-запрос
		 */
		$aBlogId=array();
		$blogs = array();
		if ($data) {
			foreach ($data as $oBlogUser) {
				$aBlogId[]=$oBlogUser->getBlogId();
			}
			/**
			 * Если указано возвращать полные объекты
			 */
			if(!$bReturnIdOnly) {
				$aUsers=$this->User_GetUsersAdditionalData($sUserId);
				$aBlogs=$this->Blog_GetBlogsAdditionalData($aBlogId);
				$forCurrentUser = ($this->oUserCurrent and $sUserId == $this->oUserCurrent->getId());
				foreach ($data as $oBlogUser) {
				  $blog_id = $oBlogUser->getBlogId();
 				  if(isset($aBlogs[$blog_id]) and $aBlogs[$blog_id]->getType() == 'secret' and $forCurrentUser == false) { // фильтр, скрывающий тайные блоги от посторонних
 				    continue;
 				  }
					if (isset($aUsers[$oBlogUser->getUserId()])) {
						$oBlogUser->setUser($aUsers[$oBlogUser->getUserId()]);
					} else {
						$oBlogUser->setUser(null);
					}
					if (isset($aBlogs[$blog_id])) {  					
						$oBlogUser->setBlog($aBlogs[$blog_id]);
					} else {
						$oBlogUser->setBlog(null);
					}
					$blogs[] = $oBlogUser;
				}
				
			}
		}
		return ($bReturnIdOnly) ? $aBlogId : $blogs;
	}

}
?>