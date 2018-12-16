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

				add_filter( 'upload_dir', 'ClubVan25CroppicHandler::tempUploadDir' );
				$fileHandle = wp_handle_upload( $file, array( 'test_form' => false ) );
				remove_filter( 'upload_dir', 'ClubVan25CroppicHandler::tempUploadDir' );

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
	
	public static void cropImage($imageData, $userData) {
		$imgUrl = $imageData['imgUrl'];
		$imgInitW = $imageData['imgInitW'];
		$imgInitH = $imageData['imgInitH'];
		$imgW = $imageData['imgW'];
		$imgH = $imageData['imgH'];
		$imgY1 = $imageData['imgY1'];
		$imgX1 = $imageData['imgX1'];
		$cropW = $imageData['cropW'];
		$cropH = $imageData['cropH'];
		
		$what = getimagesize($imgUrl);
		switch(strtolower($what['mime'])) {
			case 'image/png':
				$sourceImage = imagecreatefrompng($imgUrl);
				$fileType = 'png';
				break;
			case 'image/jpeg':
				$sourceImage = imagecreatefromjpeg($imgUrl);
				$fileType = 'jpeg';
				break;
			case 'image/gif':
				$sourceImage = imagecreatefromgif($imgUrl);
				$fileType = 'gif';
				break;
			default: die('image type not supported');
		}
		
		$fileName = "{$userData['year']} {$userData['name']} [{$userData['id']}]";
		
		$uploads = wp_upload_dir();		
		$localFileName = "clubvan25/accepted/$fileName.$fileType";
			
		$resizedImage = imagecreatetruecolor($imgW, $imgH);
		imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $imgW, $imgH, $imgInitW, $imgInitH);	
		
		$destinationImage = imagecreatetruecolor($cropW, $cropH);
		imagecopyresampled($destinationImage, $resizedImage, 0, 0, $imgX1, $imgY1, $cropW, $cropH, $cropW, $cropH);
						
		imagejpeg($destinationImage, path_join($uploads['basedir'], $localFileName), 100);
			
		$response = array(
			"status" => 'success',
			"url" => path_join($uploads['baseurl'], $localFileName) 
		);
				
		return json_encode($response);
	}
	
	public static function tempUploadDir( $dirs ) {
		$dir = '/clubvan25/temp';
		
		$dirs['subdir'] = $dir;
		$dirs['path'] = $dirs['basedir'] . $dir;
		$dirs['url'] = $dirs['baseurl'] . $dir;

		return $dirs;
	}
}