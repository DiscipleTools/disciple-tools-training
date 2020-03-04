jQuery(document).ready(function() {
    console.log(dtTrainingMetrics)
    if( ! window.location.hash || '#overview' === window.location.hash  ) {
      jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_overview()
    }
    if( '#basic_map' === window.location.hash) {
      jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_basicmap()
    }
    if( '#heat_map_1' === window.location.hash) {
      jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_heatmap1()
    }
    if( '#heat_map_2' === window.location.hash) {
      jQuery('#metrics-sidemenu').foundation('down', jQuery('#training-menu'));
        write_training_heatmap2()
    }

})

function write_training_overview() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<h2>Overview</h2>`)


}

function write_training_basicmap() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<h2>Basic Map</h2>`)

}

function write_training_heatmap1() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    tAPI.heatmap( { location: 'test'} )
        .done( function( data ) {
            console.log(data)
        })


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

function write_training_heatmap2() {
    let obj = dtTrainingMetrics
    let chart = jQuery('#chart')

    chart.empty().html(`<img src="${obj.plugin_uri}spinner.svg" width="30px" alt="spinner" />`)

    tAPI.heatmap( { location: 'test'} )
        .done( function( data ) {
            console.log(data)
        })


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