


// @todo development only
function write_training_heatmap2() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    chart.empty().html(`
        <style>
            #map-wrapper {
                position: relative;
                height: ${window.innerHeight - 100}px; 
                width:100%;
            }
            #map { 
                position: absolute;
                top: 0;
                left: 0;
                z-index: 1;
                width:100%;
                height: ${window.innerHeight - 100}px; 
             }
             #legend {
                position: absolute;
                top: 20px;
                right: 20px;
                z-index: 10;
             }
            .legend {
                background-color: #fff;
                border-radius: 3px;
                width: 250px;
                
                box-shadow: 0 1px 2px rgba(0,0,0,0.10);
                font: 12px/20px 'Helvetica Neue', Arial, Helvetica, sans-serif;
                padding: 10px;
            }
            .legend h4 {
                margin: 0 0 10px;
            }
        
            .legend div span {
                border-radius: 50%;
                display: inline-block;
                height: 10px;
                margin-right: 5px;
                width: 10px;
            }
        </style>
        <div id="map-wrapper">
            <div id='map'></div>
            <div id='legend' class='legend'>
                <h4>Coordinates</h4>
            <div id="data"></div>
        </div>
        </div>
        

        
     `)

    mapboxgl.accessToken = obj.map_key;
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-98, 38.88],
        minZoom: 0,
        zoom: 0
    });

    var zoomThreshold = 4;

    map.on('load', function() {

        map.addSource('world', {
            type: 'geojson',
            data: 'https://storage.googleapis.com/location-grid-mirror/collection/1.geojson'
        });
        map.addSource('100364199', {
            type: 'geojson',
            data: 'https://storage.googleapis.com/location-grid-mirror/collection/100364199.geojson'
        });
        map.addSource('100364205', {
            type: 'geojson',
            data: 'https://storage.googleapis.com/location-grid-mirror/collection/100364205.geojson'
        });
        map.addLayer({
            "id": '1' + Date.now() + Math.random(),
            "type": "fill",
            "source": 'world',
            'maxzoom': 3.7,
            "paint": {
                "fill-color": "#A25626",
                "fill-opacity": 0.4

            },
            "filter": ["==", "$type", "Polygon"]
        });
        map.addLayer({
            "id": '1' + Date.now() + Math.random(),
            "type": "fill",
            "source": '100364199',
            'minzoom': 3.7,
            'maxzoom': 6.5,
            "paint": {
                "fill-color": "#A25626",
                "fill-opacity": 0.4

            },
            "filter": ["==", "$type", "Polygon"]
        });
        map.addLayer({
            "id": '1' + Date.now() + Math.random(),
            "type": "fill",
            "source": '100364205',
            'minzoom': 6.5,
            "paint": {
                "fill-color": "#A25626",
                "fill-opacity": 0.4

            },
            "filter": ["==", "$type", "Polygon"]
        });

    });
    document.getElementById('data').innerHTML = 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()


    let level = 0
    map.on('zoom', function() {
        // world
        if ( map.getZoom() < 3.7 && level !== 0 ) {
            level = 0

            document.getElementById('data').innerHTML = 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()

            let bbox = map.getBounds()
            jQuery.get('https://global.zume.network.local/wp-content/themes/disciple-tools-theme/dt-mapping/location-grid-list-api.php',
                {
                    type: 'match_within_bbox',
                    north_latitude: bbox._ne.lat,
                    south_latitude: bbox._sw.lat,
                    west_longitude: bbox._sw.lng,
                    east_longitude: bbox._ne.lng,
                    level: level,
                    nonce: '12345'
                }, null, 'json' ).done(function(data) {
                console.log(data)
            })

        }
        // country
        if ( map.getZoom() >= 3.7 && map.getZoom() < 6.5 && level !== 1 ) {
            level = 1

            document.getElementById('data').innerHTML = 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()

            // query which states are within bounding box boundaries

            // loop through grid_id's
            // add sources
            // add layers
            // add grid_id to    list

            let bbox = map.getBounds()
            jQuery.get('https://global.zume.network.local/wp-content/themes/disciple-tools-theme/dt-mapping/location-grid-list-api.php',
                {
                    type: 'match_within_bbox',
                    north_latitude: bbox._ne.lat,
                    south_latitude: bbox._sw.lat,
                    west_longitude: bbox._sw.lng,
                    east_longitude: bbox._ne.lng,
                    level: level,
                    nonce: '12345'
                }, null, 'json' ).done(function(data) {
                console.log(data)
            })



        }
        // state
        if ( map.getZoom() >= 6.5  && level !== 2 ) {
            level = 2

            document.getElementById('data').innerHTML = 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()

            let bbox = map.getBounds()
            jQuery.get('https://global.zume.network.local/wp-content/themes/disciple-tools-theme/dt-mapping/location-grid-list-api.php',
                {
                    type: 'match_within_bbox',
                    north_latitude: bbox._ne.lat,
                    south_latitude: bbox._sw.lat,
                    west_longitude: bbox._sw.lng,
                    east_longitude: bbox._ne.lng,
                    level: level,
                    nonce: '12345'
                }, null, 'json' ).done(function(data) {
                console.log(data)
            })

        }
    })

}

