<?
namespace Media;

class Audio extends Common\Audible
{
	const TYPE = MEDIA_TYPE_AUDIO;
	const DESCRIPTION = 'Audio';

	protected
		$streamInfo;

	public static function canHandleFile($filePath)
	{
		if( !$streamInfo = \App\FFMPEG::doInfo($filePath) )
			return false;

		### Reject streams without Audio
		if( is_null($streamInfo['audio']) ) return false;

		### Reject streams with Video
		if( !is_null($streamInfo['video']) ) return false;

		return true;
	}


	public function __destruct()
	{
	}


	public function getInfo()
	{
		if( !isset($this->streamInfo) )
			$this->streamInfo = \App\FFMPEG::doInfo($this->getFilePath());

		return $this->streamInfo;
	}
}