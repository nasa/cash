 /* function loadFile(arg1) {
    var xmlhttp;
    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp=new XMLHttpRequest();
    }
    else {// code for IE6, IE5
      xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function(){
      if (xmlhttp.readyState==4 && xmlhttp.status==200) {
        document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
      }
    }
    xmlhttp.open("GET",arg1,true);
    xmlhttp.send();
  }

  function loadHTML(url) {
    var temp = "loadURL.php?url="+url;
    $("#myDiv").load(temp);
  }

  function parseLink(arg){
    if ((arg.substring(0,5) == "http")||(arg.substring(0,4) == "www")) {
      //forward to loadHTML function
      loadHTML(arg);
      if (arg.substring(0,4) == "www"){
        //prepend http://
        var temp = "http://" + arg;
        loadHTML(temp); 
      }
    }else{
      //forward to loadFile function
      loadHTML(arg); 
    }
  }

  function change(url){
  	var len = url.length;
    var ext = url.substring(len-3,len);
    //document.getElementById('myDiv').src = "about:blank";
    remove_download_pdf(); //removes download pdf link
    //if (!(ext == "xml")) {
  	  if (ext == "pdf") {
        show_download_pdf(url);
      }
        document.getElementById('myDiv').src = url;
   // }else{
    //  document.getElementById('myDiv').src = url;//"resources/scripts/loadURL.php?url="+url;
			$.ajax({
				type: 'GET',
				url: 'resources/scripts/loadURL.php',
				data: {'url': url},
				success: function(msg){
					if (msg) {
						$('#myDiv').contents().find('body').html(msg);
					}else{
						return;
					}
				}
			}); 
    //}
	}
*/
function linkClicked(link) {
	$('.menu li a').removeClass('linkActive');
  link.addClass('linkActive');
}

function open_in_new_tab(url){
  var win=window.open(url, '_blank');
  win.focus();
}

function show_download_pdf(url) {
  $.ajax({
    type: 'GET',
    url: '#',
    data: {'url': url},
    success: function(){
      add_download_pdf(url);
    }
  });
}

function add_download_pdf(url) {
  var link = "<a href=\""+url+"\"class=\"pdf\" download=\""+url+"\">Download PDF</a>";
  $('.centerdiv_footer').append("<div class=\"pdf_link\" style=\"text-align: right\">"+link+"</div>");
}

function remove_download_pdf(){
  $('.pdf_link').remove();
}
