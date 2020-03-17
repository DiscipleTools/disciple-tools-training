jQuery(document).ready(function() {
    console.log(dtTrainingMetrics)

    if( '#cluster_map' === window.location.hash || ! window.location.hash) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_cluster_map()
    }
    if( '#choropleth_map' === window.location.hash) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_choropleth_map()
    }
    if( '#contacts_map' === window.location.hash  ) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        contacts_map()
    }

    if( '#overview' === window.location.hash  ) {
        jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_overview()
    }


})



function write_training_cluster_map() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    tAPI.heatmap()
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

window.validate_timer_id = '';
function clear_timer() {
    clearTimeout(window.validate_timer_id);
}
function write_training_choropleth_map() {
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
                    if (dtTrainingMetrics.data[geojson.features[i].properties.id]) {
                        geojson.features[i].properties.value = parseInt(dtTrainingMetrics.data[geojson.features[i].properties.id].count)
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
        load_section( lnglat.lng, lnglat.lat )
    } )

    // drag pan
    map.on('dragend', function() {
        let lnglat = map.getCenter()
        load_section( lnglat.lng, lnglat.lat )
    } )

    // click
    map.on('click', function( e ) {
        load_section( e.lngLat.lng, e.lngLat.lat )
    })
  

    function load_section( lng, lat ) {
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
                                    if ( dtTrainingMetrics.data[geojson.features[i].properties.id] ) {
                                        geojson.features[i].properties.value = parseInt( dtTrainingMetrics.data[geojson.features[i].properties.id].count )
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

    // details on zoom
    // window.zoom_level = 0
    // map.on('zoom', function() {
    //     if ( map.getZoom() >= 1 && map.getZoom() < 2 && window.zoom_level !== 1 ) {
    //         window.zoom_level = 1
    //         document.getElementById('data').innerHTML = '<h1>Level 1</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 2 && map.getZoom() < 3 && window.zoom_level !== 2 ) {
    //         window.zoom_level = 2
    //         document.getElementById('data').innerHTML = '<h1>Level 2</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 3 && map.getZoom() < 4 && window.zoom_level !== 3 ) {
    //         window.zoom_level = 3
    //         document.getElementById('data').innerHTML = '<h1>Level 3</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 4 && map.getZoom() < 5 && window.zoom_level !== 4 ) {
    //         window.zoom_level = 4
    //         document.getElementById('data').innerHTML = '<h1>Level 4</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 5 && map.getZoom() < 6 && window.zoom_level !== 5 ) {
    //         window.zoom_level = 5
    //         document.getElementById('data').innerHTML = '<h1>Level 5</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 6 && map.getZoom() < 7 && window.zoom_level !== 6 ) {
    //         window.zoom_level = 6
    //         document.getElementById('data').innerHTML = '<h1>Level 6</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 7 && map.getZoom() < 8 && window.zoom_level !== 7 ) {
    //         window.zoom_level = 7
    //         document.getElementById('data').innerHTML = '<h1>Level 7</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 8 && map.getZoom() < 9 && window.zoom_level !== 8 ) {
    //         window.zoom_level = 8
    //         document.getElementById('data').innerHTML = '<h1>Level 8</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 9 && map.getZoom() < 10 && window.zoom_level !== 9 ) {
    //         window.zoom_level = 9
    //         document.getElementById('data').innerHTML = '<h1>Level 9</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 10 && map.getZoom() < 11 && window.zoom_level !== 10 ) {
    //         window.zoom_level = 10
    //         document.getElementById('data').innerHTML = '<h1>Level 10</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 11 && map.getZoom() < 12 && window.zoom_level !== 11 ) {
    //         window.zoom_level = 11
    //         document.getElementById('data').innerHTML = '<h1>Level 11</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 12 && map.getZoom() < 13 && window.zoom_level !== 12 ) {
    //         window.zoom_level = 12
    //         document.getElementById('data').innerHTML = '<h1>Level 12</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 13 && map.getZoom() < 14 && window.zoom_level !== 13 ) {
    //         window.zoom_level = 13
    //         document.getElementById('data').innerHTML = '<h1>Level 13</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    //     if ( map.getZoom() >= 14 && window.zoom_level !== 14 ) {
    //         window.zoom_level = 14
    //         document.getElementById('data').innerHTML = '<h1>Level 14</h1>' + 'zoom: ' + map.getZoom() + '<br>center: ' + map.getCenter() + '<br>boundary: ' + map.getBounds()
    //     }
    // })

}

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


window.tAPI = {

    heatmap: ( data ) => makeRequest('POST', 'trainings/heatmap1', data ),

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