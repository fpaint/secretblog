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
	
	/**
	 * Список топиков из коллективных блогов
	 *
	 * @param int $iPage	Номер страницы
	 * @param int $iPerPage	Количество элементов на страницу
	 * @param string $sShowType	Тип выборки топиков
	 * @param string $sPeriod	Период в виде секунд или конкретной даты
	 * @return array
	 */
	public function GetTopicsCollective($iPage,$iPerPage,$sShowType='good',$sPeriod=null) {
		if (is_numeric($sPeriod)) {
			// количество последних секунд
			$sPeriod=date("Y-m-d H:00:00",time()-$sPeriod);
		}
		$aFilter=array(
			'blog_type' => array(
				'open',
			),
			'topic_publish' => 1,
		);
		if ($sPeriod) {
			$aFilter['topic_date_more'] = $sPeriod;
		}
		switch ($sShowType) {
			case 'good':
				$aFilter['topic_rating']=array(
					'value' => Config::Get('module.blog.collective_good'),
					'type'  => 'top',
				);
				break;
			case 'bad':
				$aFilter['topic_rating']=array(
					'value' => Config::Get('module.blog.collective_good'),
					'type'  => 'down',
				);
				break;
			case 'new':
				$aFilter['topic_new']=date("Y-m-d H:00:00",time()-Config::Get('module.topic.new_time'));
				break;
			case 'newall':
				// нет доп фильтра
				break;
			case 'discussed':
				$aFilter['order']=array('t.topic_count_comment desc','t.topic_id desc');
				break;
			case 'top':
				$aFilter['order']=array('t.topic_rating desc','t.topic_id desc');
				break;
			default:
				break;
		}
		/**
		 * Если пользователь авторизирован, то добавляем в выдачу
		 * закрытые блоги в которых он состоит
		 */
		if($this->oUserCurrent) {
			$aOpenBlogs = $this->Blog_GetAccessibleBlogsByUser($this->oUserCurrent);
			if(count($aOpenBlogs)) {
			  $aFilter['blog_type']['close'] = $aOpenBlogs;
        $aFilter['blog_type']['secret'] = $aOpenBlogs;
      }  
		}
		return $this->GetTopicsByFilter($aFilter,$iPage,$iPerPage);
	}
	
	/**
	 * Получает число новых топиков в коллективных блогах
	 *
	 * @return int
	 */
	public function GetCountTopicsCollectiveNew() {
		$sDate=date("Y-m-d H:00:00",time()-Config::Get('module.topic.new_time'));
		$aFilter=array(
			'blog_type' => array(
				'open',
			),
			'topic_publish' => 1,
			'topic_new' => $sDate,
		);
		/**
		 * Если пользователь авторизирован, то добавляем в выдачу
		 * закрытые блоги в которых он состоит
		 */
		if($this->oUserCurrent) {
			$aOpenBlogs = $this->Blog_GetAccessibleBlogsByUser($this->oUserCurrent);
			if(count($aOpenBlogs)) {
			  $aFilter['blog_type']['close'] = $aOpenBlogs;
        $aFilter['blog_type']['secret'] = $aOpenBlogs;
      }  
		}
		return $this->GetCountTopicsByFilter($aFilter);
	}

}
?>