// @todo development only
function write_training_heatmap3() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    chart.empty().html(`
        <style>
            #map-wrapper {
                position: relative;
                height: ${window.innerHeight - 100}px; 
                width:100%;
            }
            #map { 
                position: absolute;
                top: 0;
                left: 0;
                z-index: 1;
                width:100%;
                height: ${window.innerHeight - 100}px; 
             }
             #legend {
                position: absolute;
                top: 20px;
                right: 20px;
                z-index: 10;
             }
             #data {
                word-wrap: break-word;
             }
            .legend {
                background-color: #fff;
                border-radius: 3px;
                width: 250px;
                
                box-shadow: 0 1px 2px rgba(0,0,0,0.10);
                font: 12px/20px 'Helvetica Neue', Arial, Helvetica, sans-serif;
                padding: 10px;
            }
            .legend h4 {
                margin: 0 0 10px;
            }    
            .legend div span {
                border-radius: 50%;
                display: inline-block;
                height: 10px;
                margin-right: 5px;
                width: 10px;
            }
        </style>
        <div id="map-wrapper">
            <div id='map'></div>
            <div id='legend' class='legend'>
                <h4>Location Information</h4><hr>
            <div id="data"></div>
        </div>
        </div>
     `)

    mapboxgl.accessToken = obj.map_key;
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-98, 38.88],
        minZoom: 0,
        zoom: 0
    });
    map.on('load', function() {


        map.addLayer({
            'id': 'states-layer-outline',
            'type': 'line',
            'source': {
                'type': 'geojson',
                'data': 'https://storage.googleapis.com/location-grid-mirror/collection/1.geojson'
            },
            'paint': {
                'line-color': 'black',
                'line-width': 2
            }
        });

    })

    /***********************************
     * Click
     ***********************************/
    map.on('click', function (e) {
        console.log(e)

        let level = jQuery('#level').val()

        let lng = e.lngLat.lng
        let lat = e.lngLat.lat

        if (lng > 180) {
            lng = lng - 180
            lng = -Math.abs(lng)
        } else if (lng < -180) {
            lng = lng + 180
            lng = Math.abs(lng)
        }

        window.active_lnglat = [lng, lat]

        // add marker
        if (window.active_marker) {
            window.active_marker.remove()
        }
        window.active_marker = new mapboxgl.Marker()
            .setLngLat(e.lngLat)
            .addTo(map);

        jQuery.get(obj.theme_uri + 'dt-mapping/location-grid-list-api.php',
            {
                type: 'geocode',
                longitude: lng,
                latitude: lat,
                level: 'admin0',
                country_code: null,
                nonce: obj.nonce
            }, null, 'json').done(function (data) {
            if (data) {
                jQuery('#data').empty().html(`
                    <p><strong>${data.name}</strong></p>
                    <p>Population: ${data.population}</p>
                    `)
            }
            console.log(data)
        });
    })
}

