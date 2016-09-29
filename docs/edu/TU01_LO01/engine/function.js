// funzione di aperture corso [1]Fullscreen JS - [2]Fullscreen con ActiveX - [3]Normale
switch_full = 1;

function apri_corso(LOF) {
	//width=900;
	width = 1024;
	//height=675;
	height = 714;

	scrH = screen.availHeight;
	scrW = screen.availWidth;
	
	margH = (scrH-714)/4;
	margW = (scrW-1024)/2;

	if (LOF) {
		if (switch_full==1) {
			window.open("./"+LOF+"/object.htm?"+LOF,"wbt","fullscreen=1");
		} else if (switch_full==2 || switch_full==3) {
			window.open("./"+LOF+"/object.htm?"+LOF,"","width="+width+",height="+height+",left="+margW+",top="+margH+",resize=1,fullscreen=0");
		}
	}	else {
		if (switch_full==1) {
			window.open("./"+LO+"/object.htm?"+LO,"wbt","fullscreen=1");
		} else if (switch_full==2 || switch_full==3) {
			window.open("./"+LO+"/object.htm?"+LO,"","width="+width+",height="+height+",left="+margW+",top="+margH+",resize=1,fullscreen=0");
		}

	}
	//massimizza();
}
function apriDaIndice(linkLO){
	//width=900;
	width = 1024;
	//height=675;
	height = 714;

	scrH = screen.availHeight;
	scrW = screen.availWidth;
	
	margH = (scrH-714)/4;
	margW = (scrW-1024)/2;
	//if (scrW==1024) {
		//window.open(linkLO,"wbt","fullscreen=1");
		if (switch_full==1) {
			window.open(linkLO,"wbt","fullscreen=1");
		} else if (switch_full==2 || switch_full==3) {
			window.open(linkLO,"wbt","width="+width+",height="+height+",left="+margW+",top="+margH+",resize=1,fullscreen=0");
		}
	/*} else {
		window.open(linkLO,"","width=1024,height=714,left=0,top=0");
	}*/
}
//Copia in memoria
function copiaInMemoria(txt) {
	window.clipboardData.setData('text',txt);
}
//funzione per minimizzare la finestra
var minimizzato = false;
var minit;
function miniIE() {
	clearInterval(minit);
	window.top.moveBy(5000,0);
	window.top.blur();
	minimizzato = true;
	if (window.opener) {	
		window.opener.focus();
	}
}
function minimizza() {
	window.resizeTo(100,100);
	if (BrowserDetect.browser=="Explorer") {
		if (switch_full==2) {
			var obj = new ActiveXObject("Wscript.shell");
			obj.SendKeys("{F11}");
		}
		minit = setInterval(miniIE, 100);
	} else {
		window.top.innerWidth = 100;
		window.top.innerHeight = 100;
		window.top.screenX = screen.width;
		window.top.screenY = screen.height;
		alwaysLowered = true;
		if (window.opener) {	
			window.opener.focus();
		}
	}
}
function massimizza() {
	if (BrowserDetect.browser=="Explorer") {
		if (switch_full==2) {
			var obj = new ActiveXObject("Wscript.shell");
			obj.SendKeys("{F11}");
		}
		if (minimizzato){
			window.top.moveBy(-5000,0);
			minimizzato = false;

			//width=900;
			width = 1024;
			//height=675;
			height = 714;
		
			scrH = screen.availHeight;
			scrW = screen.availWidth;
			
			margH = (scrH-714)/4;
			margW = (scrW-1024)/2;
			
			window.resizeTo(width,height);
			window.moveTo(margW,margH);
		}
	} else {
		//window.innerWidth = screen.width;
		//window.innerHeight = screen.height;
		scrH = screen.availHeight;
		scrW = screen.availWidth;
		margH = (scrH-714)/4;
		margW = (scrW-1024)/2;
		
		window.top.innerWidth = 1024;
		window.top.innerHeight = 714;
		window.top.screenX = margW;
		window.top.screenY = margH;
		alwaysLowered = false;
	}
}
function approfondimento(f) {
	window.open("approfondimenti/"+f+".htm", "approfondimento", "width=800, height=600, resizable=1, scrollbars=1,left=0,top=0");
}
//function stampa(param,mod,uni,sch,fil) {
function stampa(param) {
	/*testiStampa = param;
	while (testiStampa.indexOf("&apos;")>0) {
		testiStampa = testiStampa.replace("&apos;", "'");
	}

	myPage = "../engine/print.htm";*/
	window.open(param,"Print","scrollbars=1,resizable=1,left=0,top=0");

}
function stampaOLD(param) {
	var appro = false;
	var note = false;
	
	myparam = new Array();
	myparam = param.split("#");	
	var navigazione = myparam[0];
	var mylo = myparam[1];
	//Approfondimento
	if (navigazione.indexOf("_E")>0){
		appro = true;
		navigazione = navigazione.substring(0,navigazione.indexOf("_E"));
	} else if (navigazione.indexOf("_N")>0){ //Note
		note = true;
		navigazione = navigazione.substring(0,navigazione.indexOf("_N"));
	}
	if (!appro && !note){	
		//Inizializza l'oggetto XML
		/*xmlObj = new ActiveXObject("Microsoft.XMLDOM");
		xmlObj.onreadystatechange = IEGo;
		xmlObj.load("../"+mylo+"/contents/tutor/"+navigazione+".xml");*/
		
		if (document.implementation && document.implementation.createDocument)
		{
			xmlObj = document.implementation.createDocument("","",null)
			xmlObj.load("../"+mylo+"/contents/tutor/"+navigazione+".xml");
			xmlObj.onload=dati();
		}
		else if (window.ActiveXObject)
		{
			/*xmlObj = new ActiveXObject("Microsoft.XMLDOM")
			xmlObj.onreadystatechange = IEGo();
			xmlObj.load("../"+mylo+"/contents/tutor/"+navigazione+".xml");
			
			xmlObj = new ActiveXObject("Microsoft.XMLDOM")
			xmlObj.async = false;
			xmlObj.load("../"+mylo+"/contents/tutor/"+navigazione+".xml");
			IEGo();*/
			xmlObj = new ActiveXObject("Microsoft.XMLDOM");
			xmlObj.onreadystatechange = IEGo;
			xmlObj.load("../"+mylo+"/contents/tutor/"+navigazione+".xml");
		}
	} else {
		dati();
	}
	function dati(){
		//Ricava il testo dall'xml audio della schermata
		/*if (!appro && !note){
			qryTesto = xmlObj.selectNodes("//audio");
			
			Testo = "";
			for (i=0; i<qryTesto.length; i++) {
				Testo += qryTesto[i].text+"<br>";
			}
		}*/

		//Titoli modulo, unità, schermata
		alert(document.getElementById("chiudiFin").getVariable("/:Browser"));
		Modulo = document.getElementById("chiudiFin").getVariable("_level10.myTitoloM");
		Unita = document.getElementById("chiudiFin").getVariable("_level10.myTitoloU");
		
		if (!appro && !note){
			Schermata = document.getElementById("chiudiFin").getVariable("_level10.myTitoloS");
			Rollover = document.getElementById("chiudiFin").getVariable("_level5.rollover");
		}
		//Testi Approfondimento
		if (appro){
			Schermata = document.getElementById("chiudiFin").getVariable("_level14.titolo") + " - Approfondimento";
			Testo = document.getElementById("chiudiFin").getVariable("_level14.testo");
		} else if (note){//Testi Note
			Schermata = "Appunti";
			Testo = document.getElementById("chiudiFin").getVariable("_level72.contenuto");
			if (Testo == "" || document.getElementById("chiudiFin").getVariable("_level72.archivio")){
				Schermata = "Appunti - Archivio";
				Testo = document.getElementById("chiudiFin").getVariable("_level72.archivio");
			}
			Testo = Testo.replace("SIZE","__SIZE__");
		}
		//File swf
		FileSwf = document.getElementById("chiudiFin").getVariable("_level10.fileSwf");

		if (!appro && !note){
			window.open("../engine/print.htm","Stampa","width=750, height=580, scrollbars=1,resizable=1,left=0,top=0");
		} else if(appro) {
			//window.open("printA.htm","Stampa","scrollbars=1,resizable=1,left=0,top=0");
		} else if (note){
			//window.open("printN.htm","4a","scrollbars=1,resizable=1,left=0,top=0");
		}
	}
	/*function IEGo(){
		if (xmlObj.readyState == 4)
			try{
				dati();
			}catch(e){
		}
	}*/
	function IEGo() {
	 if (xmlObj.readyState == 4)
	   dati();
	}
}

