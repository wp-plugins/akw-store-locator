//akw-store-locator general section styles

document.getElementById('working').style.display = 'inline-block';
//Declare variables
var map;
var markers = [];
var infoWindow;
var locationSelect;
var pos;

//Map is loaded when this js file is called.
map = new google.maps.Map(document.getElementById("akwMap"), {
  center: new google.maps.LatLng(43.010045, -83.697824),
  zoom: 4,
  mapTypeId: 'roadmap',
  mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
});

//Instantiate info window
infoWindow = new google.maps.InfoWindow();

// Try HTML5 geolocation
if(navigator.geolocation)
{
    navigator.geolocation.getCurrentPosition(function(position) {
	pos = new google.maps.LatLng(position.coords.latitude,
                                       position.coords.longitude);
    
    console.log("init pos: "+pos);
    
    }, function() {
      handleNoGeolocation(true);
    });
}
else
{
    // Browser doesn't support Geolocation
    pos = '';
    handleNoGeolocation(false);
}

//Setting timeout because in firefox and chrome closing the geolocation share does not work
setTimeout(function(){ document.getElementById('working').style.display = 'none'; }, 5000);

//Function to search for loacation from address input
function searchLocations()
{
    var address = document.getElementById("addressInput").value;
    
    if(address != '')
    {
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({address: address}, function(results, status)
	{
	    if (status == google.maps.GeocoderStatus.OK)
	    {
		searchLocationsNear(results[0].geometry.location);
	    }
	    else
	    {
		alert(address + ' not found');
	    }
	});
    }
    else if(address == '' && (pos != '' && typeof pos != 'undefined'))
    {
	searchLocationsNear(pos);
    }
    else
    {
	alert('Address field is empty and Current location is not available');
    }
}

function handleNoGeolocation(errorFlag)
{
    console.log("init pos: empty");
    if (errorFlag)
    {
	var content = 'Error: The Geolocation service failed.';
    }
    else
    {
	var content = 'Error: Your browser doesn\'t support geolocation.';
    }
    alert(content);
}

//Function to Clear locations before new search
function clearLocations()
{
  infoWindow.close();
  for (var i = 0; i < markers.length; i++)
  {
    markers[i].setMap(null);
  }
 markers.length = 0;
 
 var option = document.createElement("option");
 option.value = "none";
 option.innerHTML = "See all results:";
}

//Function to search for locations near the address input and display the details in map and sidebar
function searchLocationsNear(center)
{
 clearLocations();

 var radius = document.getElementById('radiusSelect').value;
 var distanceType = document.getElementById('distanceType').value;
 var storeName = document.getElementById("nameInput").value;
 
 var searchUrl = akwstorelocatorobject.plugin_url+'/searchStores.php?lat=' + center.lat() + '&lng=' + center.lng() + '&radius=' + radius + '&distType=' + distanceType + '&storeName=' + storeName;
 downloadUrl(searchUrl, function(data) {
   var xml = parseXml(data);
   var markerNodes = xml.documentElement.getElementsByTagName("marker");
   var bounds = new google.maps.LatLngBounds();
   
   var sidebar = document.getElementById('akwLocationSidebar');
   sidebar.innerHTML = '';
   if (markerNodes.length == 0) {
     sidebar.innerHTML = 'No results found.';
     map.setCenter(new google.maps.LatLng(43.010045, -83.697824), 4);
     return;
   }
   
   for (var i = 0; i < markerNodes.length; i++) {
     var name = markerNodes[i].getAttribute("name");
     var address = markerNodes[i].getAttribute("address");
     var distance = parseFloat(markerNodes[i].getAttribute("distance"));
     var phone;
     if(markerNodes[i].getAttribute("phone") == '' || markerNodes[i].getAttribute("phone") == 'null')
     {
       phone = 'NA';
     }
     else
     {
      phone = markerNodes[i].getAttribute("phone");
     }
     
     var preferredStore = markerNodes[i].getAttribute("preferredStore");
     var customInfo = markerNodes[i].getAttribute("customInfo");
     
     var latlng = new google.maps.LatLng(
	  parseFloat(markerNodes[i].getAttribute("lat")),
	  parseFloat(markerNodes[i].getAttribute("lng")));

     createOption(name, distance, i);
     createMarker(latlng, name, address, phone, distance, distanceType, preferredStore, customInfo);
     bounds.extend(latlng);
  } 
  map.fitBounds(bounds);
  });
}

