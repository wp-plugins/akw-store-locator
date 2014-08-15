//akw-store-locator admim javascript functions

//Function to move and set the marker at the address
function moveToAddress()
{
    var map1;
    var geocoder1;
    var marker1;
    var Lat1;
    var Lng1;
    Lat1 = document.getElementById('latitude').value;
    Lng1 = document.getElementById('longitude').value;
    var mapOptions1 = {
      zoom: 14,
      center: new google.maps.LatLng(Lat1, Lng1),
      mapTypeId: google.maps.MapTypeId.ROADMAP //google.maps.MapType.G_NORMAL_MAP//
    }
    
    map1 = new google.maps.Map(document.getElementById('akwAdminMap'), mapOptions1);
    marker1 = new google.maps.Marker(
                       {map: map1,
                        position: new google.maps.LatLng(Lat1, Lng1)
                       });
    
    geocoder1 = new google.maps.Geocoder(); //GClientGeocoder(); //
    
  //var address1 = document.getElementById('address').value;
  var address1 = document.getElementById('street').value + document.getElementById('city').value
    + document.getElementById('province').value + document.getElementById('country').value;
  address1 = address1.replace("\n", ' ');
  geocoder1.geocode(
    {'address': address1},
      function(results, status)
      {
        if (status == google.maps.GeocoderStatus.OK)
        {
          document.getElementById('latitude').value = results[0].geometry.location.lat();
          document.getElementById('longitude').value = results[0].geometry.location.lng();
          map1.setCenter(results[0].geometry.location);
          if(marker1)
          {
            marker1.setMap(null);
          }
          
          marker1 = new google.maps.Marker(
                             {map: map1,
                              position: results[0].geometry.location
                             });
          
          marker1.setDraggable(true);
          google.maps.event.addListener(marker1, "dragend", function(a){
            var point = marker1.getPosition();
            map1.panTo(point);
            //console.log(a);
            document.getElementById('latitude').value = a.latLng.lat();
            document.getElementById('longitude').value = a.latLng.lng();
            
            geocoder1.geocode({latLng: a.latLng}, function(responses){
                if(responses && responses.length > 0){
                    console.log('formated address: '+responses[0].formatted_address);
                    var formattedAddress = responses[0].formatted_address;
                    var addressArray = formattedAddress.split(",");
                    var provPostal = addressArray[2].trim();
                    var provPostalArray = provPostal.split(" ");
                    var postal;
                    
                    if(typeof provPostalArray[2] != 'undefined' && provPostalArray[2] != '')
                    {
                        postal = provPostalArray[1] + ' ' + provPostalArray[2];
                    }
                    else
                    {
                        postal = provPostalArray[1];
                    }
                    document.getElementById('street').value = addressArray[0].trim();
                    document.getElementById('city').value = addressArray[1].trim();
                    document.getElementById('province').value = provPostalArray[0].trim();
                    document.getElementById('country').value = addressArray[3].trim();
                    document.getElementById('postalCode').value = postal;
                }
                else
                {
                    alert("Unable to find the address for the location.");
                }
            });
          });
        }
        else
        {
          alert("Request not successful for the following reason: " + status);
        }
      });
}

//Function for to create AJAX call to server
function createXHR()
{
  if (typeof XMLHttpRequest != "undefined")
  {
    return new XMLHttpRequest();
  }
  else
  {
    var aVersions = ["MSXML2.XMLHttp.6.0", "MSXML2.XMLHttp.3.0"];
    for (var z=0; z < aVersions.length; z++)
    {
      try
      {
        var oXHR = new ActiveXObject(aVersions[z]);
        return oXHR;
      }
      catch (oError)
      {
      }
    }
  }
  
  throw new Error("XMLHttpRequest or XMLHttp could not be created");
}

