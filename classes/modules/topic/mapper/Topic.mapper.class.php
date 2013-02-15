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
 * Объект маппера для работы с БД
 *
 * @package modules.topic
 * @since 1.0
 */
class PluginSecretblog_ModuleTopic_MapperTopic extends PluginSecretblog_Inherit_ModuleTopic_MapperTopic {
	/**
	 * Строит строку условий для SQL запроса топиков
	 *
	 * @param array $aFilter	Фильтр
	 * @return string
	 */
	protected function buildFilter($aFilter) {
		$sWhere='';
		if (isset($aFilter['topic_date_more'])) {
			$sWhere.=" AND t.topic_date_add >  '".mysql_real_escape_string($aFilter['topic_date_more'])."'";
		}
		if (isset($aFilter['topic_publish'])) {
			$sWhere.=" AND t.topic_publish =  ".(int)$aFilter['topic_publish'];
		}
		if (isset($aFilter['topic_rating']) and is_array($aFilter['topic_rating'])) {
			$sPublishIndex='';
			if (isset($aFilter['topic_rating']['publish_index']) and $aFilter['topic_rating']['publish_index']==1) {
				$sPublishIndex=" or topic_publish_index=1 ";
			}
			if ($aFilter['topic_rating']['type']=='top') {
				$sWhere.=" AND ( t.topic_rating >= ".(float)$aFilter['topic_rating']['value']." {$sPublishIndex} ) ";
			} else {
				$sWhere.=" AND ( t.topic_rating < ".(float)$aFilter['topic_rating']['value']."  ) ";
			}
		}
		if (isset($aFilter['topic_new'])) {
			$sWhere.=" AND t.topic_date_add >=  '".$aFilter['topic_new']."'";
		}
		if (isset($aFilter['user_id'])) {
			$sWhere.=is_array($aFilter['user_id'])
				? " AND t.user_id IN(".implode(', ',$aFilter['user_id']).")"
				: " AND t.user_id =  ".(int)$aFilter['user_id'];
		}
		if (isset($aFilter['blog_id'])) {
			if(!is_array($aFilter['blog_id'])) {
				$aFilter['blog_id']=array($aFilter['blog_id']);
			}
			$sWhere.=" AND t.blog_id IN ('".join("','",$aFilter['blog_id'])."')";
		}
		if (isset($aFilter['blog_type']) and is_array($aFilter['blog_type'])) {
			$aBlogTypes = array();
			foreach ($aFilter['blog_type'] as $sType=>$aBlogId) {
				/**
				 * Позиция вида 'type'=>array('id1', 'id2')
				 */
				if(!is_array($aBlogId) && is_string($sType)){
					$aBlogId=array($aBlogId);
				}
				/**
				 * Позиция вида 'type'
				 */
				if(is_string($aBlogId) && is_int($sType)) {
					$sType=$aBlogId;
					$aBlogId=array();
				}
                                if(count($aBlogId)==0) {
                                  $aBlogTypes[] = "(b.blog_type='".$sType."')";
                                } else if($sType=='close') {
                                  $aBlogTypes[] = "(b.blog_type IN ('close','secret') AND t.blog_id IN ('".join("','",$aBlogId)."'))";
                                } else {
                                  $aBlogTypes[] = "(b.blog_type='".$sType."' AND t.blog_id IN ('".join("','",$aBlogId)."'))";
                                }
			}
			$sWhere.=" AND (".join(" OR ",(array)$aBlogTypes).")";
		}
		if (isset($aFilter['topic_type'])) {
			if(!is_array($aFilter['topic_type'])) {
				$aFilter['topic_type']=array($aFilter['topic_type']);
			}
			$sWhere.=" AND t.topic_type IN ('".join("','",array_map('mysql_real_escape_string',$aFilter['topic_type']))."')";
		}
		return $sWhere;
	}
}
?>