jQuery(document).ready(function() {
    // console.log(dtTrainingMetrics)

    if( '#cluster_map' === window.location.hash || ! window.location.hash) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_cluster_map()
    }
    if( '#choropleth_map' === window.location.hash) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_choropleth_map()
    }
    if( '#points_map' === window.location.hash  ) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_points_map()
    }
})

function write_training_cluster_map() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    tAPI.cluster_geojson()
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
                    font: 12px/20px 'Roboto','Helvetica Neue', Arial, Helvetica, sans-serif;
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
                    <div id="data">Zoom to ungrouped record and click for details.</div>
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
                    console.log( e.features )
                    let dataDiv = jQuery('#data')
                    dataDiv.empty()

                    jQuery.each( e.features, function(i,v) {
                        var address = v.properties.address;
                        var post_id = v.properties.post_id;
                        var name = v.properties.name

                        dataDiv.append(`<p><a href="/trainings/${post_id}">${name}</a><br>${address}</p>`)
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

function write_training_choropleth_map() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    tAPI.grid_totals()
        .then(grid_data=>{

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
                        top: 10px;
                        left: 10px;
                        z-index: 2;
                     }
                     #data {
                        word-wrap: break-word;
                     }
                    .legend {
                        background-color: #fff;
                        border-radius: 3px;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.10);
                        font: 12px/20px 'Helvetica Neue', Arial, Helvetica, sans-serif;
                        padding: 10px;
                        opacity: .9;
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
                    #cross-hair {
                        position: absolute;
                        z-index: 20;
                        font-size:3em;
                        top:50%;
                        left:50%;
                        display:none;
                        pointer-events: none;
                    }
                    #spinner {
                        position: absolute;
                        top:50%;
                        left:50%;
                        z-index: 20;
                        display:none;
                    }
                    .info-bar-font {
                        font-size: 1.5em;
                        padding-top: 9px;
                    }
                    .border-left {
                        border-left: 1px lightgray solid;
                    }
                </style>
                <div id="map-wrapper">
                    <div id='map'></div>
                    <div id='legend' class='legend'>
                        <div class="grid-x grid-margin-x grid-padding-x">
                            <div class="cell small-1 center info-bar-font">
                                Trainings 
                            </div>
                            <div class="cell small-2 center border-left">
                                <select id="level" class="small" style="width:170px;">
                                    <option value="none" disabled></option>
                                    <option value="none" disabled>Zoom Level</option>
                                    <option value="none"></option>
                                    <option value="none" selected>Auto Zoom</option>
                                    <option value="none" disabled>-----</option>
                                    <option value="world">World</option>
                                    <option value="country">Country</option>
                                    <option value="state">State</option>
                                    <option value="none" disabled></option>
                                </select> 
                            </div>
                            <div class="cell small-3 center border-left float-right" >
                                <div class="grid-x">
                                    <div class="cell small-3">
                                        Click
                                    </div>
                                    <div class="cell small-3">
                                        <div class="switch small center" style="margin:0 auto;">
                                          <input class="switch-input" id="click1" type="radio" checked name="exampleSwitch">
                                          <label class="switch-paddle" for="click1">
                                            <span class="show-for-sr">Layer</span>
                                          </label>
                                        </div>
                                        Layer
                                    </div>
                                    <div class="cell small-3">
                                        <div class="switch small center" style="margin:0 auto;">
                                          <input class="switch-input" id="click2" type="radio" name="exampleSwitch">
                                          <label class="switch-paddle" for="click2">
                                            <span class="show-for-sr">Add</span>
                                          </label>
                                        </div>
                                        Add
                                    </div>
                                    <div class="cell small-3">
                                        <div class="switch small center" style="margin:0 auto;">
                                          <input class="switch-input" id="click3" type="radio" name="exampleSwitch">
                                          <label class="switch-paddle" for="click4">
                                            <span class="show-for-sr">Details</span>
                                          </label>
                                        </div>
                                        Details
                                    </div>
                                    
                                </div>
                           </div>
                            
                            <div class="cell small-2 center border-left">
                                <select id="level" class="small" style="width:170px;">
                                    <option value="none" disabled></option>
                                    <option value="none" disabled>Status</option>
                                    <option value="none"></option>
                                    <option value="none" selected>All</option>
                                    <option value="none" disabled>-----</option>
                                    <option value="world">New</option>
                                    <option value="country">Proposed</option>
                                    <option value="state">In-Progress</option>
                                    <option value="state">Completed</option>
                                    <option value="state">Paused</option>
                                    <option value="state">Closed</option>
                                    <option value="none" disabled></option>
                                </select> 
                            </div>
                            <div class="cell small-2 center border-left info-bar-font">
                                
                            </div>
                            <div id="admin" class="cell small-1 center border-left info-bar-font">
                                World 
                            </div>
                            <div id="zoom" class="cell small-1 center border-left info-bar-font">
                                0
                            </div>
                        </div>
                    </div>
                    <div id="spinner"><img src="${obj.spinner_url}" alt="spinner" style="width: 25px;" /></div>
                    <div id="cross-hair">&#8982</div>
                </div>
             `)

            // set info box
            jQuery('.legend').css( 'width', jQuery('#map-wrapper').innerWidth() - 20 )
            jQuery( window ).resize(function() {
                jQuery('.legend').css( 'width', jQuery('#map-wrapper').innerWidth() - 20 )
            });

            // init map
            mapboxgl.accessToken = obj.map_key;
            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/light-v10',
                // style: 'mapbox://styles/mapbox/streets-v11',
                center: [-98, 38.88],
                minZoom: 1,
                zoom: 1
            });

            // disable map rotation using right click + drag
            map.dragRotate.disable();

            // disable map rotation using touch rotation gesture
            map.touchZoomRotate.disableRotation();

            // cross-hair
            map.on('zoomstart', function() {
                jQuery('#cross-hair').show()
            })
            map.on('zoomend', function() {
                jQuery('#cross-hair').hide()
            })
            map.on('dragstart', function() {
                jQuery('#cross-hair').show()
            })
            map.on('dragend', function() {
                jQuery('#cross-hair').hide()
            })

            // default load
            window.previous_grid_id = 0
            map.on('load', function() {

                window.previous_grid_id = '1'

                jQuery.get('https://storage.googleapis.com/location-grid-mirror/collection/1.geojson', null, null, 'json')
                    .done(function (geojson) {

                        jQuery.each(geojson.features, function (i, v) {
                            if (grid_data[geojson.features[i].properties.id]) {
                                geojson.features[i].properties.value = parseInt(grid_data[geojson.features[i].properties.id].count)
                            } else {
                                geojson.features[i].properties.value = 0
                            }
                        })
                        map.addSource('1', {
                            'type': 'geojson',
                            'data': geojson
                        });
                        map.addLayer({
                            'id': '1',
                            'type': 'fill',
                            'source': '1',
                            'paint': {
                                'fill-color': [
                                    'interpolate',
                                    ['linear'],
                                    ['get', 'value'],
                                    0,
                                    'rgba(0, 0, 0, 0)',
                                    1,
                                    '#547df8',
                                    50,
                                    '#3754ab',
                                    100,
                                    '#22346a'
                                ],
                                'fill-opacity': 0.75
                            }
                        });
                        map.addLayer({
                            'id': '1line',
                            'type': 'line',
                            'source': '1',
                            'paint': {
                                'line-color': 'black',
                                'line-width': 1
                            }
                        });
                    })
            })

            // zoom
            map.on('zoomend', function() {
                let lnglat = map.getCenter()
                load_layer( lnglat.lng, lnglat.lat )
            } )

            // drag pan
            map.on('dragend', function() {
                let lnglat = map.getCenter()
                load_layer( lnglat.lng, lnglat.lat )
            } )

            // click
            map.on('click', function( e ) {
                load_layer( e.lngLat.lng, e.lngLat.lat )
            })

            // load layer
            function load_layer( lng, lat ) {
                let spinner = jQuery('#spinner')
                spinner.show()

                // set geocode level
                let level = 'admin0'
                if ( map.getZoom() <= 2 ) {
                    level = 'world'
                }
                else if ( map.getZoom() >= 5 ) {
                    level = 'admin1'
                }

                // standardize longitude
                if (lng > 180) {
                    lng = lng - 180
                    lng = -Math.abs(lng)
                } else if (lng < -180) {
                    lng = lng + 180
                    lng = Math.abs(lng)
                }

                // geocode
                jQuery.get(obj.plugin_uri + 'includes/location-grid-map-api.php',
                    {
                        type: 'geocode',
                        longitude: lng,
                        latitude: lat,
                        level: level,
                        country_code: null,
                        nonce: obj.nonce
                    }, null, 'json').done(function (data) {

                        // default layer to world
                        if ( data.grid_id === undefined ) {
                            data.grid_id = '1'
                        }

                        // load layer, if new
                        if ( window.previous_grid_id !== data.grid_id ) {

                            // remove previous layer
                            if ( window.previous_grid_id > 0 && map.getLayer( window.previous_grid_id.toString() ) ) {
                                map.removeLayer(window.previous_grid_id.toString() )
                                map.removeLayer(window.previous_grid_id.toString() + 'line' )
                                map.removeSource(window.previous_grid_id.toString() )
                            }
                            window.previous_grid_id = data.grid_id

                            // add info to box
                            if (data && data.grid_id !== '1' ) {
                                jQuery('#data').empty().html(`
                                <p><strong>${data.name}</strong></p>
                                <p>Population: ${data.population}</p>
                                `)
                            }

                            // add layer
                            var mapLayer = map.getLayer(data.grid_id);
                            if(typeof mapLayer === 'undefined') {

                                // get geojson collection
                                jQuery.get('https://storage.googleapis.com/location-grid-mirror/collection/'+data.grid_id+'.geojson', null, null, 'json')
                                    .done(function (geojson) {

                                        // add data to geojson properties
                                        jQuery.each( geojson.features, function(i,v) {
                                            if ( grid_data[geojson.features[i].properties.id] ) {
                                                geojson.features[i].properties.value = parseInt( grid_data[geojson.features[i].properties.id].count )
                                            } else {
                                                geojson.features[i].properties.value = 0
                                            }
                                        })

                                        // add source
                                        map.addSource(data.grid_id.toString(), {
                                            'type': 'geojson',
                                            'data': geojson
                                        });

                                        // add fill layer
                                        map.addLayer({
                                            'id': data.grid_id.toString(),
                                            'type': 'fill',
                                            'source': data.grid_id.toString(),
                                            'paint': {
                                                'fill-color': [
                                                    'interpolate',
                                                    ['linear'],
                                                    ['get', 'value'],
                                                    0,
                                                    'rgba(0, 0, 0, 0)',
                                                    1,
                                                    '#547df8',
                                                    50,
                                                    '#3754ab',
                                                    100,
                                                    '#22346a'
                                                ],
                                                'fill-opacity': 0.75
                                            }
                                        });

                                        // add border lines
                                        map.addLayer({
                                            'id': data.grid_id.toString() + 'line',
                                            'type': 'line',
                                            'source': data.grid_id.toString(),
                                            'paint': {
                                                'line-color': 'black',
                                                'line-width': 1
                                            }
                                        });
                                    }) // end get geojson collection
                            } // end add layer
                        } // end load new layer
                    spinner.hide()
                }); // end geocode
            } // end load section function

            // info box
            map.on('zoom', function() {
                document.getElementById('zoom').innerHTML = Math.floor(map.getZoom())

                let level = 'Country'
                if ( map.getZoom() <= 2 ) {
                    level = 'World'
                }
                else if ( map.getZoom() >= 5 ) {
                    level = 'State'
                }
                document.getElementById('admin').innerHTML = level
            })



        }).catch(err=>{
        console.log("error")
        console.log(err)
    })

}

function write_training_points_map() {
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
                    #cross-hair {
                        position: absolute;
                        z-index: 20;
                        font-size:3em;
                        top:50%;
                        left:50%;
                        display:none;
                        pointer-events: none;
                    }
                    #spinner {
                        position: absolute;
                        top:50%;
                        left:50%;
                        z-index: 20;
                        display:none;
                    }
                </style>
                <div id="map-wrapper">
                    <div id='map'></div>
                    <div id='legend' class='legend'>
                        <div id="grid"></div>
                        <div id="info"></div>
                        <div id="data"></div>
                    </div>
                    <div id="spinner"><img src="${obj.spinner_url}" alt="spinner" style="width: 25px;" /></div>
                    <div id="cross-hair">&#8982</div>
                </div>
             `)


    mapboxgl.accessToken = obj.map_key;
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/light-v10',
        // style: 'mapbox://styles/mapbox/streets-v11',
        center: [-98, 38.88],
        minZoom: 1,
        zoom: 1
    });

    // disable map rotation using right click + drag
    map.dragRotate.disable();

    // disable map rotation using touch rotation gesture
    map.touchZoomRotate.disableRotation();

    // load sources
    map.on('load', function() {
        let spinner = jQuery('#spinner')
        spinner.show()

        tAPI.points_geojson()
            .then(points=>{
                map.addSource('points', {
                    'type': 'geojson',
                    'data': points
                });
            })
        jQuery.get('https://storage.googleapis.com/location-grid-mirror/collection/1.geojson', null, null, 'json')
            .done(function (geojson) {

                tAPI.grid_country_totals()
                    .then(country_totals=>{

                        jQuery.each( geojson.features, function(i,v) {
                            if ( country_totals[geojson.features[i].properties.id] ) {
                                geojson.features[i].properties.value = parseInt( country_totals[geojson.features[i].properties.id].count )
                            } else {
                                geojson.features[i].properties.value = 0
                            }
                        })

                        map.addSource('world', {
                            'type': 'geojson',
                            'data': geojson
                        });
                        map.addLayer({
                            'id': 'world',
                            'type': 'fill',
                            'source': 'world',
                            'paint': {
                                'fill-color': [
                                    'interpolate',
                                    ['linear'],
                                    ['get', 'value'],
                                    0,
                                    'rgba(0, 0, 0, 0)',
                                    1,
                                    '#547df8',
                                    50,
                                    '#3754ab',
                                    100,
                                    '#22346a'
                                ],
                                'fill-opacity': 0.75
                            }
                        });

                        spinner.hide()
                    })

            })

    })

    // cross-hair
    map.on('zoomstart', function() {
        jQuery('#cross-hair').show()
    })
    map.on('zoomend', function() {
        jQuery('#cross-hair').hide()
    })
    map.on('dragstart', function() {
        jQuery('#cross-hair').show()
    })
    map.on('dragend', function() {
        jQuery('#cross-hair').hide()
    })

    window.previous_grid_id = '0'
    function load_world() {
        // remove previous layer
        if ( window.previous_grid_id > '0' && window.previous_grid_id !== '1' ) {
            map.removeLayer(window.previous_grid_id.toString() + 'line' )
            map.removeLayer(window.previous_grid_id.toString() + 'points' )
            map.removeSource( window.previous_grid_id.toString() )
        }
        window.previous_grid_id = '0'

        map.setLayoutProperty('world', 'visibility', 'visible');
    }


    // load layer events
    // zoom
    map.on('zoomend', function() {
        let lnglat = map.getCenter()
        if ( map.getZoom() <= 2 ) {
            load_world()
        } else {
            load_layer( lnglat.lng, lnglat.lat )
        }
    } )
    // drag pan
    map.on('dragend', function() {
        let lnglat = map.getCenter()
        if ( map.getZoom() <= 2 ) {
            load_world()
        } else {
            load_layer( lnglat.lng, lnglat.lat )
        }
    } )

    function load_layer( lng, lat ) {
        let spinner = jQuery('#spinner')
        spinner.show()

        map.setLayoutProperty('world', 'visibility', 'none');

        // set geocode level
        let level = 'admin0'

        // standardize longitude
        if (lng > 180) {
            lng = lng - 180
            lng = -Math.abs(lng)
        } else if (lng < -180) {
            lng = lng + 180
            lng = Math.abs(lng)
        }

        // geocode
        jQuery.get(obj.plugin_uri + 'includes/location-grid-map-api.php',
            {
                type: 'geocode',
                longitude: lng,
                latitude: lat,
                level: level,
                country_code: null,
                nonce: obj.nonce
            }, null, 'json').done(function (data) {

            // default layer to world
            if ( data.grid_id === undefined ) {
                load_world()
            }

            // load layer, if new
             else if ( window.previous_grid_id !== data.grid_id ) {

                // remove previous layer
                if ( window.previous_grid_id > 0 && map.getLayer( window.previous_grid_id.toString() + 'line' ) ) {
                    map.removeLayer(window.previous_grid_id.toString() + 'line' )
                    map.removeSource( window.previous_grid_id.toString() )
                    var mapPointsLayer = map.getLayer( window.previous_grid_id.toString() + 'points' );
                    if(typeof mapPointsLayer !== 'undefined') {
                        map.removeLayer(window.previous_grid_id.toString() + 'points')
                    }
                }
                window.previous_grid_id = data.grid_id

                // add info to box
                if (data && data.grid_id !== '1' ) {
                    jQuery('#data').empty().html(`
                        <p><strong>${data.name}</strong></p>
                        <p>Population: ${data.population}</p>
                        `)
                }

                // add layer
                var mapLayer = map.getLayer(data.grid_id);
                if(typeof mapLayer === 'undefined') {

                    // get geojson collection
                    jQuery.get('https://storage.googleapis.com/location-grid-mirror/low/'+data.grid_id+'.geojson', null, null, 'json')
                        .done(function (geojson) {

                            // add source
                            map.addSource(data.grid_id.toString(), {
                                'type': 'geojson',
                                'data': geojson
                            });
                            // add border lines
                            map.addLayer({
                                'id': data.grid_id.toString() + 'line',
                                'type': 'line',
                                'source': data.grid_id.toString(),
                                'paint': {
                                    'line-color': '#22346a',
                                    'line-width': 2
                                }
                            });
                            map.addLayer({
                                id: data.grid_id.toString() + 'points',
                                type: 'circle',
                                source: 'points',
                                paint: {
                                    'circle-color': '#11b4da',
                                    'circle-radius':12,
                                    'circle-stroke-width': 1,
                                    'circle-stroke-color': '#fff'
                                },
                                filter: ["==", data.grid_id.toString(), ["get", "a0"] ]
                            });
                            map.on('click', data.grid_id.toString() + 'points', function(e) {
                                console.log( e.features )
                                let dataDiv = jQuery('#data')
                                dataDiv.empty()

                                jQuery.each( e.features, function(i,v) {
                                    var address = v.properties.l;
                                    var post_id = v.properties.pid;
                                    var name = v.properties.n

                                    dataDiv.append(`<p><a href="/trainings/${post_id}">${name}</a><br>${address}</p>`)
                                })

                            });
                            map.on('mouseenter', data.grid_id.toString() + 'points', function() {
                                map.getCanvas().style.cursor = 'pointer';
                            });
                            map.on('mouseleave', data.grid_id.toString() + 'points', function() {
                                map.getCanvas().style.cursor = '';
                            });
                        }) // end get geojson collection
                } // end add layer
            } // end load new layer
            spinner.hide()
        }); // end geocode
    } // end load section function

    // click
    // map.on('click', function( e ) {
    //     if ( map.getZoom() <= 2 ) {
    //         load_world()
    //     } else {
    //         load_layer( e.lngLat.lng, e.lngLat.lat )
    //
    //     }
    // })

}

window.tAPI = {

    cluster_geojson: () => makeRequest('GET', 'trainings/cluster_geojson' ),

    points_geojson: () => makeRequest('GET', 'trainings/points_geojson' ),

    grid_totals: () => makeRequest('GET', 'trainings/grid_totals' ),

    grid_country_totals: () => makeRequest('GET', 'trainings/grid_country_totals' ),

}
function makeRequest (type, url, data, base = 'dt/v1/') {
    const options = {
        type: type,
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        url: url.startsWith('http') ? url : `${dtTrainingMetrics.root}${base}${url}`,
        beforeSend: xhr => {
            xhr.setRequestHeader('X-WP-Nonce', dtTrainingMetrics.nonce);
        }
    }

    if (data) {
        options.data = JSON.stringify(data)
    }

    return jQuery.ajax(options)
}
function handleAjaxError (err) {
    if (_.get(err, "statusText") !== "abortPromise" && err.responseText){
        console.trace("error")
        console.log(err)
    }
}
jQuery(document).ajaxComplete((event, xhr, settings) => {
    if (_.get(xhr, 'responseJSON.data.status') === 401) {
        console.log('401 error')
        console.log(xhr)
    }
}).ajaxError((event, xhr) => {
    handleAjaxError(xhr)
})