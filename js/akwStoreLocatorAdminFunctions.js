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
        }
        else
        {
          alert("Geocode was not successful for the following reason: " + status);
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
       //call to getCoords function
        getCoords(lines);
      }
      else
      {
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
        fullAddress = fields[1]+', '+fields[2]+', '+fields[3]+', '+fields[4];
        
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
                    alert("Geocode was not successful for the following reason: " + status);
                }
            });
    }
}

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
            alert('Coordinates added!');
        }
        else
        {
            alert(xhr.responseText);
        }
      }
    }
  
    xhr.send('cmd=saveAddress&ID='+storeid+'&lat='+latitude+'&long='+longitude);
}