// @todo development only
function write_training_heatmap4() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    chart.empty().html(`
    <style>
        #map-wrapper {
            position: relative;
            height: ${window.innerHeight - 100}px; 
            width:100%;
        }
        #map { 
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
            width:100%;
            height: ${window.innerHeight - 100}px; 
         }
         #legend {
            position: absolute;
            top: 50px;
            right: 20px;
            z-index: 10;
         }
         #data {
            word-wrap: break-word;
         }
        .legend {
            background-color: #fff;
            border-radius: 3px;
            width: 250px;
            
            box-shadow: 0 1px 2px rgba(0,0,0,0.10);
            font: 12px/20px 'Helvetica Neue', Arial, Helvetica, sans-serif;
            padding: 10px;
        }
        .legend h4 {
            margin: 0 0 10px;
        }    
        .legend div span {
            border-radius: 50%;
            display: inline-block;
            height: 10px;
            margin-right: 5px;
            width: 10px;
        }
    </style>
    <div id="map-wrapper">
        <div id='map'></div>
        <div id='legend' class='legend'>
            <h4>Location Information</h4><hr>
        <div id="data">Sample of a points cluster map</div>
    </div>
    </div>
    `)
    //https://docs.mapbox.com/api/maps/#styles

    mapboxgl.accessToken = obj.map_key;
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        center: [-98, 38.88],
        minZoom: 0,
        zoom: 0
    });
    map.addControl(new mapboxgl.FullscreenControl());

    map.on('load', function() {
// Add a geojson point source.
// Heatmap layers also work with a vector tile source.
        map.addSource('earthquakes', {
            'type': 'geojson',
            'data':
                'https://docs.mapbox.com/mapbox-gl-js/assets/earthquakes.geojson'
        });

        map.addLayer(
            {
                'id': 'earthquakes-heat',
                'type': 'heatmap',
                'source': 'earthquakes',
                'maxzoom': 9,
                'paint': {
// Increase the heatmap weight based on frequency and property magnitude
                    'heatmap-weight': [
                        'interpolate',
                        ['linear'],
                        ['get', 'mag'],
                        0,
                        0,
                        6,
                        1
                    ],
// Increase the heatmap color weight weight by zoom level
// heatmap-intensity is a multiplier on top of heatmap-weight
                    'heatmap-intensity': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        0,
                        1,
                        9,
                        3
                    ],
// Color ramp for heatmap.  Domain is 0 (low) to 1 (high).
// Begin color ramp at 0-stop with a 0-transparancy color
// to create a blur-like effect.
                    'heatmap-color': [
                        'interpolate',
                        ['linear'],
                        ['heatmap-density'],
                        0,
                        'rgba(33,102,172,0)',
                        0.2,
                        'rgb(103,169,207)',
                        0.4,
                        'rgb(209,229,240)',
                        0.6,
                        'rgb(253,219,199)',
                        0.8,
                        'rgb(239,138,98)',
                        1,
                        'rgb(178,24,43)'
                    ],
// Adjust the heatmap radius by zoom level
                    'heatmap-radius': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        0,
                        2,
                        9,
                        20
                    ],
// Transition from heatmap to circle layer by zoom level
                    'heatmap-opacity': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        7,
                        1,
                        9,
                        0
                    ]
                }
            },
            'waterway-label'
        );

        map.addLayer(
            {
                'id': 'earthquakes-point',
                'type': 'circle',
                'source': 'earthquakes',
                'minzoom': 7,
                'paint': {
// Size circle radius by earthquake magnitude and zoom level
                    'circle-radius': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        7,
                        ['interpolate', ['linear'], ['get', 'mag'], 1, 1, 6, 4],
                        16,
                        ['interpolate', ['linear'], ['get', 'mag'], 1, 5, 6, 50]
                    ],
// Color circle by earthquake magnitude
                    'circle-color': [
                        'interpolate',
                        ['linear'],
                        ['get', 'mag'],
                        1,
                        'rgba(33,102,172,0)',
                        2,
                        'rgb(103,169,207)',
                        3,
                        'rgb(209,229,240)',
                        4,
                        'rgb(253,219,199)',
                        5,
                        'rgb(239,138,98)',
                        6,
                        'rgb(178,24,43)'
                    ],
                    'circle-stroke-color': 'white',
                    'circle-stroke-width': 1,
// Transition from heatmap to circle layer by zoom level
                    'circle-opacity': [
                        'interpolate',
                        ['linear'],
                        ['zoom'],
                        7,
                        0,
                        8,
                        1
                    ]
                }
            },
            'waterway-label'
        );
    });

}

