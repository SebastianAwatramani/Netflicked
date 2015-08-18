var starCount = document.getElementsByClassName('star');
if(starCount.length > 0) {
	var ratingContainerWidth = starCount.length * 27;
	var ratingContainer = document.querySelector('.ratingContainer');
	console.log(ratingContainer);
	ratingContainer.style.width = ratingContainerWidth + "px";
}