//Function to get addresses from server
function getAddresses()
{
  var xhr = createXHR();
  xhr.open("POST", akwstorelocatoradminobject.plugin_url+"/storeFunctions.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function()
  {
    if (xhr.readyState == 4)
    {
      var lines = xhr.responseText.split("\n");
      if(lines[0] == 'OKAY')
      {
        document.getElementById('working').style.display = 'block';
       //call to getCoords function
        getCoords(lines);
      }
      else
      {
        document.getElementById('working').style.display = 'none';
        alert(xhr.responseText);
      }
    }
  }

  xhr.send('cmd=getAddress');
}

//Function to get the location co-ordinates for the addresses
function getCoords(lines)
{
    var geocoder1;
    var lat;
    var longi;
    var fields;
    var storeID;
    var fullAddress;
    
    for(var z=1;z<lines.length;z++)
    {
        fields = lines[z].split('|');
       
        storeID = fields[0];
        fullAddress = fields[1]+', '+fields[2]+', '+fields[3]+', '+fields[4]+', '+fields[5];
        
        //setInterval(function(){ getLatLong(storeID, fullAddress) }, 1000);
        
        getLatLong(storeID, fullAddress);
    }
}

function getLatLong(storeID, fullAddress)
{
    geocoder1 = new google.maps.Geocoder();
    geocoder1.geocode(
        {
            'address': fullAddress
        },
        function(results, status)
        {
            if (status == google.maps.GeocoderStatus.OK)
            {
                lat = results[0].geometry.location.lat();
                longi = results[0].geometry.location.lng();
                //Call to funtion saveCoords       
                saveCoords(storeID, lat, longi);
            }
            else
            {
                var msg = '';
                console.log("Geocode was not successful for the following reason: " + status);
                if(status == 'ZERO_RESULTS')
                {
                    msg += 'Unable to find the location for the address: '+fullAddress;
                }
                else if(status == 'OVER_QUERY_LIMIT')
                {
                    msg += 'Reached Google Geocode query limit. Please try later.';
                }
                else
                {
                    msg += status;
                }
                document.getElementById('working').style.display = 'none';
                alert("Error: " + msg);
                return;
            }
        });
}
var count = 0;
//Function to save the coords for the address
function saveCoords(storeid, latitude, longitude)
{
    var xhr = createXHR();
    xhr.open("POST", akwstorelocatoradminobject.plugin_url+"/storeFunctions.php", true); 
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function()
    {
      if (xhr.readyState == 4)
      {
        var lines = xhr.responseText.split("\n");
        if(lines[0] == 'OKAY')
        {
            //Alert the user when save successfull
            //alert('Coordinates added!');
            count++;
            console.log('Coordinates added! = count: '+count);
            setTimeout(function(){ getAddresses() }, 3000);
        }
        else
        {
            console.log(xhr.responseText);
            //alert(xhr.responseText);
        }
      }
    }
  
    xhr.send('cmd=saveAddress&ID='+storeid+'&lat='+latitude+'&long='+longitude);
}

//Function to display map when add new store is selected
function initBlankMap()
{
    var map1;
    var geocoder1;
    var marker1;
    var Lat1;
    var Lng1;
    Lat1 = 43.010045;
    Lng1 = -83.697824;
    var mapOptions1 = {
      zoom: 2,
      center: new google.maps.LatLng(Lat1, Lng1),
      mapTypeId: google.maps.MapTypeId.ROADMAP //google.maps.MapType.G_NORMAL_MAP//
    }
    
    map1 = new google.maps.Map(document.getElementById('akwAdminMap'), mapOptions1);
    marker1 = new google.maps.Marker(
                       {map: map1,
                        position: new google.maps.LatLng(Lat1, Lng1)
                       });
    
    geocoder1 = new google.maps.Geocoder(); //GClientGeocoder(); //
    
    marker1.setDraggable(true);
    google.maps.event.addListener(marker1, "dragend", function(a){
      var point = marker1.getPosition();
      map1.panTo(point);
      //console.log(a);
      document.getElementById('latitude').value = a.latLng.lat();
      document.getElementById('longitude').value = a.latLng.lng();
      
      geocoder1.geocode({latLng: a.latLng}, function(responses){
        if(responses && responses.length > 0){
            console.log('formated address: '+responses[0].formatted_address);
            var formattedAddress = responses[0].formatted_address;
            var addressArray = formattedAddress.split(",");
            var provPostal = addressArray[2].trim();
            var provPostalArray = provPostal.split(" ");
            var postal;
            
            if(typeof provPostalArray[2] != 'undefined' && provPostalArray[2] != '')
            {
                postal = provPostalArray[1] + ' ' + provPostalArray[2];
            }
            else
            {
                postal = provPostalArray[1];
            }
            document.getElementById('street').value = addressArray[0].trim();
            document.getElementById('city').value = addressArray[1].trim();
            document.getElementById('province').value = provPostalArray[0].trim();
            document.getElementById('country').value = addressArray[3].trim();
            document.getElementById('postalCode').value = postal;
        }
        else
        {
            alert("Unable to find the address for the location.");
        }
      });
    });
}

//Function to check for add store input fields
function checkRequiredFields()
{
    var msg = '';
    var nameField = document.getElementById('name').value;
    var streetField = document.getElementById('street').value;
    var cityField = document.getElementById('city').value;
    var provinceField = document.getElementById('province').value;
    var countryField = document.getElementById('country').value;
    
    if(nameField.trim() == '')
    {
        msg += 'Store name is required';
    }
    
    if(streetField.trim() == '' && cityField.trim() == '' && provinceField.trim() == '' && countryField.trim() == '')
    {
        msg += '\nStore should have atleast one of Street, city, province/state or country field filled.';   
    }
    if(msg.trim() != '')
    {
        alert('Following errors occured:\n'+ msg);
        return false;
    }
}