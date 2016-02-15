<!--
/**
 * plxEditor
 *
 * @package PLX
 * @author	Stephane F
 **/
PLXEDITOR={};

function E$(id){return document.getElementById(id)}

PLXEDITOR.editor=function() {

	var buttons = ['bold', 'italic', 'underline', 'strikethrough', 'separator', 'image', 'forecolor', 'backcolor', 'link', 'unlink', 'removeformat', 'separator', 'justifyleft', 'justifycenter', 'justifyright', 'separator', 'insertorderedlist', 'insertunorderedlist', 'separator', 'outdent', 'indent', 'separator', 'subscript', 'superscript', 'smilies', 'separator', 'html', 'fullscreen'];

	function create(editorName, textareaId, medias_path, plugins_path){
		this.path = {
			editor : plugins_path+'plxeditor/',
			images : plugins_path+'plxeditor/plxeditor/images/',
			css	   : plugins_path+'plxeditor/plxeditor/css/',
			medias : medias_path
		}
		this.editor = editorName;
		this.textareaId = textareaId;
		// Chargement des donn�es avec conversion des liens
		this.textareaValue = this.convertLinks(E$(this.textareaId).value, 0);
		//
		this.popup = null;
		this.viewSource = false;
		this.viewFullscreen = false;
		// browser detection
		var ie = 0;
			try { ie = navigator.userAgent.match( /(MSIE |Trident.*rv[ :])([0-9]+)/ )[ 2 ]; }
		catch(e){}
		this.browser = {
			"ie": ie,
			"gecko" : (navigator.userAgent.toLowerCase().indexOf("gecko") != -1)
		}
		// EDITOR
		var editor = document.createElement("div");
		editor.id = this.textareaId+"-wysiwyg";
		editor.setAttribute('class', 'wysiwyg');
		editor.innerHTML = this.getEditorHtml();
		E$(this.textareaId).parentNode.replaceChild(editor, E$(this.textareaId));
		// FRAME
		this.frame = E$(this.textareaId+"-iframe").contentWindow;
		this.frame.document.designMode = "on";
		this.frame.document.open();
		this.frame.document.write(this.getFrameHtml());
		this.frame.document.close();
		this.setFrameContent();
		this.frame.focus();
		// Update the textarea with content in iframe when user submits form
		var f_submit = this.editor + '.updateTextArea()';
		for (var i=0;i<document.forms.length;i++) { PLXEDITOR.event.addEvent(document.forms[i], 'submit', function() { eval(f_submit) }); }
		// Word counter on keyup event
		var f = this.editor+".keyup()";
		PLXEDITOR.event.addEvent(this.frame, 'keyup', function(evt) { eval(f) });
		this.keyup(null);
	}
	//------------
	create.prototype.keyup=function(evt) {
		if(this.viewSource==true) {
			return;
		}
		// words counter
		//var txt = document.all ? this.frame.document.body.innerText : this.frame.document.body.textContent;
		if (document.body.innerText) {
			var txt = this.frame.document.body.innerText;
		} else {
			var txt = this.frame.document.body.innerHTML.replace(/<br>/gi,"\n");
			var txt = txt.replace(/<\/?[^>]+(>|$)/g, "");
		}
		var count = txt!=undefined ? txt.split(/\b\S+\b/g).length - 1 : 0;
		E$(this.textareaId+'-footer').innerHTML = "Mots : " + count;
		// autoresize
		//E$(this.textareaId+"-iframe").style.height = this.frame.document.body.scrollHeight + "px";
	},
	create.prototype.getEditorHtml=function() {
		var html = '';
		html += '<input type="hidden" id="'+this.textareaId+'" name="'+this.textareaId.replace(/id_/,'')+'" value="" />';
		html += '<div id="'+this.textareaId+'-toolbar" class="toolbar">';
		// toolbar
		html += '<select onchange="'+this.editor+'.execCommand(\'formatblock\', this.value);this.selectedIndex=0;"><option value="">Style</option><option value="<h1>">H1</option><option value="<h2>">H2</option><option value="<h3>">H3</option><option value="<p>">P</option><option value="<pre>">Pre</option></select>';
		for (var i = 0; i < buttons.length; ++i) {
			if(buttons[i]=='separator') {
				html += '<div class="separator"></div>';
			} else {
				html += '<img id="'+this.textareaId+'-'+buttons[i]+'" src="'+this.path.images+buttons[i]+'.gif" width="20" height="20" alt="" title="" onclick="'+this.editor+'.execCommand(\''+buttons[i]+'\')" />';
			}
		}
		// iframe
		html += '</div>';
		html += '<div id="'+this.textareaId+'-editor" class="editor">';
		html += '<iframe id="'+this.textareaId+'-iframe" class="iframe resizable" frameborder="0"></iframe>';
		html += '<div id="'+this.textareaId+'-footer" class="footer"></div>';
		html += '</div>'; // fin frame
		return html;
	},
	create.prototype.trim=function(str) {
		try {return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '') } catch(e) { return str; };
	},
	create.prototype.getselection=function() {
		var win = this.frame; var doc = win.document;
		var sel = doc.selection;
		if (this.browser.ie && this.browser.ie < 11) {
			try {
				return sel.createRange();
			} catch (e2) {
				return win.getSelection();
			}
		} else {
			return win.getSelection().getRangeAt(0).toString();
		}
		return null;
	},
	create.prototype.pasteHTML=function(html) {
		var sel = this.frame.document.getSelection();
		if (sel.getRangeAt && sel.rangeCount) {
			range = sel.getRangeAt(0);
			range.deleteContents();
		}
		
		var el = this.frame.document.createElement("div");
		el.innerHTML = html;
		var frag = this.frame.document.createDocumentFragment(), node, lastNode;
		while ( (node = el.firstChild) ) {
			lastNode = frag.appendChild(node);
		}
		
		var firstNode = frag.firstChild;
		range.insertNode(frag);
		
		if (lastNode) {
			range = range.cloneRange();
			range.setStartAfter(lastNode);
			if (selectPastedContent) {
				range.setStartBefore(firstNode);
			} else {
				range.collapse(true);
			}
				sel.removeAllRanges();
				sel.addRange(range);
		}	
	},
	create.prototype.execCommand=function(cmd, value) {
		this.frame.focus();
		if (cmd == "image" && !value) {
			this.openPopup(this.path.editor+'medias.php?id='+this.editor, this.editor, 780, 580);
		} else if (cmd == "link" && !value) {
			sel = this.getselection();
			new PLXEDITOR.linker.create(this.editor, this.textareaId+'-link', this.trim(sel));
		} else if (cmd == "forecolor" && !value) {
			new PLXEDITOR.cpicker.create(this.editor, this.textareaId+'-forecolor', "forecolor");
		} else if (cmd == "backcolor" && !value) {
			new PLXEDITOR.cpicker.create(this.editor, this.textareaId+'-backcolor', (this.browser.ie ? "backcolor" : "hilitecolor") );
		} else if (cmd == "smilies" && !value) {
			new PLXEDITOR.smilies.create(this.editor, this.textareaId+'-smilies', "smilies", this.path);
		} else if (cmd == "html" && !value) {
			this.toggleSource();
		} else if (cmd == "fullscreen" && !value) {
			this.toggleFullscreen();
		} else if (cmd == "inserthtml" && this.browser.ie) { // IE
			if(this.viewSource==true) return;
			this.pasteHTML(value);
		} else {
			if(this.viewSource==true) return;
			this.frame.document.execCommand(cmd, false, value);
		}
		this.frame.focus();
	},
	create.prototype.updateTextArea=function() {
		if(this.viewSource) { this.toggleSource(); }
		txt = this.frame.document.body.innerHTML;
		txt = this.convertLinks(txt, 1); // conversion des liens
		txt = this.toXHTML(txt);
		E$(this.textareaId).value = txt;
	},
	create.prototype.setFrameContent=function () {
		try { this.frame.document.body.innerHTML = this.textareaValue; } catch (e) { setTimeout(this.setFrameContent, 10); }
	},
	create.prototype.getFrameHtml=function() {
		var html = "";
		var html = "<!DOCTYPE html>";
		html += '<html><head>';
		html += '<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">';
		html += '<style type="text/css">pre { background-color: #fff; padding: 0.75em 1.5em; border: 1px solid #dddddd;* }</style>';
		html += '<style type="text/css">html,body { font-size: 93.7%; font-family: helvetica, arial, sans-serif; cursor: text; } body { margin: 0.5em; padding: 0; } img { border:none; }</style>';
		html += '</head><body></body></html>';
		return html;
	},
	create.prototype.toggleFullscreen = function() {
		if (!this.viewFullscreen) {
			E$(this.textareaId+'-wysiwyg').setAttribute('class', 'wysiwyg-fullscreen');
			this.viewFullscreen = true;
		} else {
			E$(this.textareaId+'-wysiwyg').setAttribute('class', 'wysiwyg');

			this.viewFullscreen = false;
		}
		this.frame.focus();
	},
	create.prototype.getViewportHeight=function() {
		var height;
		if (window.innerHeight!=window.undefined) height=window.innerHeight;
		else if (document.compatMode=='CSS1Compat') height=document.documentElement.clientHeight;
		else if (document.body) height=document.body.clientHeight;
		return height-100;
	},
	create.prototype.convertLinks=function(txt, how) {
		// conversion des liens
		if(how==0) {
			txt=txt.replace(new RegExp(this.path.medias, 'g'), "../../"+this.path.medias);
		} else {
			txt=txt.replace(new RegExp("../../"+this.path.medias, 'g'), this.path.medias);
		}
		return txt;
	},
	create.prototype.toggleSource=function() {
		var html, txt;
		if (!this.viewSource) {
			txt = this.frame.document.body.innerHTML;
			// conversion des liens
			txt = this.convertLinks(txt, 1);
			txt = this.toXHTML(txt);
			txt = this.formatHTML(txt);
			this.frame.document.body.innerHTML = txt.toString();
			// change icon image
			E$(this.textareaId+'-html').src = this.path.images+'text.gif';
			// set color css file
			var filecss=this.frame.document.createElement("link");
			filecss.rel = 'stylesheet'
			filecss.type = 'text/css';
			filecss.href = this.path.css+'viewsource.css';
			this.frame.document.getElementsByTagName("head")[0].appendChild(filecss);
			// set the font values for displaying HTML source
			this.frame.document.body.style.fontSize = "13px";
			this.frame.document.body.style.fontFamily = "Courier New";
			this.viewSource = true;
			E$(this.textareaId+'-footer').innerHTML = "";
		} else {
			if (this.browser.ie) {
				txt = this.frame.document.body.innerText;
				// conversion des liens
				txt = this.convertLinks(txt.toString(), 0);
				this.frame.document.body.innerHTML = txt;
			} else {
				html = this.frame.document.body.ownerDocument.createRange();
				html.selectNodeContents(this.frame.document.body);
				// conversion des liens
				txt = this.convertLinks(html.toString(), 0);
				this.frame.document.body.innerHTML = txt;
			}
			// change icon image
			E$(this.textareaId+'-html').src = this.path.images+'html.gif';
			// set the font values for displaying HTML source
			this.frame.document.body.style.fontSize = "";
			this.frame.document.body.style.fontFamily = "";
			this.viewSource = false;
			this.keyup(null);
		}
	},
	create.prototype.toXHTML=function(v) {
		function lc(str){return str.toLowerCase()}
		function sa(str){return str.replace(/("|;)\s*[A-Z-]+\s*:/g,lc);}
		v=v.replace(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\)/gi, function toHex($1,$2,$3,$4) { return '#' + (1 << 24 | $2 << 16 | $3 << 8 | $4).toString(16).substr(1); });
		v=v.replace(/<span class="apple-style-span">(.*)<\/span>/gi,'$1');
		v=v.replace(/s*class="apple-style-span"/gi,'');
		v=v.replace(/s*class="webkit-indent-blockquote"/gi,'');
		v=v.replace(/<span style="">/gi,'');
		v=v.replace(/<b\b[^>]*>(.*?)<\/b[^>]*>/gi,'<strong>$1</strong>');
		v=v.replace(/<i\b[^>]*>(.*?)<\/i[^>]*>/gi,'<em>$1</em>');
		v=v.replace(/<(s|strike)\b[^>]*>(.*?)<\/(s|strike)[^>]*>/gi,'<span style="text-decoration: line-through;">$2</span>');
		v=v.replace(/<u\b[^>]*>(.*?)<\/u[^>]*>/gi,'<span style="text-decoration:underline">$1</span>');
		v=v.replace(/<(b|strong|em|i|u) style="font-weight: normal;?">(.*)<\/(b|strong|em|i|u)>/gi,'$2');
		v=v.replace(/<(b|strong|em|i|u) style="(.*)">(.*)<\/(b|strong|em|i|u)>/gi,'<span style="$2"><$4>$3</$4></span>');
		v=v.replace(/<blockquote .*?>(.*?)<\/blockquote>/gi,'<blockquote>$1<\/blockquote>');
		v=v.replace(/<span style="font-weight: normal;?">(.*?)<\/span>/gi,'$1');
		v=v.replace(/<span style="font-weight: bold;?">(.*?)<\/span>/gi,'<strong>$1</strong>');
		v=v.replace(/<span style="font-style: italic;?">(.*?)<\/span>/gi,'<em>$1</em>');
		v=v.replace(/<span style="font-weight: bold;?">(.*?)<\/span>|<b\b[^>]*>(.*?)<\/b[^>]*>/gi,'<strong>$1</strong>')
		v=v.replace(/BACKGROUND-COLOR/gi,'background-color');
		//v=v.replace(/<div><br \/><\/div>/gi, '<p></p>');
		v=v.replace(/<(IMG|INPUT|BR|HR|LINK|META)([^>]*)>/gi,"<$1$2 />") //self-close tags
		v=v.replace(/(<\/?[A-Z]*)/g,lc) // lowercase tags
		v=v.replace(/STYLE="[^"]*"/gi,sa); //lc style atts
		return v;
	},
	create.prototype.formatHTML=function(html) {
		//strip white space
		html = html.replace(/\s/g, ' ');
		//convert html to text
		html = html.replace(/&/g, '&amp;');
		html = html.replace(/</g, '&lt;');
		html = html.replace(/>/g, '&gt;');
		//change all attributes " to &quot; so they can be distinguished from the html we are adding
		html = html.replace(/="/g, '=&quot;');
		html = html.replace(/=&quot;(.*?)"/g, '=&quot;$1&quot;');
		//search for opening tags
		html = html.replace(/&lt;([a-z](?:[^&|^<]+|&(?!gt;))*?)&gt;/gi, "<span class=\"tag\">&lt;$1&gt;</span><blockquote>");
		//Search for closing tags
		html = html.replace(/&lt;\/([a-z].*?)&gt;/gi, "</blockquote><span class=\"tag\">&lt;/$1&gt;</span>");
		//search for self closing tags
		html = html.replace(/\/&gt;<\/span><blockquote>/gi, "/&gt;</span>");
		//Search for values
		html = html.replace(/&quot;(.*?)&quot;/gi, "<span class=\"literal\">\"$1\"</span>");
		//search for comments
		html = html.replace(/&lt;!--(.*?)--&gt;/gi, "<span class=\"comment\">&lt;!--$1--&gt;</span>");
		//search for html entities
		html = html.replace(/&amp;(.*?);/g, '<b>&amp;$1;</b>');
		return html;
	},
	create.prototype.openPopup=function(fichier,nom,width,height) {
		this.popup = window.open(unescape(fichier) , nom, "directories=no, toolbar=no, menubar=no, location=no, resizable=yes, scrollbars=yes, width="+width+" , height="+height);
		if(this.popup) {
			this.popup.focus();
		} else {
			alert('Ouverture de la fenetre bloquee par un anti-popup!');
		}
		return;
	};
	return{create:create}
}();

PLXEDITOR.event=function() {
	return {
		addEvent:function(obj, evType, fn){
			if (obj.addEventListener){
				obj.addEventListener(evType, fn, false);
				return true;
			} else if (obj.attachEvent){
				var r = obj.attachEvent("on"+evType, fn);
				return r;
			} else {
				return false;
			}
		},
		removeEvent:function removeEvent(obj, evType, fn, useCapture){
			if (obj.removeEventListener){
				obj.removeEventListener(evType, fn, useCapture);
				return true;
			} else if (obj.detachEvent){
				var r = obj.detachEvent("on"+evType, fn);
				return r;
			} else {
				alert("Handler could not be removed");
			}
		}
	}
}();

PLXEDITOR.dialog=function() {
	return {
		close:function(obj){
			var dialog = E$(obj);
			if(dialog!=null) {
				document.body.removeChild(dialog); return;
			}
		},
		getAbsoluteOffsetTop:function(obj){
			var top = obj.offsetTop;
			var parent = obj.offsetParent;
			while (parent != document.body && parent != null) {
				top += parent.offsetTop;
				parent = parent.offsetParent;

			}
			return top;
		},
		getAbsoluteOffsetLeft:function(obj) {
			var left = obj.offsetLeft;
			var parent = obj.offsetParent;
			while (parent != document.body && parent != null) {
				left += parent.offsetLeft;
				parent = parent.offsetParent;
			}
			return left;
		}
	}
}();

PLXEDITOR.linker=function() {
	function create(editor, button, value){
		this.editor=editor;
		this.button=button;
		this.value=value;
		if(E$('linker')) return PLXEDITOR.dialog.close('linker');
		this.showPanel();
	}
	//------------
	create.prototype.showPanel=function(){
		var elemDiv = document.createElement('div');
		elemDiv.id = 'linker';
	    elemDiv.style.position = 'absolute';
		elemDiv.style.display = 'block';
		elemDiv.style.border = '#aaa 1px solid';
		var top = PLXEDITOR.dialog.getAbsoluteOffsetTop(E$(this.button)) + 20;
		var left = PLXEDITOR.dialog.getAbsoluteOffsetLeft(E$(this.button));
		elemDiv.style.top = top + 'px';
		elemDiv.style.left = left + 'px';
		elemDiv.innerHTML = this.panel();
		document.body.appendChild(elemDiv);
	},
	create.prototype.panel=function() {
		var table = '<table id="popup" border="0" cellspacing="0" cellpadding="0">';
		table += '<tr><td>Lien :</td><td><input type="text" value="http://" id="txtHref" /></td></tr>';
		table += '<tr><td>Titre du lien :</td><td><input type="text" value="'+this.value+'" id="txtTitle" /></td></tr>';
		table += '<tr><td>class :</td><td><input type="text" value="" id="txtClass" /></td></tr>';
		table += '<tr><td>rel :</td><td><input type="text" value="" id="txtRel" /></td></tr>';
		table += '<tr><td colspan="2" style="text-align:center"><input type="submit" value="Ajouter" onclick="PLXEDITOR.linker.setLink('+this.editor+')" />&nbsp;<input type="submit" name="btnCancel" id="btnCancel" value="Annuler" onclick="PLXEDITOR.dialog.close(\'linker\')" /></td></tr>';
		table += '</table>';
		return table;
	};
	return{
		create:create,
		setLink:function(editor) {
			var sHref = (E$('txtHref') ? E$('txtHref').value : '');
			var sTtitle = (E$('txtTitle') ? E$('txtTitle').value : '');
			var sClass = (E$('txtClass') ? (E$('txtClass').value!=''? ' class="'+E$('txtClass').value+'"':'') : '');
			var sRel = (E$('txtRel') ? (E$('txtRel').value!=''? ' rel="'+E$('txtRel').value+'"':'') : '');
			if(sTtitle=='' || PLXEDITOR.linker.isUrl(sHref)==false) return;
			editor.execCommand('inserthtml', '<a href="'+sHref+'" title="'+sTtitle+'"'+sClass+sRel+'>'+sTtitle+'</a> ');
			PLXEDITOR.dialog.close('linker');
		},
		isUrl:function(s) {
			var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
			return regexp.test(s);
		}
	}
}();

PLXEDITOR.cpicker=function(){

	var colors = [
		["ffffff", "ffcccc", "ffcc99", "ffff99", "ffffcc", "99ff99", "99ffff", "ccffff", "ccccff", "ffccff"],
		["cccccc", "ff6666", "ff9966", "ffff66", "ffff33", "66ff99", "33ffff", "66ffff", "9999ff", "ff99ff"],
		["c0c0c0", "ff0000", "ff9900", "ffcc66", "ffff00", "33ff33", "66cccc", "33ccff", "6666cc", "cc66cc"],
		["999999", "cc0000", "ff6600", "ffcc33", "ffcc00", "33cc00", "00cccc", "3366ff", "6633ff", "cc33cc"],
		["666666", "990000", "cc6600", "cc9933", "999900", "009900", "339999", "3333ff", "6600cc", "993399"],
		["333333", "660000", "993300", "996633", "666600", "006600", "336666", "000099", "333399", "663366"],
		["000000", "330000", "663300", "663333", "333300", "003300", "003333", "000066", "330099", "330033"]];

	function create(editor, button, action){
		this.editor=editor;
		this.button=button;
		this.action=action;
		if(E$('colorpicker')) return PLXEDITOR.dialog.close('colorpicker');
		this.displayPanel();
	}
	//------------
	create.prototype.displayPanel=function(){
		var elemDiv = document.createElement('div');
		elemDiv.id = 'colorpicker';
	    elemDiv.style.position = 'absolute';
		elemDiv.style.display = 'block';
		var top = PLXEDITOR.dialog.getAbsoluteOffsetTop(E$(this.button)) + 20;
		var left = PLXEDITOR.dialog.getAbsoluteOffsetLeft(E$(this.button));
		elemDiv.style.top = top + 'px';
		elemDiv.style.left = left + 'px';
		elemDiv.innerHTML = this.panel();
		document.body.appendChild(elemDiv);
	},
	create.prototype.panel=function() {
		var table = '<table id="popup" border="0" cellspacing="0" cellpadding="0">';
		for(var y=0; y < colors.length; y++) {
			table += '<tr style="padding:0;margin:0;border:none;line-height:10px">';
			for(var x=0; x < colors[y].length; x++) {
				table += '<td style="padding:0;margin:0;border:none"><a style="border:1px solid #222; color: #' + colors[y][x] + '; background-color: #' + colors[y][x] + ';font-size: 10px;" title="' + colors[y][x] + '" href="javascript:'+this.editor+'.execCommand(\''+this.action+'\', \'#' + colors[y][x] + '\');PLXEDITOR.dialog.close(\'colorpicker\');">&nbsp;&nbsp;&nbsp;&nbsp;</a></td>';
			}
			table += '</tr>';
		}
		table += '</table>';
		return table;
	};
	return{create:create}
}();

PLXEDITOR.smilies=function(){
	var smilies = [
			["big_smile.png", "cool.png", "hmm.png", "icon_arrow.gif", "icon_eek.gif", "icon_exclaim.gif"],
			["icon_question.gif", "icon_redface.gif", "icon_twisted.gif", "lol.png", "mad.png", "neutral.png"],
			["roll.png", "sad.png", "smile.png", "tongue.png", "wink.png", "yikes.png"]];

	function create(editor, button, action, path){
		this.editor=editor;
		this.button=button;
		this.action=action;
		this.path = path;		
		if(E$('smilies')) return PLXEDITOR.dialog.close('smilies');
		this.displayPanel();
	}
	//------------
	create.prototype.displayPanel=function(){
		var elemDiv = document.createElement('div');
		elemDiv.id = 'smilies';
	    elemDiv.style.position = 'absolute';
		elemDiv.style.display = 'block';
		elemDiv.style.border = '#aaa 1px solid';
		var top = PLXEDITOR.dialog.getAbsoluteOffsetTop(E$(this.button)) + 20;
		var left = PLXEDITOR.dialog.getAbsoluteOffsetLeft(E$(this.button));
		elemDiv.style.top = top + 'px';
		elemDiv.style.left = left + 'px';
		elemDiv.innerHTML = this.panel();
		document.body.appendChild(elemDiv);
	},
	create.prototype.panel=function() {
		var table = '<table id="popup" border="0" cellspacing="1" cellpadding="0">';
		for(var y=0; y < smilies.length; y++) {
			table += '<tr>';
			for(var x=0; x < smilies[y].length; x++) {
				table += '<td><a href="javascript:'+this.editor+'.execCommand(\'InsertImage\', \''+this.path.editor+'plxeditor/smilies/' + smilies[y][x] + '\');PLXEDITOR.dialog.close(\'smilies\');"><img alt="" src="'+this.path.editor+'plxeditor/smilies/'+smilies[y][x]+'" /></a></td>';
			}
			table += '</tr>';
		}
		table += '</table>';
		return table;
	};
	return{create:create}
}();
