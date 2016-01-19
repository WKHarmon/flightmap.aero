  var map = [];
  var mm = [];
  var airportMarkers = [];
  var userStates = [];
  var sitenumber = '';
  var infowindow = new google.maps.InfoWindow();
  
  google.maps.event.addListener(infowindow, 'domready', function() {
  	$(".colorbox").colorbox();
  	$('#toggle').click(function() {
		if (airportMarkers[sitenumber]['status'] == 0) {
			infowindow.close();
			airportMarkers[sitenumber]['status'] = 1;
			mm.removeMarker(airportMarkers[sitenumber]);
			airportMarkers[sitenumber].setIcon('images/mm_20_blue.png');
			mm.addMarker(airportMarkers[sitenumber], 0);
		} else {
			infowindow.close();
			airportMarkers[sitenumber]['status'] = 0;
			mm.removeMarker(airportMarkers[sitenumber]);
			airportMarkers[sitenumber].setIcon('images/mm_20_red.png');
			mm.addMarker(airportMarkers[sitenumber], 7);
		}
		mm.refresh();
		var url = "/ajax.php?action=toggle&sitenumber="+encodeURIComponent(sitenumber)+"&enabled="+airportMarkers[sitenumber]['status'];
		$.ajax({
			url: url,
			type: "GET"
		});
		return false;
	});
  });
  
  function initialize() {
    var myOptions = {
      zoom: 7,
      center: myLatlng,
      streetViewControl: false,
      mapTypeId: google.maps.MapTypeId.TERRAIN
    }
    
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    mm = new MarkerManager(map);
    
    google.maps.event.addListener(mm, 'loaded', function() {
    	var stateDiv = document.getElementById('states');
    	var keyDiv = document.getElementById('key');
    	var controlDiv = document.getElementById('control');
    	map.controls[google.maps.ControlPosition.LEFT_BOTTOM].push(stateDiv);
    	map.controls[google.maps.ControlPosition.RIGHT_TOP].push(keyDiv);
    	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
    	
    	$(".ajax").colorbox({
			scrolling: 'no',
			overlayClose: false
		});
    	
	    addFlights(myFlights);
	    
	    google.maps.event.addListener(map, 'idle', function(event) {
		    if (map.getZoom() > 6) {
			    var latLngBounds = map.getBounds();
		 	 	var sw = latLngBounds.getSouthWest();
		  		var ne = latLngBounds.getNorthEast();
		  		var minLat = sw.lat();
		  		var maxLat = ne.lat();
		  		var minLong = sw.lng();
		  		var maxLong = ne.lng();
		  		var url = "/ajax.php?action=airports&minLat="+minLat+"&maxLat="+maxLat+"&minLong="+minLong+"&maxLong="+maxLong;
		  		$.ajax({
		  			url: url,
		  			type: "GET",
		  			dataType: "json",
		  			success: function(airports){
		  				addFlights(airports);
		  			}
		  		});
		  	}
	    });
	});
  }
  
  function addFlights(flightArray) {
      for (var i in flightArray) {
      	if (typeof airportMarkers[flightArray[i]['sitenumber']] != null) {
	      	if (flightArray[i]['status'] == 1) {
	      		var icon = 'images/mm_20_blue.png';
	      		if (typeof userStates[flightArray[i]['state']] == "undefined") {
	      			userStates[flightArray[i]['state']] = 1;
	      		} else {
		      		userStates[flightArray[i]['state']]++;
		      	}
	      	} else {
				var icon = 'images/mm_20_red.png';
			}
			var googleLatLong = new google.maps.LatLng(flightArray[i]['lat'],flightArray[i]['long']);
			var marker = new google.maps.Marker({
				position: googleLatLong,
				title: flightArray[i]['identifier'],
				icon: icon
			});
			marker['sitenumber'] = flightArray[i]['id'];
			marker['status'] = flightArray[i]['status'];
			marker['lat'] = flightArray[i]['lat'];
			marker['long'] = flightArray[i]['long'];
		 	
		 	google.maps.event.addListener(marker, 'click', function() {
		 		var url = "/ajax.php?action=airport_ajax&airportid="+this['sitenumber'];
		 		var marker = this;
		 		sitenumber = this['sitenumber'];
		 		infowindow.setContent('<div class="content" style="text-align: center"><img src="images/loading_white.gif" alt="Loading ..." title="Loading ..." /></div>');
		 		infowindow.open(map,marker);
		 		$.ajax({
		 				url: url,
		 				dataType: 'html',
		 				success: function(data) {
		 					infowindow.setContent(data);
		 					infowindow.open(map,marker);
		 				},
		 				error: function(xml, error, status) {
		 					infowindow.setContent(status+": "+error);
		 					infowindow.open(map,marker);
		 				}
		 		});
		 	});
			
			airportMarkers[marker['sitenumber']] = marker;
			
			if (marker['status'] == 1) {
	  			mm.addMarker(marker, 0);
	      	} else {
	      		mm.addMarker(marker, 7);
	      	}
      	}
    }
  }