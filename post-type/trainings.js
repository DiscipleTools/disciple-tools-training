"use strict"
jQuery(document).ready(function($) {

    let post_id = window.detailsSettings.post_id
    let post_type = window.detailsSettings.post_type
    let post = window.detailsSettings.post_fields
    let field_settings = window.detailsSettings.post_settings.fields


    /* Member List*/
    let memberList = $('.member-list')
    let memberCountInput = $('#member_count')
    let leaderCountInput = $('#leader_count')
    let populateMembersList = ()=>{
        memberList.empty()

        post.members = post.members || []
        post.members.forEach(m=>{
            if ( window.lodash.find( post.leaders || [], {ID: m.ID} ) ){
                m.leader = true
            }
        })
        post.members = window.lodash.sortBy( post.members, ["leader"])
        post.members.forEach(member=>{
            let leaderHTML = '';
            if( member.leader ){
                leaderHTML = `<i class="fi-foot small leader"></i>`
            }
            let memberHTML = `<div class="member-row" style="" data-id="${window.lodash.escape( member.ID )}">
          <div style="flex-grow: 1" class="member-status">
              <i class="fi-torso small"></i>
              <a href="${window.lodash.escape(window.wpApiShare.site_url)}/contacts/${window.lodash.escape( member.ID )}">${window.lodash.escape(member.post_title)}</a>
              ${leaderHTML}
          </div>
          <button class="button clear make-leader member-row-actions" data-id="${window.lodash.escape( member.ID )}">
            <i class="fi-foot small"></i>
          </button>
          <button class="button clear delete-member member-row-actions" data-id="${window.lodash.escape( member.ID )}">
            <i class="fi-x small"></i>
          </button>
        </div>`
            memberList.append(memberHTML)
        })
        if (post.members.length === 0) {
            $("#empty-members-list-message").show()
        } else {
            $("#empty-members-list-message").hide()
        }
        memberCountInput.val( post.member_count )
        leaderCountInput.val( post.leader_count )
        window.masonGrid.masonry('layout')
    }
    populateMembersList()

    $( document ).on( "dt-post-connection-created", function( e, new_post, field_key ){
        if ( field_key === "members" ){
            post = new_post
            populateMembersList()
        }
    } )
    $(document).on("click", ".delete-member", function () {
        let id = $(this).data('id')
        $(`.member-row[data-id="${id}"]`).remove()
        API.update_post( post_type, post_id, {'members': {values:[{value:id, delete:true}]}}).then(groupRes=>{
            post=groupRes
            populateMembersList()
            masonGrid.masonry('layout')
        })
        if( window.lodash.find( post.leaders || [], {ID: id}) ) {
            API.update_post( post_type, post_id, {'leaders': {values: [{value: id, delete: true}]}})
        }
    })
    $(document).on("click", ".make-leader", function () {
        let id = $(this).data('id')
        let remove = false
        let existingLeaderIcon = $(`.member-row[data-id="${id}"] .leader`)
        if( window.lodash.find( post.leaders || [], {ID: id}) || existingLeaderIcon.length !== 0){
            remove = true
            existingLeaderIcon.remove()
        } else {
            $(`.member-row[data-id="${id}"] .member-status`).append(`<i class="fi-foot small leader"></i>`)
        }
        API.update_post( post_type, post_id, {'leaders': {values:[{value:id, delete:remove}]}}).then(groupRes=>{
            post=groupRes
            populateMembersList()
            window.masonGrid.masonry('layout')
        })
    })
    $('.add-new-member').on("click", function () {
        $('#add-new-group-member-modal').foundation('open');
        Typeahead[`.js-typeahead-members`].adjustInputSize()
    })
    $( document ).on( "dt-post-connection-added", function( e, new_post, field_key ){
        post = new_post;
        if ( field_key === "members" ){
            populateMembersList()
        }
    })

    /* end Member List */


    /**
     * Assigned_to
     */
    let assigned_to_input = $(`.js-typeahead-assigned_to`)
    if ( assigned_to_input.length ){
      $.typeahead({
          input: '.js-typeahead-assigned_to',
          minLength: 0,
          maxItem: 0,
          accent: true,
          searchOnFocus: true,
          source: TYPEAHEADS.typeaheadUserSource(),
          templateValue: "{{name}}",
          template: function (query, item) {
              return `<div class="assigned-to-row" dir="auto">
          <span>
              <span class="avatar"><img style="vertical-align: text-bottom" src="{{avatar}}"/></span>
              ${window.lodash.escape( item.name )}
          </span>
          ${ item.status_color ? `<span class="status-square" style="background-color: ${window.lodash.escape(item.status_color)};">&nbsp;</span>` : '' }
          ${ item.update_needed && item.update_needed > 0 ? `<span>
            <img style="height: 12px;" src="${window.lodash.escape( window.wpApiShare.template_dir )}/dt-assets/images/broken.svg"/>
            <span style="font-size: 14px">${window.lodash.escape(item.update_needed)}</span>
          </span>` : '' }
        </div>`
          },
          dynamic: true,
          hint: true,
          emptyTemplate: window.lodash.escape(window.wpApiShare.translations.no_records_found),
          callback: {
              onClick: function(node, a, item){
                  API.update_post( post_type, post_id, {assigned_to: 'user-' + item.ID}).then(function (response) {
                      window.lodash.set(post, "assigned_to", response.assigned_to)
                      assigned_to_input.val(post.assigned_to.display)
                      assigned_to_input.blur()
                  }).catch(err => { console.error(err) })
              },
              onResult: function (node, query, result, resultCount) {
                  let text = TYPEAHEADS.typeaheadHelpText(resultCount, query, result)
                  $('#assigned_to-result-container').html(text);
              },
              onHideLayout: function () {
                  $('.assigned_to-result-container').html("");
              },
              onReady: function () {
                  if (window.lodash.get(post,  "assigned_to.display")){
                      $('.js-typeahead-assigned_to').val(post.assigned_to.display)
                  }
              }
          },
      });
    }
    $('.search_assigned_to').on('click', function () {
        assigned_to_input.val("")
        assigned_to_input.trigger('input.typeahead')
        assigned_to_input.focus()
    })

    /*https://www.daterangepicker.com/*/
    function add_datetime_series_picker_listener(){
        $('.dt-datetime-series-picker').daterangepicker({
            singleDatePicker: true,
            timePicker: true,
            timePickerIncrement: 15,
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        })
            .on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD hh:mm:ss a'));

                let date = window.SHAREDFUNCTIONS.convertArabicToEnglishNumbers(picker.startDate.format('YYYY-MM-DD hh:mm:ss a'));

                if (!picker.startDate) {
                    date = " ";//null;
                }
                let id = 'meeting_times'
                $(`#${id}-spinner`).addClass('active')

                let data = {}
                data.meeting_times = []

                let key = $(this).data('key')
                if ( key ) {
                    data.meeting_times.push({ key: key, value: moment.utc(date).unix() })
                } else {
                    data.meeting_times.push({ value: moment.utc(date).unix() })
                }

                window.API.update_post(post_type, post_id, data ).then((resp) => {
                    post = resp
                    window.write_meeting_times_list()
                }).catch(handleAjaxError)
            })
    }

    window.write_meeting_times_list = () => {
        let field = 'meeting_times'
        let list = $(`#edit-${window.lodash.escape( field )}`)

        list.empty()

        if ( typeof post[field] !== 'undefined' ) {
            let times = post[field]
            $.each( window.lodash.orderBy(times, ['timestamp']), function(i,v){
                list.append(`
                    <div class="input-group">
                        <input id="${window.lodash.escape( v.key )}"
                               type="text"
                               data-field="${window.lodash.escape( field )}"
                               data-key="${window.lodash.escape( v.key )}"
                               value="${window.SHAREDFUNCTIONS.formatDate( window.lodash.escape( v.timestamp ) )}"
                               class="dt-datetime-series-picker input-group-field" />
                        <div class="input-group-button">
                            <button class="button alert input-height delete-button-style datetime-series-delete-button delete-button" data-field="${window.lodash.escape( field )}" data-key="${window.lodash.escape( v.key )}">&times;</button>
                        </div>
                    </div>
               `)
            })
        } else {
            list.append(`
            <div class="input-group">
                <input type="text" data-field="${window.lodash.escape( field )}" class="dt-datetime-series-picker input-group-field" />
                <div class="input-group-button">
                    <button class="button alert input-height delete-button-style datetime-series-delete-button delete-button new-${window.lodash.escape( field )}" data-key="new" data-field="${window.lodash.escape( field )}">&times;</button>
                </div>
            </div>
            `)
        }

        add_datetime_series_picker_listener()

        $(`#${field}-spinner`).removeClass('active')

        masonGrid.masonry({
            itemSelector: '.grid-item',
            percentPosition: true
        });
    }

    window.write_meeting_times_list()


    // Clicking the plus sign next to the field label
    $('button.add-time-button').on('click', e => {
        const field = $(e.currentTarget).data('list-class')
        const $list = $(`#edit-${window.lodash.escape( field )}`)

        $list.prepend(`<div class="input-group">
            <input type="text" data-field="${window.lodash.escape( field )}" class="dt-datetime-series-picker input-group-field" />
                <div class="input-group-button">
                    <button class="button alert input-height delete-button-style datetime-series-delete-button delete-button new-${window.lodash.escape( field )}" data-key="new" data-field="${window.lodash.escape( field )}">&times;</button>
                </div>
            </div>`)

        add_datetime_series_picker_listener()
        //leave at the end of this file
        masonGrid.masonry({
            itemSelector: '.grid-item',
            percentPosition: true
        });

    })
    $(document).on('click', '.datetime-series-delete-button', function(){
        let field = $(this).data('field')
        let key = $(this).data('key')
        let update = { delete:true }
        if ( key === 'new' ){
            $(this).parent().parent().remove()

            add_starter_meeting_times_field( field )
            add_datetime_series_picker_listener()

            $(`#${field}-spinner`).removeClass('active')
            masonGrid.masonry({
                itemSelector: '.grid-item',
                percentPosition: true
            });
        } else if ( key ){
            $(`#${field}-spinner`).addClass('active')
            update["key"] = key;
            API.update_post(post_type, post_id, { [field]: [update]}).then((updatedContact)=>{
                post = updatedContact

                $(this).parent().parent().remove()

                add_starter_meeting_times_field( field )
                add_datetime_series_picker_listener()

                $(`#${field}-spinner`).removeClass('active')
                masonGrid.masonry({
                    itemSelector: '.grid-item',
                    percentPosition: true
                });

            }).catch(handleAjaxError)
        }

    })

    function add_starter_meeting_times_field( field ){
        let list = $(`#edit-${window.lodash.escape( field )}`)
        if ( list.children().length === 0 ){
            list.append(`<div class="input-group">
                        <input type="text" data-field="${window.lodash.escape( field )}" class="dt-datetime-series-picker input-group-field" />
                        <div class="input-group-button">
                        <button class="button alert input-height delete-button-style datetime-series-delete-button delete-button new-${window.lodash.escape( field )}" data-key="new" data-field="${window.lodash.escape( field )}">&times;</button>
                        </div></div>`)

            add_datetime_series_picker_listener()
        }
    }

    $(document).on('blur', 'input.dt-communication-channel', function(){
        let field_key = $(this).data('field')
        let value = $(this).val()
        let id = $(this).attr('id')
        let update = { value }
        if ( id ) {
            update["key"] = id;
        }
        $(`#${field_key}-spinner`).addClass('active')
        API.update_post(post_type, post_id, { [field_key]: [update]}).then((updatedContact)=>{
            $(`#${field_key}-spinner`).removeClass('active')
            let key = window.lodash.last(updatedContact[field_key]).key
            $(this).attr('id', key)
            if ( $(this).next('div.input-group-button').length === 1 ) {
                console.log('present')
                $(this).parent().find('.datetime-series-delete-button').data('key', key)
            } else {
                console.log('new x')
                $(this).parent().append(`<div class="input-group-button">
                    <button class="button alert delete-button-style input-height datetime-series-delete-button delete-button" data-key="${window.lodash.escape( key )}" data-field="${window.lodash.escape( field_key )}">&times;</button>
                </div>`)
            }
            post = updatedContact

            masonGrid.masonry({
                itemSelector: '.grid-item',
                percentPosition: true
            });
        }).catch(handleAjaxError)
    })

})
