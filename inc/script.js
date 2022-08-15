
function quote (text) {
	// Source: Fuukaba Basic, probably from Futaba/Wakaba before that

	var textarea = document.forms.postform.message;
	text = '>>' + text;

	if (textarea) {
		if (textarea.createTextRange && textarea.caretPos) { // IE
			var caretPos	= textarea.caretPos;
			caretPos.text	= caretPos.text.charAt (caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		}
		else if (textarea.setSelectionRange) { // Firefox
			var start	= textarea.selectionStart;
			var finish	= textarea.selectionEnd;
			textarea.value	= textarea.value.substr (0, start) + text + textarea.value.substr (finish);
			textarea.setSelectionRange (start + text.length, start + text.length);
		}
		else {
			textarea.value	+= text + ' ';
		}

	}
}

var lastHighlighted = false;

function reHighlight() {
	var hash = window.location.hash;
	if (hash == undefined) return;
	
	// Remove any highlighted posts.
	if (lastHighlighted) {
		lastHighlighted.class = "reply";
		lastHighlighted.className = "reply";
	}
	
	// Highlight the selected post.
	if (hash.length) {
		var id = document.getElementById("reply"+hash.substr(1));
		if (id) {
			id.className = "highlight";
			id.class = "highlight";
			lastHighlighted = id;
		}
	}	
}

function onFirstLoad() {
	lastHighlighted = false;
	window.onhashchange = reHighlight;
	if (window.location.hash) reHighlight();
}