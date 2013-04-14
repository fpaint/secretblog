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
 * Модуль для работы с топиками
 *
 * @package modules.topic
 * @since 1.0
 */
class PluginSecretblog_ModuleTopic extends PluginSecretblog_Inherit_ModuleTopic  {

	/**
	 * Получает список топиков по юзеру
	 *
	 * @param int $sUserId	ID пользователя
	 * @param int $iPublish	Флаг публикации топика
	 * @param int $iPage	Номер страницы
	 * @param int $iPerPage	Количество элементов на страницу
	 * @return array
	 */
	public function GetTopicsPersonalByUser($sUserId,$iPublish,$iPage,$iPerPage) {
		$aFilter=array(
			'topic_publish' => $iPublish,
			'user_id' => $sUserId,
			'blog_type' => array('open','personal'),
		);
		/**
		 * Если пользователь смотрит свой профиль, то добавляем в выдачу
		 * закрытые блоги в которых он состоит
		 */
		if($this->oUserCurrent && $this->oUserCurrent->getId()==$sUserId) {
			$aFilter['blog_type'][]='close';
      $aFilter['blog_type'][]='secret';
		}
		return $this->GetTopicsByFilter($aFilter,$iPage,$iPerPage);
	}
	
	/**
	 * Возвращает количество топиков которые создал юзер
	 *
	 * @param int $sUserId	ID пользователя
	 * @param int $iPublish	Флаг публикации топика
	 * @return array
	 */
	public function GetCountTopicsPersonalByUser($sUserId,$iPublish) {
		$aFilter=array(
			'topic_publish' => $iPublish,
			'user_id' => $sUserId,
			'blog_type' => array('open','personal'),
		);
		/**
		 * Если пользователь смотрит свой профиль, то добавляем в выдачу
		 * закрытые блоги в которых он состоит
		 */
		if($this->oUserCurrent && $this->oUserCurrent->getId()==$sUserId) {
			$aFilter['blog_type'][]='close';
      $aFilter['blog_type'][]='secret';
		}
		$s=serialize($aFilter);
		if (false === ($data = $this->Cache_Get("topic_count_user_{$s}"))) {
			$data = $this->oMapperTopic->GetCountTopics($aFilter);
			$this->Cache_Set($data, "topic_count_user_{$s}", array("topic_update_user_{$sUserId}"), 60*60*24);
		}
		return 	$data;
	}

}
?>