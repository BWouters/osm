<?php

	$key = $_REQUEST['code'];
	if(isset($key)){
		$client_id ="";
		$client_secret = "";
		$redirect_uri = "http://dump.visionsandviews.net/osm/"; //In this example the redirect_uri is just pointing back to this file
	
		$uri = file_get_contents("https://foursquare.com/oauth2/access_token?client_id=".$client_id."&client_secret=".$client_secret."&grant_type=authorization_code&redirect_uri=".$redirect_uri."&code=".$key, 
		    true);
	
		$obj = json_decode($uri);
	
		$usertoken = $obj->access_token; 
	    //If you want to show "Connected App" check-in replies for this user you will need to save this access token  
	    //in a database with the user's foursquare id so you get access it later 
		
		if(isset($_POST['limit'])){
			$limit = $_POST['limit'];
			
		}else{
			$limit = 20;
		}
		$uri = file_get_contents("https://api.foursquare.com/v2/users/self/checkins?limit=".$limit."&oauth_token=".$obj->access_token,
		  true); 
		 
	
		$obj = json_decode($uri);
	
		// Pull the info you want to save about the user https://developer.foursquare.com/docs/responses/user
		// Examples
		$checkinCount = $obj->response->checkins->count;
		/*$firstname = $obj->response->user->firstName;
		$lastname = $obj->response->user->lastName;
			
			// Not all fields available are actually present in the user object..	
		    if(isset($obj->response->user->contact->phone))	
				$phone = $obj->response->user->contact->phone;
			else 	
		    	$phone="";
	
			
		    if(isset($obj->response->user->contact->email))	
				$email = $obj->response->user->contact->email;
			else 	
		    	$email="";
		 */
		
		$checkins = $obj->response->checkins->items;
	}
	
?>





<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />

		<!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame
		Remove this if you use the .htaccess -->
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

		<title>index</title>
		<meta name="description" content="" />
		<meta name="author" content="Berend" />

		<meta name="viewport" content="width=device-width; initial-scale=1.0" />

		<!-- Replace favicon.ico & apple-touch-icon.png in the root of your domain and delete these references -->
		<link rel="shortcut icon" href="/favicon.ico" />
		<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
		<!-- jQuery (+UI) -->
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
 
		<!-- Leaflet -->
		 <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.css" />
		 <!--[if lte IE 8]>
		     <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.ie.css" />
		 <![endif]-->
		 <style type="text/css">
		 	#map { height: 500px; }
		 	.leaflet-div-icon {
				background: transparent;
				border: none;
			}
			 
			.leaflet-marker-icon .number{
				position: relative;
				top: -41px;
				font-size: 12px;
				width: 25px;
				text-align: center;
			}
		 </style>
		 
	</head>

	<body>
		<div>
			<header>
				<h1>Last 20 check-ins on Foursquare</h1>
				<p>This is a experiment with <a href='http://leafletjs.com'>Leafletjs</a> and the Foursquare API. Just connect with Foursquare and you'll see your last 20 check-ins on Foursquare in a fancy map. I don't do anything with your data, it's just a try-out.</p>
				<p>Edit: added a slider to show up to 250 check-ins.</p>
			</header>
			<a href="https://foursquare.com/oauth2/authenticate?client_id=ZWFXMP12CRJWVBE3EQYTP233IJGG53JPC0NQ3VXFL55OO2NZ&response_type=code&redirect_uri=http://dump.visionsandviews.net/osm/">
			  <img alt="Foursquare" src="https://playfoursquare.s3.amazonaws.com/press/logo/connect-blue.png">
			  </a>
			<p><?php echo "Total check-ins: ".$checkinCount; ?></p>
			<p>Amount of check-ins: </p>
			<form method="post" action="#">
				<input type="text" name="limit" id="checkins" style="border: 0; color: #f6931f; font-weight: bold;" />
				<input type="submit" value="Update limit" />
			</form>
			
			<div id="slider"></div>
			<div>
				<div id="map"></div>
			</div>

			<footer>
				<p>
					&copy; Copyright  by Visions and Views
				</p>
			</footer>
		</div>
	</body>
	
	<!-- jQuery (+UI) -->
	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  	<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
	
	<!-- Leaflet JS -->
	<script type="text/javascript" src="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.js"></script>
	<script type="text/javascript">
		var map = L.map('map').setView([51.505, -0.09], 13);
		L.tileLayer('http://{s}.tile.cloudmade.com/0a30f1a1f7ce4f4cb1acb5c09f7c430a/96264/256/{z}/{x}/{y}.png', {
		    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>[…]',
		    maxZoom: 18
		}).addTo(map)
		var bounds = new L.LatLngBounds();
		var checkinPoints = new Array();
		L.NumberedDivIcon = L.Icon.extend({
			options: {
		    // EDIT THIS TO POINT TO THE FILE AT http://www.charliecroom.com/marker_hole.png (or your own marker)
		    iconUrl: 'http://www.charliecroom.com/marker_hole.png',
		    number: '',
		    shadowUrl: null,
		    iconSize: new L.Point(25, 41),
				iconAnchor: new L.Point(13, 41),
				popupAnchor: new L.Point(0, -33),
				/*
				iconAnchor: (Point)
				popupAnchor: (Point)
				*/
				className: 'leaflet-div-icon'
			},
		 
			createIcon: function () {
				var div = document.createElement('div');
				var img = this._createImg(this.options['iconUrl']);
				var numdiv = document.createElement('div');
				numdiv.setAttribute ( "class", "number" );
				numdiv.innerHTML = this.options['number'] || '';
				div.appendChild ( img );
				div.appendChild ( numdiv );
				this._setIconStyles(div, 'icon');
				return div;
			},
		 
			//you could change this to add a shadow like in the normal marker if you really wanted
			createShadow: function () {
				return null;
			}
		});
		var counter = <?php echo $limit; ?>+1;
		<?php
		foreach($checkins as $checkin){
			?>
			counter--;
			L.marker([<?php echo $checkin->venue->location->lat.",".$checkin->venue->location->lng; ?>], {icon:	new L.NumberedDivIcon({number: counter})}).bindPopup("<p><?php echo $checkin->venue->name; if(isset($checkin->shout)){ echo "<br />".$checkin->shout."</p>"; } ?>").addTo(map);
			bounds.extend([<?php echo $checkin->venue->location->lat.",".$checkin->venue->location->lng; ?>]);
			checkinPoints.push([<?php echo $checkin->venue->location->lat.",".$checkin->venue->location->lng; ?>]);
			<?php
		}
		?>
		
		
		
		var path = {
			"stroke":true, 
			"color": 'red'};
		var polyline = new L.Polyline(checkinPoints).setStyle(path).addTo(map);
		map.fitBounds(polyline.getBounds());
		
		$(document).ready(function(){
			    $( "#slider" ).slider({
			    	range: "min",
			      	value: <?php echo $limit; ?>,
			      	min: 5,
			      	max: 250,
			      	slide: function( event, ui ) {
			        	$( "#checkins" ).val(ui.value );
			      	}
			    });
			     $( "#checkins" ).val( $( "#slider" ).slider( "value" ) );
			 
		});
		
	</script>
</html>
