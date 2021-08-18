jQuery(document).ready(function() {
    if ( window.wpApiShare.url_path.startsWith( 'metrics/trainings/tree' ) ) {
    project_training_tree()
    }

    function project_training_tree() {
    "use strict";
    let chart = jQuery('#chart')
    jQuery('#metrics-sidemenu').foundation('down', jQuery('#trainings-menu'));

    let translations = dtMetricsProject.data.translations

    chart.empty().html(`
          <span class="section-header">${window.lodash.escape(translations.title_training_tree)}</span><hr>
           <div class="grid-x grid-padding-x">
               <div class="cell">
                   <span>
                      <button class="button action-button" id="only_multiplying">${window.lodash.escape(translations.show_multiplying)/*Show Multiplying*/}</button>
                   </span>
                   <span>
                      <button class="button hollow action-button" id="show_all">${window.lodash.escape(translations.show_all)/*Show All*/}</button>
                   </span>
              </div>
              <div class="cell">
                  <div class="scrolling-wrapper" id="generation_map"><span class="loading-spinner active"></span></div>
              </div>
          </div>
        `)

        jQuery('#only_multiplying').on('click', function(e){
            load_tree('only_multiplying')
        })
        jQuery('#show_all').on('click', function(e){
            load_tree('show_all')
        })

        load_tree('only_multiplying')
    }

    function load_tree(action) {
        let gen_map = jQuery('#generation_map')
        gen_map.html(`<span class="loading-spinner active"></span>`)

        jQuery('.action-button').addClass('hollow')
        jQuery('#'+action).removeClass('hollow')

        makeRequest('POST', 'metrics/trainings/tree', { action: action } )
            .then(response => {
                // console.log(response)
                gen_map.empty().html(response)
                jQuery('#generation_map li:last-child').addClass('last');
            })
    }
})