function stampaE(param) {
	if (param.length==1) {
		myPage = "../engine/print" + param + ".pdf";
	} else {
		myPage = "../engine/printE.htm";
		testiAppro = param;	
	}
	window.open(myPage,"Print","scrollbars=1,resizable=1,left=0,top=0");
}

function passaLO() {
	//var url = top.document.location.href;
	//var LOini = url.indexOf("?")+1;
	//var LO = url.substring(LOini, url.length);
	
	//var LO = idLezione();
	document.chiudiFin.setVariable("_root.LearningObject",LO);
}

function esercitazione(g){
	if (g == undefined) {
		g = top.document.getElementById("wbt").geogebraFile;
	}
	top.document.getElementById("geogebra").src = g+"E.htm";
	
	cols = String(top.document.getElementById("wbt").rows).split(",");
	timegeo = setTimeout("apriGeogebra("+cols[0]+", "+cols[1]+")",1);
	top.document.getElementById("wbt").geogebraFile = g;
}
function soluzione(){
	top.document.getElementById("geogebra").src = top.document.getElementById("wbt").geogebraFile+"S.htm";
}
function apriGeogebra(val0, val1) {
	if (val0>60) {
		clearTimeout(timegeo);
		val0 = Number(val0)-10;
		val1 = Number(val1)+10;
		//top.document.getElementById("wbt").rows = val0+","+val1; // Apertura in Animazione
		top.document.getElementById("wbt").rows = "60,540"; // Apertura in Sovrapposizione
		if(val0>60){
			timegeo = setTimeout("apriGeogebra("+val0+","+val1+")",1);
		}
	} else {
		clearTimeout(timegeo);
	}
}
function chiudiGeogebra(val0, val1) {
	if (val0<600) {
		clearTimeout(timegeo);
		val0 = Number(val0)+10;
		val1 = Number(val1)-10;
		top.document.getElementById("wbt").rows = val0+","+val1;
		if(val0<600){
			timegeo = setTimeout("chiudiGeogebra("+val0+","+val1+")",1);
		}
	} else {
		clearTimeout(timegeo);
		top.document.getElementById("geogebra").src = "void.htm";
	}
}
function writeFlashContent(LO){
	/*if (BrowserDetect.browser=="Firefox") {
		mystart = "startFF.swf";
	} else {*/
		mystart = "start.swf";
	//}

	document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,0,0" id="chiudiFin" width="100%" height="100%" align="middle">');
	document.write('<param name="allowScriptAccess" value="sameDomain" />');
	document.write('<param name="SCALE" value="exactfit" />');
	document.write(' <param name=quality value="high" />');
	document.write(' <param name=swLiveConnect value="true" />');
	document.write('<param name="allowFullScreen" value="true" />');
	document.write('<param name=mayscript value="mayscript" />');
	document.write('<param name="movie" value="../engine/'+mystart+'?myLO_VAR=');
	document.write(LO);
	document.write('&show_debug='+show_debug+'" /><param name="quality" value="high" /><param name="bgcolor" value="#000000" /><embed src="../engine/'+mystart+'?myLO_VAR=');
	document.write(LO);
	document.write('&show_debug='+show_debug+'" quality="high" bgcolor="#000000" width="100%" height="100%" name="chiudiFin" swliveconnect="true" mayscript="mayscript" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />');
	document.write('</object>');
}
function splash_DoFSCommand(command, args){ 
	if ( command == "apri_corso" ){ 		
		apri_corso(args);
	}
	if ( command == "apriDaIndice" ){ 		
		apriDaIndice(args);
	}
	if ( command == "prerequisiti" ){ 		
		window.open("engine/prerequisiti.pdf","prerequisiti", "width=800,height=600,resizable=1,scrollbars=1");
	}
}

/*
navigator.appCodeName = Mozilla
navigator.appName = Microsoft Internet Explorer
navigator.appMinorVersion = ;SP1;Q833989;
navigator.cpuClass = x86
navigator.platform = Win32
navigator.plugins = 
navigator.opsProfile = 
navigator.userProfile = 
navigator.systemLanguage = it
navigator.userLanguage = it
navigator.appVersion = 4.0 (compatible; MSIE 6.0; Windows NT 5.0; PCM_06g; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.1)
navigator.userAgent = Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; PCM_06g; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.1)
navigator.onLine = true
navigator.cookieEnabled = true
navigator.mimeTypes = 
*/

var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
BrowserDetect.init();

function setBrowser() {
	if (document.getElementById("splash")) {
		document.getElementById("splash").setVariable("_root.browser",BrowserDetect.browser);
	} else if (document.getElementById("chiudiFin")) {
		document.getElementById("chiudiFin").setVariable("_root.browser",BrowserDetect.browser);
	}
}
chiusuraDaBrowser = true;
function confermaChiusura() {
	if(chiusuraDaBrowser) {
 		event.returnValue = "Confermando la chiusura si uscirà dall'unità didattica.";
		//window.opener.document.location.href = window.opener.document.location.href+"?n=1";
	}
}