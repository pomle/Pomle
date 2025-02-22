<?
namespace Post;

class Diary extends \Post
{
	const TYPE = \POST_TYPE_DIARY;

	public
		$content;


	public static function loadFromDB($postIDs, $skipContent = false)
	{
		$posts = parent::loadFromDB($postIDs);

		$postIDs = array_keys($posts);

		if( $skipContent === false )
		{
			$query = \DB::prepareQuery("SELECT
					postID,
					content
				FROM
					PostDiaries
				WHERE
					postID IN %a",
				$postIDs);

			$result = \DB::queryAndFetchResult($query);

			$mediaIDs = array();

			while($row = \DB::assoc($result))
			{
				$postID = (int)$row['postID'];
				$Post = $posts[$postID];
				$Post->content = $row['content'];
				$mediaIDs = array_merge($mediaIDs, $Post->mediaIDs = $Post->getContentMediaIDs());
			}

			$medias = \Manager\Media::loadFromDB($mediaIDs);

			foreach($posts as $Post)
			{
				if( isset($Post->mediaIDs) )
				{
					foreach($Post->mediaIDs as $mediaID)
						if( isset($medias[$mediaID]) )
							$Post->addMedia($medias[$mediaID]);

					unset($Post->mediaIDs);
				}
			}
			reset($posts);
		}

		return $posts;
	}

	public static function saveToDB(Diary $Post)
	{
		parent::saveToDB($Post);

		$query = \DB::prepareQuery("INSERT INTO PostDiaries (postID, content) VALUES(%u, %s) ON DUPLICATE KEY UPDATE content = VALUES(content)", $Post->postID, $Post->content);
		\DB::query($query);

		return true;
	}


	public function getContentMediaHashes()
	{
		$mediaHashes = array();

		if( preg_match_all('%<(flipblog)?:media.+(hash="(.+)").+/?>%U', $Post->content, $match) )
			$mediaHashes = array_merge($mediaHashes, $match[3]);

		return $mediaHashes;
	}

	public function getContentMediaIDs()
	{
		$mediaIDs = array();

		if( preg_match_all('%<(flipblog)?:media.+(mediaID="([0-9]+)").+/?>%U', $this->content, $match) )
			$mediaIDs = array_merge($mediaIDs, $match[3]);

		return $mediaIDs;
	}

	public function getHTMLContent()
	{
		$content = $this->content;

		if( preg_match_all('%<(pre|code).*>(.*)</(pre|code)>%Usme', $content, $matches) )
			foreach($matches[2] as $code)
				$content = str_replace($code, htmlspecialchars($code), $content);

		#$content = preg_replace('%(<(pre|code).*>)(.*)(</(pre|code)>)%Usme', "'\\1'.htmlspecialchars('\\3').'\\4'", $content);

		$replacements = array
		(
			"\t" => '&nbsp;&nbsp;&nbsp;&nbsp;' ### Tabs >> four HTML no-break spaces
		);

		$content = str_replace(array_keys($replacements), $replacements, $content);

		foreach($this->getPlugins() as $Plugin)
			$content = str_replace($Plugin->source, $Plugin->getHTML(), $content);

		return $content;
	}

	public function getPlugins()
	{
		$plugins = array();

		foreach($this->getPluginTags() as $plugin => $tags)
		{
			$pluginClass = '\\Plugin\\' . ucfirst($plugin);

			if( !class_exists($pluginClass) ) continue;

			foreach($tags as $tag)
				$plugins[] = new $pluginClass($tag);
		}

		return $plugins;
	}

	public function getPluginTags() ### Returns 2-dimensional array with first level containing plugin types and second level plugin tags
	{
		$tags = array();

		if( preg_match_all('%<flipblog:([A-Za-z]+) .*/>%', $this->content, $matches) )
			foreach(array_combine($matches[0], $matches[1]) as $tag => $type)
				$tags[$type][] = $tag;

		return $tags;
	}

	public function getSummary()
	{
		$lenLimit = 128;
		$summary = trim(strip_tags($this->content));
		if( mb_strlen($summary) > $lenLimit ) $summary = mb_substr($summary, 0, 164) . '...';
		return $summary;
	}

	public function getURL()
	{
		return sprintf('/DiaryView.php?diaryID=%u', $this->postID);
	}
}