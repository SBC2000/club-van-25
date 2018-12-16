<?php

class ClubVan25CroppicHandler {
	private static $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");
	
	public static function uploadImage($file) {		
		
		$extension = end(explode(".", $file["name"]));

		if ( in_array($extension, self::$allowedExts)) {
			if ($file["error"] > 0) {
				$response = array(
					"status" => 'error',
					"message" => 'ERROR Return Code: '. $file["error"],
				);
			} else {	
				$filename = $file["tmp_name"];
				list($width, $height) = getimagesize( $filename );

				add_filter( 'upload_dir', 'ClubVan25Handler::setTempUploadDir' );
				$fileHandle = wp_handle_upload( $file, array( 'test_form' => false ) );
				remove_filter( 'upload_dir', 'ClubVan25Handler::setTempUploadDir' );

				if ( $fileHandle && !isset( $fileHandle['error'] ) ) {
					$response = array(
						"status" => 'success',
						"url" => $fileHandle['url'],
						"width" => $width,
						"height" => $height
					);
				} else {
					$response = array(
						"status" => 'error',
						"message" => $fileHandle['error'],
					);
				}
			}
		} else {
			$response = array(
				"status" => 'error',
				"message" => "Extension '$extension' is not supported",
			);
		}

		return json_encode($response);
	}
	
	public static function cropImage($imageData, $id) {
		$member = ClubVan25Handler::getMemberById($id);
		$fileName = $member->getFileName();
		
		$imgUrl = $imageData['imgUrl'];
		$imgInitW = $imageData['imgInitW'];
		$imgInitH = $imageData['imgInitH'];
		$imgW = $imageData['imgW'];
		$imgH = $imageData['imgH'];
		$imgY1 = $imageData['imgY1'];
		$imgX1 = $imageData['imgX1'];
		$cropW = $imageData['cropW'];
		$cropH = $imageData['cropH'];

		$imageConversionMethods = array (
			'image/png' => function($i) { return imagecreatefrompng($i); },
			'image/jpeg' => function($i) { return imagecreatefromjpeg($i); },
			'image/gif' => function($i) { return imagecreatefromgif($i); },
		);
		
		$imageType = strtolower(getimagesize($imgUrl)['mime']);
		$conversionMethod = $imageConversionMethods[$imageType];
		
		if($conversionMethod) {
			$sourceImage = $conversionMethod($imgUrl);
				
			$resizedImage = imagecreatetruecolor($imgW, $imgH);
			imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $imgW, $imgH, $imgInitW, $imgInitH);	
			
			$destinationImage = imagecreatetruecolor($cropW, $cropH);
			imagecopyresampled($destinationImage, $resizedImage, 0, 0, $imgX1, $imgY1, $cropW, $cropH, $cropW, $cropH);
							
			imagejpeg($destinationImage, path_join(ClubVan25Handler::getUploadDir(), $fileName), 100);
			
			$member->setHasFile(true);
			$updateResult = ClubVan25Handler::updateMember($member);
			
			if (false === $updateResult) {
				$response = array (
					"status" => "error",
					"message" => "Updating the database failed",
				);
			} else {
				$response = array(
					"status" => 'success',
					// timestamp is added to force reload when changing the image
					"url" => path_join(ClubVan25Handler::getUploadUrl(), $fileName . '?timestamp=' . time()) 
				);
			}
		} else {
			$response = array(
				"status" => 'error',
				"message" => "Image type $imageType is not supported"
			);
		}
		return json_encode($response);
	}
}