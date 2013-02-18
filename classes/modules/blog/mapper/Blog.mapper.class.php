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
 * Маппер для работы с БД по части блогов
 *
 * @package modules.blog
 * @since 1.0
 */
class PluginSecretblog_ModuleBlog_MapperBlog extends PluginSecretblog_Inherit_ModuleBlog_MapperBlog {

	/**
	 * Получить список блогов по хозяину
	 *
	 * @param int $sUserId ID пользователя
	 * @return array
	 */
	public function GetBlogsByOwnerId($sUserId) {
		$sql = "SELECT 
			b.blog_id			 
			FROM 
				".Config::Get('db.table.blog')." as b				
			WHERE 
				b.user_owner_id = ? 
				AND
				b.blog_type NOT IN ('personal', 'secret')
				";
		$aBlogs=array();
		if ($aRows=$this->oDb->select($sql,$sUserId)) {
			foreach ($aRows as $aBlog) {
				$aBlogs[]=$aBlog['blog_id'];
			}
		}
		return $aBlogs;
	}
	
	/**
	 * Возвращает список не персональных блогов с сортировкой по рейтингу
	 *
	 * @param int $iCount Возвращает общее количество элементов
	 * @param int $iCurrPage	Номер текущей страницы
	 * @param int $iPerPage		Количество элементов на одну страницу
	 * @return array
	 */
	public function GetBlogsRating(&$iCount,$iCurrPage,$iPerPage) {
		$sql = "SELECT 
					b.blog_id													
				FROM 
					".Config::Get('db.table.blog')." as b 									 
				WHERE 									
					b.blog_type NOT IN ('personal', 'secret')
				ORDER by b.blog_rating desc
				LIMIT ?d, ?d 	";
		$aReturn=array();
		if ($aRows=$this->oDb->selectPage($iCount,$sql,($iCurrPage-1)*$iPerPage, $iPerPage)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=$aRow['blog_id'];
			}
		}
		return $aReturn;
	}
	/**
	 * Получает список блогов в которых состоит пользователь
	 *
	 * @param int $sUserId ID пользователя
	 * @param int $iLimit	Ограничение на выборку элементов
	 * @return array
	 */
	public function GetBlogsRatingJoin($sUserId,$iLimit) {
		$sql = "SELECT 
					b.*													
				FROM 
					".Config::Get('db.table.blog_user')." as bu,
					".Config::Get('db.table.blog')." as b	
				WHERE 	
					bu.user_id = ?d
					AND
					bu.blog_id = b.blog_id
					AND				
					b.blog_type NOT IN ('personal', 'secret')
				ORDER by b.blog_rating desc
				LIMIT 0, ?d 
				;	
					";
		$aReturn=array();
		if ($aRows=$this->oDb->select($sql,$sUserId,$iLimit)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=Engine::GetEntity('Blog',$aRow);
			}
		}
		return $aReturn;
	}
	/**
	 * Получает список блогов, которые создал пользователь
	 *
	 * @param int $sUserId ID пользователя
	 * @param int $iLimit	Ограничение на выборку элементов
	 * @return array
	 */
	public function GetBlogsRatingSelf($sUserId,$iLimit) {
		$sql = "SELECT 
					b.*													
				FROM 					
					".Config::Get('db.table.blog')." as b	
				WHERE 						
					b.user_owner_id = ?d
					AND				
					b.blog_type NOT IN ('personal','secret')
				ORDER by b.blog_rating desc
				LIMIT 0, ?d 
			;";
		$aReturn=array();
		if ($aRows=$this->oDb->select($sql,$sUserId,$iLimit)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=Engine::GetEntity('Blog',$aRow);
			}
		}
		return $aReturn;
	}
  
  
	/**
	 * Возвращает полный список закрытых блогов
	 *
	 * @return array
	 */
	public function GetCloseBlogs() {
		$sql = "SELECT b.blog_id										
				FROM ".Config::Get('db.table.blog')." as b					
				WHERE b.blog_type IN('close', 'secret')
			;";
		$aReturn=array();
		if ($aRows=$this->oDb->select($sql)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=$aRow['blog_id'];
			}
		}
		return $aReturn;
	}
  
	
}
?>