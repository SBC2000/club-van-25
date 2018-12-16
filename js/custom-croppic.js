var croppicContainerEyecandyOptions = {	
	loadCroppedPicture: php.image,
	uploadUrl: php.url,
	uploadData:{
		'action': php.uploadAction,
	},
	cropUrl: php.url,
	cropData:{
		'action': php.cropAction,
		'id': php.id,
	},
	disableRemove: true,
	outputUrlId:'croppedFileName',
	modal:true,
	rotateControls:false,	// I mix two version of croppic, so rotation is not supported
	loaderHtml:"<div class='loader bubblingG'><span id='bubblingG_1'></span><span id='bubblingG_2'></span><span id='bubblingG_3'></span></div>",
}
var cropContainerEyecandy = new Croppic('cropContainerEyecandy', croppicContainerEyecandyOptions);