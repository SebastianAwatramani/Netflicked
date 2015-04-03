itemToDelete = document.getElementById("deleteMovie");

itemToDelete.addEventListener('click', deleteItem, false);

function deleteItem(e) {
	url = window.location.href.split('#')[0];
	e.target.setAttribute("href", url + "&delete=1");
	e.preventDefault()
	itemToDelete.removeEventListener('click', deleteItem, false);

}