// drag
/*
    map.on('dragend', function(){

    let level = '0'
    window.zoom_level = Math.ceil( map.getZoom() )
    if ( window.zoom_level >= 3  && window.zoom_level < 7 ) {
        level = '1'
    } else if ( window.zoom_level >= 7 ) {
        level = '2'
    }
    level = '2'
    let bounds = map.getBounds()

    jQuery.get(obj.theme_uri + 'dt-mapping/location-grid-list-api.php',
        {
            type: 'match_within_bbox',
            north_latitude: bounds._ne.lat,
            south_latitude: bounds._sw.lat,
            west_longitude: bounds._sw.lng,
            east_longitude: bounds._ne.lng,
            level: level,
            nonce: obj.nonce
        }, null, 'json').done(function (data) {
        if (data) {
            // console.log(data)

            let old_list = Array.from(window.boundary_list)
            window.boundary_list = data

            console.log(map.getStyle().layers)

            jQuery.each( data, function( i,v ){

                var mapLayer = map.getLayer(v.toString());

                if(typeof mapLayer === 'undefined') {
                    map.addLayer({
                        'id': v.toString(),
                        'type': 'line',
                        'source': {
                            'type': 'geojson',
                            'data': 'https://storage.googleapis.com/location-grid-mirror/low/'+v+'.geojson'
                        },
                        'paint': {
                            'line-color': 'red',
                            'line-width': 2
                        }
                    });
                }
            })

            jQuery.each( old_list, function(i,v) {

                if ( data.indexOf(v) < 0 && map.getLayer( v.toString() ) ) {

                    map.removeLayer( v.toString() )
                    console.log( 'removed: ' + v.toString())
                }

            })

        }


    });
})
*/

