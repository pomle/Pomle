<?
interport('mediaID');

switch($action)
{
	case 'upload':
		if( !isset($_FILES) || !is_array($_FILES) )
			throw New Exception(_('Inga filer hittades i begäran'));

		$preferredMediaType = $_POST['preferredMediaType'] ?: null;
		$mediaID = $_GET['mediaID'] ?: null;

		foreach($_FILES as $file)
		{
			$originalFilename = $file['name'];

			try
			{
				$Media = \Operation\Media::importFileToLibrary($file['tmp_name'], $file['name'], $preferredMediaType, null, $mediaID);

				Message::addNotice('Upload Success "' . $file['name'] . '": Identified as: ' . $Media::DESCRIPTION . ', Media ID: ' . sprintf('<a href="/MediaEdit.php?mediaID=%1$u">%1$u</a>', $Media->mediaID));

				$result['mediaIDs'][] = $Media->mediaID;
				$result['mediaID'] = $Media->mediaID;
			}
			catch(Exception $e)
			{
				Message::addError(sprintf('"%s" misslyckades: %s', $file['name'], $e->getMessage()));
			}
		}
	break;

	case 'url':
		try
		{
			$url = $_POST['url'];

			$Media = \Operation\Media::downloadFileToLibrary($url, $_GET['mediaID']);

			Message::addNotice('Fetch Success "' . $url . '": Identified as: ' . $Media::DESCRIPTION . ', Media ID: ' . sprintf('<a href="/MediaEdit.php?mediaID=%1$u">%1$u</a>', $Media->mediaID));

			$result['mediaIDs'][] = $Media->mediaID;
			$result['mediaID'] = $Media->mediaID;
		}
		catch(Exception $e)
		{
			Message::addError(sprintf('"%s" misslyckades: %s', $url, $e->getMessage()));
		}
	break;

	case 'publishToImgur':
		$mediaID = $_POST['mediaID'];

		if( !$Media = \Manager\Media::loadOneFromDB($_POST['mediaID']) )
			throw New Exception("Invalid Media ID");

		$fileName = $Media->getFilePath();

		$data = file_get_contents($fileName);

		$pvars = array('image' => base64_encode($data), 'key' => IMGUR_API_KEY);
		$timeout = 30;
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 'http://api.imgur.com/2/upload.json');
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);

		$json = curl_exec($curl);

		curl_close($curl);

		$response = json_decode($json);

		if( !$response )
			throw New \Exception("Non or weird Imgur Response");

		#print_r();


		Message::addNotice(sprintf('Imgur Original: <a href="%1$s">%1$s</a>', htmlspecialchars($response->upload->links->original)));

		#throw New Exception($json);

	break;

	case 'flushAutogen':
		ensurePolicies('AllowDeleteMedia');

		$displayFullPaths = $User->isAdministrator();

		if( $files = \Manager\Dataset\Media::getSpreadByID($mediaID) )
		{
			foreach($files as $fileName)
			{
				$displayFilename = $displayFullPaths ? $fileName : str_replace(DIR_MEDIA, '', $fileName);

				if( is_writable($fileName) && unlink($fileName) )
					Message::addNotice("File \"$displayFilename\" deleted");
				else
					Message::addAlert("Could not delete \"$displayFilename\"");
			}
		}
		else
		{
			Message::addAlert("No files found");
		}

		break;

	case 'save':
		ensurePolicies('AllowEditMedia');

		interport('mediaType');

		$Media = \Manager\Media::loadOneFromDB($mediaID);


		$query = \DB::prepareQuery("UPDATE
				Media
			SET
				mediaType = %s,
				fileOriginalName = IF(%u, NULLIF(%s, ''), fileOriginalName)
			WHERE
				ID = %u",
			$mediaType,
			isset($_POST['fileOriginalName']),
			$_POST['fileOriginalName'],
			$mediaID);

		\DB::queryAndCountAffected($query);

		Message::addNotice(MESSAGE_ROW_UPDATED);

	case 'load':
		ensurePolicies('AllowViewMedia');
		$query = \DB::prepareQuery("SELECT ID AS mediaID, mediaType FROM Media WHERE ID = %u", $mediaID);
		$result = DB::queryAndFetchOne($query);
		break;

	case 'delete':
		ensurePolicies('AllowDeleteMedia');
		if( !\Manager\Media::removeFromDB($mediaID) )
			throw New Exception(_('Kunde inte ta bort media'));

		Message::addNotice(MESSAGE_ROW_DELETED);
		break;
}