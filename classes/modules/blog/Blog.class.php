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
	
}
?>