// map.on('click', function (e) {
//     let spinner = jQuery('#spinner')
//     spinner.show()
//
//     let level = 'admin0'
//     if ( map.getZoom() <= 2 ) {
//         level = 'world'
//     }
//     else if ( map.getZoom() >= 5 ) {
//         level = 'admin1'
//     }
//
//
//
//     if (lng > 180) {
//         lng = lng - 180
//         lng = -Math.abs(lng)
//     } else if (lng < -180) {
//         lng = lng + 180
//         lng = Math.abs(lng)
//     }
//
//     window.active_lnglat = [lng, lat]
//
//     // add marker
//     if (window.active_marker) {
//         window.active_marker.remove()
//     }
//     window.active_marker = new mapboxgl.Marker()
//         .setLngLat(e.lngLat)
//         .addTo(map);
//
//     jQuery.get(obj.plugin_uri + 'includes/training-location-grid-api.php',
//         {
//             type: 'geocode',
//             longitude: lng,
//             latitude: lat,
//             level: level,
//             country_code: null,
//             nonce: obj.nonce
//         }, null, 'json').done(function (data) {
//
//         if ( data.grid_id === undefined ) {
//             data.grid_id = '1'
//         }
//
//         if ( window.previous_grid_id !== data.grid_id ) {
//
//             // remove previous
//             if ( window.previous_grid_id > 0 && map.getLayer( window.previous_grid_id.toString() ) ) {
//                 map.removeLayer(window.previous_grid_id.toString() )
//                 map.removeLayer(window.previous_grid_id.toString() + 'line' )
//                 map.removeSource(window.previous_grid_id.toString() )
//             }
//             // set new
//             window.previous_grid_id = data.grid_id
//
//             // add info to box
//             if (data && data.grid_id !== '1' ) {
//                 jQuery('#data').empty().html(`
//             <p><strong>${data.name}</strong></p>
//             <p>Population: ${data.population}</p>
//             `)
//             }
//
//             // add layer
//             var mapLayer = map.getLayer(data.grid_id);
//             if(typeof mapLayer === 'undefined') {
//
//                 jQuery.get('https://storage.googleapis.com/location-grid-mirror/collection/'+data.grid_id+'.geojson', null, null, 'json')
//                     .done(function (geojson) {
//
//                         jQuery.each( geojson.features, function(i,v) {
//                             if ( dtTrainingMetrics.data[geojson.features[i].properties.id] ) {
//                                 geojson.features[i].properties.value = parseInt( dtTrainingMetrics.data[geojson.features[i].properties.id].count )
//                             } else {
//                                 geojson.features[i].properties.value = 0
//                             }
//                         })
//                         map.addSource(data.grid_id.toString(), {
//                             'type': 'geojson',
//                             'data': geojson
//                         });
//                         map.addLayer({
//                             'id': data.grid_id.toString(),
//                             'type': 'fill',
//                             'source': data.grid_id.toString(),
//                             'paint': {
//                                 'fill-color': [
//                                     'interpolate',
//                                     ['linear'],
//                                     ['get', 'value'],
//                                     0,
//                                     'rgba(0, 0, 0, 0)',
//                                     1,
//                                     '#547df8',
//                                     50,
//                                     '#3754ab',
//                                     100,
//                                     '#22346a'
//                                 ],
//                                 'fill-opacity': 0.75
//                             }
//                         });
//                         map.addLayer({
//                             'id': data.grid_id.toString() + 'line',
//                             'type': 'line',
//                             'source': data.grid_id.toString(),
//                             'paint': {
//                                 'line-color': 'black',
//                                 'line-width': 1
//                             }
//                         });
//                     })
//             }
//         }
//         spinner.hide()
//     });
// })

