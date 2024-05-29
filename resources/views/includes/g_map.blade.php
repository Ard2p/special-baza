@section('map')
    @php
    $machines = \App\Machinery::all();
    @endphp
    <div id="map" style="height: 600px;"></div>
    @push('after-scripts')
        <script>
            // This example creates circles on the map, representing populations in North
            // America.

            // First, create an object containing LatLng and population for each city.
            var citymap = {
                @foreach($machines as $machine)
               '{{$machine->id}}': {
                    center: {lat: {{$machine->lat}}, lng: {{$machine->lng}}},
                    type: '{{$machine->_type->name ?? ''}}'
                },
                @endforeach
            };

            function initMap() {
                // Create the map.
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 6,
                    center: {lat: 55.656981, lng: 37.8642473},
                    mapTypeId: 'terrain'
                });

                // Construct the circle for each value in citymap.
                // Note: We scale the area of the circle based on the population.
                for (var city in citymap) {
                    // Add the circle for this city to the map.
                    var cityCircle = new google.maps.Circle({
                        strokeColor: '#24aff463',
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: '#24aff463',
                        fillOpacity: 0.15,
                        map: map,
                        center: citymap[city].center,
                        radius: 30000
                    });
                    newMarker(citymap[city].center, citymap[city].type)
                }
                function newMarker(position, title = '', string = '') {
                    var contentString = title;

                    var infowindow = new google.maps.InfoWindow({
                        content: contentString
                    });

                    var marker = new google.maps.Marker({
                        map: map,
                        position: position,

                    })
                    marker.addListener('click', function () {
                        infowindow.open(map, marker);
                    });
                }
            }
        </script>
        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=AIzaSyADNF1IcRIfY1GtUDGr-Gxz25TO4IwSWP0&callback=initMap">
        </script>
    @endpush
@endsection