//Function to create marker
function createMarker(latlng, name, address, phone, distance, distType, preferredStore, customInfo) {
  var sidebar = document.getElementById('akwLocationSidebar');
  var html = '';
  if(preferredStore == 1)
  {
    html = "<span class='preferredStoreSpan'><strong>" + name + "</strong></span> <br/>" ;
  }
  else
  {
    html = "<strong>" + name + "</strong> <br/>" ;  
  }
  if(customInfo != '')
  {
    html += customInfo + '<br />';
  }
  html += address;
  var marker = new google.maps.Marker({
    map: map,
    position: latlng
  });
  google.maps.event.addListener(marker, 'click', function() {
    infoWindow.setContent(html);
    infoWindow.open(map, marker);
  });
  
  var sidebarEntry = createSidebarEntry(marker, name, address, phone, distance, distType, preferredStore, customInfo);
     sidebar.appendChild(sidebarEntry);
     
  markers.push(marker);
}

function createOption(name, distance, num) {
  var option = document.createElement("option");
  option.value = num;
  option.innerHTML = name + "(" + distance.toFixed(1) + ")";
  //locationSelect.appendChild(option);
}

function downloadUrl(url, callback) {
  var request = window.ActiveXObject ?
      new ActiveXObject('Microsoft.XMLHTTP') :
      new XMLHttpRequest;

  request.onreadystatechange = function() {
    if (request.readyState == 4) {
      request.onreadystatechange = doNothing;
      callback(request.responseText, request.status);
    }
  };

  request.open('GET', url, true);
  request.send(null);
}

function parseXml(str)
{
  if (window.ActiveXObject) {
    var doc = new ActiveXObject('Microsoft.XMLDOM');
    doc.loadXML(str);
    return doc;
  } else if (window.DOMParser) {
    return (new DOMParser).parseFromString(str, 'text/xml');
  }
}

function doNothing()
{
  
}

//Function to create sidebar entry
function createSidebarEntry(marker, name, address, phone, distance, distType, preferredStore, customInfo)
{
  var div = document.createElement('div');
  var html = '';
  
  if(preferredStore == 1)
  {
    if(!document.getElementsByClassName('preferredStoreSpan').length)
    {
      html += '<h3>Preferred Locations:</h3>';
    }
    html += '<span class="preferredStoreSpan"><strong>' + name + '</strong></span>';
  }
  else
  {
    if(!document.getElementsByClassName('nonPreferredStoreSpan').length)
    {
      html += '<h3>Locations:</h3>';
    }
    html += '<span class="nonPreferredStoreSpan"><strong>' + name + '</strong></span>';  
  }
  
  html += ' (' + distance.toFixed(1) + ' '+distType+')<br/>';
  if(customInfo != '')
  {
    html +=  customInfo + '<br />';
  }
  html += address + ', Ph: ' + phone;
  div.innerHTML = html;
  div.style.cursor = 'pointer';
  div.style.marginBottom = '5px';
  google.maps.event.addDomListener(div, 'click', function() {
    google.maps.event.trigger(marker, 'click');
  });
  google.maps.event.addDomListener(div, 'mouseover', function() {
    div.style.backgroundColor = '#ffffff';
  });
  google.maps.event.addDomListener(div, 'mouseout', function() {
    div.style.backgroundColor = '#F4F3EF';
  });
  return div;
}