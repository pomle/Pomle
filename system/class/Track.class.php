<?
class Track extends Post
{
	const TYPE = POST_TYPE_TRACK;


	public static function loadFromDB($postIDs)
	{
		$posts = parent::loadFromDB($postIDs);

		$query = \DB::prepareQuery("SELECT * FROM PostTracks WHERE postID IN %a", array_keys($posts));

		$result = \DB::queryAndFetchResult($query);

		while($track = \DB::assoc($result))
		{
			$postID = (int)$track['postID'];
			if( isset($posts[$postID]) )
			{
				$Post = $posts[$postID];

				$Post->lastFmID = (int)$track['lastFmID'];
				$Post->artist = $track['artist'];
				$Post->track = $track['track'];
				$Post->artistURL = $track['artistURL'];
				$Post->trackURL = $track['trackURL'];
			}
		}

		return $posts;
	}

	public static function saveToDB(\Track $Post)
	{
		parent::saveToDB($Post);

		$query = \DB::prepareQuery("REPLACE INTO
			PostTracks (
				postID,
				lastFmID,
				artist,
				track,
				artistURL,
				trackURL
			) VALUES (
				%u,
				%u,
				%s,
				%s,
				NULLIF(%s, ''),
				NULLIF(%s, ''))",
			$Post->postID,
			$Post->lastFmID,
			$Post->artist,
			$Post->track,
			$Post->artistURL,
			$Post->trackURL);

		#throw New Exception($query);

		\DB::query($query);

		return true;
	}


	public function getSummary()
	{
		return sprintf(
			'<a href="http://open.spotify.com/search/%s" class="spotifySearch">Spotify Sök</a>' .
			'<a href="%s" class="lastFMLookUp">LastFM Lookup</a>',
			urlencode(sprintf('%s %s', $this->artist, $this->track)),
			$this->artistURL
		);
	}

	public function getURL()
	{
		return $this->trackURL;
		return sprintf('/TrackView.php?trackID=%u', $this->postID);
	}
}