function contacts_map() {
    let obj = dtTrainingMetrics
    console.log(dtTrainingMetrics)
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.theme_uri}/spinner.svg" width="30px" alt="spinner" />`)

    jQuery.ajax({
        type: "GET",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: dtTrainingMetrics.root + 'dt/v1/trainings/contacts_map/',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', dtTrainingMetrics.nonce);
        },
    })
        .then(data=>{
            console.log(data)

            let geojson = JSON.stringify( data )

            chart.empty().html(`
            <style>
                #map-wrapper {
                    position: relative;
                    height: ${window.innerHeight - 100}px; 
                    width:100%;
                }
                #map { 
                    position: absolute;
                    top: 0;
                    left: 0;
                    z-index: 1;
                    width:100%;
                    height: ${window.innerHeight - 100}px; 
                 }
                 #legend {
                    position: absolute;
                    top: 50px;
                    right: 20px;
                    z-index: 2;
                 }
                 #data {
                    word-wrap: break-word;
                 }
                .legend {
                    background-color: #fff;
                    border-radius: 3px;
                    width: 250px;
                    
                    box-shadow: 0 1px 2px rgba(0,0,0,0.10);
                    font: 12px/20px 'Helvetica Neue', Arial, Helvetica, sans-serif;
                    padding: 10px;
                }
                .legend h4 {
                    margin: 0 0 10px;
                }    
                .legend div span {
                    border-radius: 50%;
                    display: inline-block;
                    height: 10px;
                    margin-right: 5px;
                    width: 10px;
                }
            </style>
            <div id="map-wrapper">
                <div id='map'></div>
                <div id='legend' class='legend'>
                    <h4>Contacts</h4><hr>
                <div id="data">Select a location to see contacts</div>
            </div>
            </div>
            `)

            mapboxgl.accessToken = obj.map_key;
            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/light-v10',
                center: [-98, 38.88],
                minZoom: 0,
                zoom: 0
            });
            map.addControl(new mapboxgl.FullscreenControl());

            map.on('load', function() {

                map.addSource('trainings', {
                    type: 'geojson',
                    data: data,
                    cluster: true,
                    clusterMaxZoom: 14,
                    clusterRadius: 50
                });

                map.addLayer({
                    id: 'clusters',
                    type: 'circle',
                    source: 'trainings',
                    filter: ['has', 'point_count'],
                    paint: {
                        'circle-color': [
                            'step',
                            ['get', 'point_count'],
                            '#51bbd6',
                            100,
                            '#f1f075',
                            750,
                            '#f28cb1'
                        ],
                        'circle-radius': [
                            'step',
                            ['get', 'point_count'],
                            20,
                            100,
                            30,
                            750,
                            40
                        ]
                    }
                });

                map.addLayer({
                    id: 'cluster-count',
                    type: 'symbol',
                    source: 'trainings',
                    filter: ['has', 'point_count'],
                    layout: {
                        'text-field': '{point_count_abbreviated}',
                        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                        'text-size': 12
                    }
                });

                map.addLayer({
                    id: 'unclustered-point',
                    type: 'circle',
                    source: 'trainings',
                    filter: ['!', ['has', 'point_count']],
                    paint: {
                        'circle-color': '#11b4da',
                        'circle-radius':12,
                        'circle-stroke-width': 1,
                        'circle-stroke-color': '#fff'
                    }
                });


                map.on('click', 'clusters', function(e) {

                    var features = map.queryRenderedFeatures(e.point, {
                        layers: ['clusters']
                    });

                    var clusterId = features[0].properties.cluster_id;
                    map.getSource('trainings').getClusterExpansionZoom(
                        clusterId,
                        function(err, zoom) {
                            if (err) return;

                            map.easeTo({
                                center: features[0].geometry.coordinates,
                                zoom: zoom
                            });
                        }
                    );
                })


                map.on('click', 'unclustered-point', function(e) {
                    let info = jQuery('#data')
                    info.empty().html(`<img src="${obj.theme_uri}/spinner.svg" width="30px" alt="spinner" />`)

                    var coordinates = e.features[0].geometry.coordinates.slice();

                    while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                        coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
                    }

                    let searchParameters = {
                        location_grid: [ e.features[0].properties.location_grid ]
                        // overall_status: [ 'active', 'new', 'unassigned', 'assigned', 'paused' ]
                    }

                    jQuery.ajax({
                        type: "GET",
                        contentType: "application/json; charset=utf-8",
                        data: searchParameters,
                        url: dtTrainingMetrics.root + 'dt-posts/v2/contacts/',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', dtTrainingMetrics.nonce);
                        },
                    })
                        .done(function( results ) {

                            console.log(results)
                            info.empty()

                            if ( results.posts ) {
                                jQuery.each(results.posts, function(i,v) {
                                    info.append(`<p><a href="/contacts/${v.ID}">${v.post_title}</a></p>`)
                                })
                            }
                        })

                });

                map.on('mouseenter', 'clusters', function() {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'clusters', function() {
                    map.getCanvas().style.cursor = '';
                });
            });

        }).catch(err=>{
        console.log("error")
        console.log(err)
    })

}


// @todo development only
function write_training_overview() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<h2>Overview</h2>`)


}