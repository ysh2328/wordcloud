<?php if (  (! isset( $_GET['merchant_return_link'] ) ) && (! isset( $_GET['payed_booking'] ) ) && (!function_exists ('get_option')  )  ) { die('You do not have permission to direct access to this file !!!'); }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  S u p p o r t    f u n c t i o n s       ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function wpdevbk_db_prepare($sql_req){                                      // Compatibility with WordPress 3.5 function
         global $wpdb;
         return $wpdb->prepare( $sql_req, array() );
    }
    
    // Change date format
    function wpdevbk_get_date_in_correct_format( $dt, $date_format = false, $time_format = false ) {

        if ($date_format === false)
            $date_format = get_bk_option( 'booking_date_format');
        if ($time_format === false)
            $time_format = get_bk_option( 'booking_time_format');
        if (empty($date_format)) $date_format = "m / d / Y, D";
        if (empty($time_format)) $time_format = 'h:i a';
        $my_time = date('H:i:s' , mysql2date('U',$dt) );
        if ($my_time == '00:00:00')     $time_format='';
        $bk_date = date_i18n($date_format  , mysql2date('U',$dt));
        $bk_time = date_i18n(' ' . $time_format  , mysql2date('U',$dt));
        if ($bk_time == ' ') $bk_time = '';

        return array($bk_date, $bk_time);
    }

    // Check if nowday is tommorow from previosday
    function wpdevbk_is_next_day($nowday, $previosday) {

        if ( empty($previosday) ) return false;

        $nowday_d = (date('m.d.Y',  mysql2date('U', $nowday ))  );
        $prior_day = (date('m.d.Y',  mysql2date('U', $previosday ))  );
        if ($prior_day == $nowday_d)    return true;                // if its the same date


        $previos_array = (date('m.d.Y',  mysql2date('U', $previosday ))  );
        $previos_array = explode('.',$previos_array);
        $prior_day =  date('m.d.Y' , mktime(0, 0, 0, $previos_array[0], ($previos_array[1]+1), $previos_array[2] ));


        if ($prior_day == $nowday_d)    return true;                // tommorow
        else                            return false;               // no
    }

    // Transform the REQESTS parameters (GET and POST) into URL
    function get_params_in_url( $exclude_prms = array(), $only_these_parameters = false ){

        //$url_start = 'admin.php?';                          //$url_start = 'admin.php?page='. WPDEV_BK_PLUGIN_DIRNAME . '/'. WPDEV_BK_PLUGIN_FILENAME. 'wpdev-booking';
        $my_page = WPDEV_BK_PLUGIN_DIRNAME . '/'. WPDEV_BK_PLUGIN_FILENAME . 'wpdev-booking';
        if ( isset($_GET['page']) ) $my_page = $_GET['page'] ;
        $url_start     = 'admin.php?page=' . $my_page . '&' ;
        $exclude_prms[]='page';
        foreach ($_REQUEST as $prm_key => $prm_value) {
            if ( ! in_array($prm_key, $exclude_prms ) )
                    if ( ($only_these_parameters === false) || ( in_array($prm_key, $only_these_parameters ) ) )
                $url_start .= $prm_key .'=' . $prm_value . '&' ;
        }
        $url_start = substr($url_start, 0, -1);
        return $url_start ;
    }

    // Load default filter parameters only for the initial loading of page.     // ShiftP
    function wpdevbk_get_default_bk_listing_filter_set_to_params( $filter_name ) {

        $wpdevbk_saved_filter  = get_user_option( 'booking_listing_filter_' . $filter_name ) ;

        $exclude_options_from_saved_params = array('tab', 'tab_cvm', 'view_mode');         // Exclude some parameters from the saved Default parameters - the values of these parameters are loading from General Booking Settings page or from the request.

        // Get here default selected tab saved in a General Booking Settings page
        if (! isset($_REQUEST['tab'])) {  
            $booking_default_toolbar_tab = get_bk_option( 'booking_default_toolbar_tab');
            if ( $booking_default_toolbar_tab !== false) {
                $wpdevbk_filter_params[ 'tab' ] = $booking_default_toolbar_tab;  // 'filter' / 'actions' ;
                $_REQUEST['tab'] = $booking_default_toolbar_tab; ;                    // Set to REQUEST
            }
        }

        // Get here default View mode saved in a General Booking Settings page
        if (! isset($_REQUEST['view_mode'])) { 
            $booking_default_view_mode = get_bk_option( 'bookings_listing_default_view_mode');
            if ( $booking_default_view_mode !== false) {
                $wpdevbk_filter_params[ 'view_mode' ] = $booking_default_view_mode;  // 'vm_calendar' / 'vm_listing' ;
                $_REQUEST['view_mode'] = $booking_default_view_mode;                     // Set to REQUEST
            }
        }


        if ($wpdevbk_saved_filter !== false) {

            $wpdevbk_saved_filter = str_replace('admin.php?', '', $wpdevbk_saved_filter);

            $wpdevbk_saved_filter = explode('&',$wpdevbk_saved_filter);
            $wpdevbk_filter_params = array();
            foreach ($wpdevbk_saved_filter as $bkfilter) {
                $bkfilter_key_value = explode('=',$bkfilter);
                if ( ! in_array($bkfilter_key_value[0], $exclude_options_from_saved_params) ) { // Exclude some parameters from the saved Default parameters - the values of these parameters are loading from General Booking Settings page or from the request.
                    $wpdevbk_filter_params[ $bkfilter_key_value[0] ] = trim($bkfilter_key_value[1]);
                }
            }

            if (! isset($_REQUEST['wh_approved'])) {                            // We are do not have approved or pending value, so its mean that user open the page as default, without clicking on Filter apply.
                foreach ($wpdevbk_filter_params as $filter_key => $filter_value) {
                    $_REQUEST[$filter_key] = $filter_value ;                    // Set to REQUEST
                }
            }

        }
    }


    function wpdevbk_get_str_from_dates_short($bk_dates_short, $is_approved = false, $bk_dates_short_id = array() , $booking_types = array() ){
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Get SHORT Dates showing data ////////////////////////////////////////////////////////////////////////////////////////////////////
            $short_dates_content = '';
            $dcnt = 0;
            foreach ($bk_dates_short as $dt) {
                if ($dt == '-') {       $short_dates_content .= '<span class="date_tire"> - </span>';
                } elseif ($dt == ',') { $short_dates_content .= '<span class="date_tire">, </span>';
                } else {
                    $short_dates_content .= '<a href="javascript:;" class="field-booking-date ';
                    if ($is_approved) $short_dates_content .= ' approved';
                    $short_dates_content .= '">';

                    $bk_date = wpdevbk_get_date_in_correct_format($dt);
                    $short_dates_content .= $bk_date[0];
                    $short_dates_content .= '<sup class="field-booking-time">'. $bk_date[1] .'</sup>';

                     // BL
                     if (class_exists('wpdev_bk_biz_l')) {
                         if (! empty($bk_dates_short_id[$dcnt]) ) {
                             $bk_booking_type_name_date   = $booking_types[$bk_dates_short_id[$dcnt]]->title;        // Default
                             if (strlen($bk_booking_type_name_date)>19) $bk_booking_type_name_date = substr($bk_booking_type_name_date, 0,  13) . '...' . substr($bk_booking_type_name_date, -3 );

                             $short_dates_content .= '<sup class="field-booking-time date_from_dif_type"> '.$bk_booking_type_name_date.'</sup>';
                         }
                     }
                    $short_dates_content .= '</a>';
                }
                $dcnt++;
            }

            return $short_dates_content;
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Control elements      ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function wpdevbk_selectbox_normal_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block){

        if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
        else                                  $wpdevbk_value = '';
        $wpdevbk_selector_default = array_search($wpdevbk_value, $wpdevbk_selectors);
        if ($wpdevbk_selector_default === false) $wpdevbk_selector_default = current($wpdevbk_selectors);
          ?>
          <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
            <div class="inline controls">
                <div class="btn-group">
                    <select class="span8 chzn-select" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" data-placeholder="<?php echo $wpdevbk_help_block; ?>"                       
                             >
                      <?php
                      foreach ($wpdevbk_selectors as $key=>$value) {
                        if ($value != 'divider') {
                            ?><option <?php if ($wpdevbk_value == $value ) echo ' selected="SELECTED" '; ?> 
                                <?php if (strpos($key , '&nbsp;') === false) echo ' style="font-weight:bold;" '; ?>
                                value="<?php echo $value; ?>"><?php echo $key; ?></option><?php
                        } else {
                            ?><?php
                        }
                      } ?>
                  </select>
                </div>                
                <p class="help-block" style="margin-top: 0px;"><?php echo $wpdevbk_help_block; ?></p>
            </div>
          </div>
            <script type="text/javascript">
              jQuery(document).ready( function(){
                jQuery("#<?php echo $wpdevbk_id; ?>").chosen({no_results_text: "No results matched"});
              });
            </script>
            <style type="text/css">
                .bookingpage .wpdevbk a.chzn-single {
                    height: 25px;
                    margin-top: 1px;
                }
            </style>
        <?php
    }


    function wpdevbk_selectbox_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block, $wpdevbk_default_value = ''){

            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else                                  $wpdevbk_value = $wpdevbk_default_value;
            $wpdevbk_selector_default = array_search($wpdevbk_value, $wpdevbk_selectors);
            if ($wpdevbk_selector_default === false) {
                $wpdevbk_selector_default = key($wpdevbk_selectors);
                $wpdevbk_selector_default_value = current($wpdevbk_selectors);
            } else $wpdevbk_selector_default_value = $wpdevbk_value;
          ?>
          <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
            <div class="inline controls">
                <div class="btn-group">
                  <a href="#" data-toggle="dropdown" id="<?php echo $wpdevbk_id;?>_selector" class="btn dropdown-toggle"><?php echo $wpdevbk_selector_default; ?> &nbsp; <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                      <?php
                      foreach ($wpdevbk_selectors as $key=>$value) {
                        if ($value != 'divider') {
                          ?><li><a href="#" onclick="javascript:jQuery('#<?php echo $wpdevbk_id;?>_selector').html(jQuery(this).html() + ' &nbsp; <span class=&quot;caret&quot;></span>');jQuery('#<?php echo $wpdevbk_id; ?>').val('<?php echo $value; ?>');" ><?php echo $key; ?></a></li><?php
                        } else { ?><li class="divider"></li><?php }
                      } ?>
                  </ul>
                  <input type="hidden" value="<?php echo $wpdevbk_selector_default_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />
                </div>
              <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
            </div>
          </div>
        <?php
    }


    function wpdevbk_checkboxbutton_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block){

            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else                                  $wpdevbk_value = '';
            $wpdevbk_selector_default = array_search($wpdevbk_value, $wpdevbk_selectors);
            if ($wpdevbk_selector_default === false) $wpdevbk_selector_default = current($wpdevbk_selectors);
          ?>
          <div class="control-group" style="float:left;">
           <!--label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?>:</label-->
           <div class="inline controls">
            
            <a href="#" class="btn" data-toggle="button" name="checkboxbutton_<?php echo $wpdevbk_id; ?>" id="checkboxbutton_<?php echo $wpdevbk_id; ?>"
               onclick="javascript:if (jQuery(this).attr('class').indexOf('active')>0) { jQuery('#<?php echo $wpdevbk_id; ?>').val('<?php echo $wpdevbk_selectors[0]; ?>'); } else { jQuery('#<?php echo $wpdevbk_id; ?>').val('<?php echo $wpdevbk_selectors[1]; ?>'); }; "

               ><?php echo $wpdevbk_control_label; ?></a>
            
            <script type="text/javascript">
                jQuery('#checkboxbutton_<?php echo $wpdevbk_id; ?>').button();
                <?php if ($wpdevbk_value == '1') {  // Press the button ?>
                    jQuery('#checkboxbutton_<?php echo $wpdevbk_id; ?>').button('toggle');
                <?php } ?>
            </script>

            <input type="hidden" value="<?php echo $wpdevbk_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />

            <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
           </div>
          </div>
        <?php
    }


    function wpdevbk_text_filter($wpdevbk_id, $wpdevbk_control_label, $wpdevbk_help_block) {

            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else                                  $wpdevbk_value = '';
        ?>
          <div class="control-group" style="float:left;">
           <!--label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?>:</label-->
           <div class="inline controls">
            <input type="text" class="span2"  placeholder="<?php echo $wpdevbk_control_label; ?>" value="<?php echo $wpdevbk_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />
            <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
           </div>
          </div>
        <?php
    }


    function wpdevbk_text_from_to_filter($wpdevbk_id, $wpdevbk_control_label, $wpdevbk_placeholder, $wpdevbk_help_block, $wpdevbk_id2, $wpdevbk_control_label2, $wpdevbk_placeholder2, $wpdevbk_help_block2, $wpdevbk_width, $input_append = '') {

            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else                                  $wpdevbk_value = '';
            if (isset($_REQUEST[$wpdevbk_id2]))   $wpdevbk_value2 = $_REQUEST[$wpdevbk_id2];
            else                                  $wpdevbk_value2 = '';
        ?>
          <div class="control-group" style="float:left;">
           <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
           <div class="inline controls">
            <?php if ( $input_append !== '' ) { ?><div class="input-append"><?php } ?>
            <input type="text" class="<?php echo $wpdevbk_width; ?>"  placeholder="<?php echo $wpdevbk_placeholder; ?>" value="<?php echo $wpdevbk_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />
            <?php if ( $input_append !== '' ) { ?><span class="add-on"><?php echo $input_append ?></span></div><?php } ?>
            <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
           </div>
          </div>
           <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id2; ?>" class="control-label" style="margin-left: -5px; text-align: left; width: 10px;"><?php echo $wpdevbk_control_label2; ?></label>
            <div class="inline controls">
            <?php if ( $input_append !== '' ) { ?><div class="input-append"><?php } ?>
                <input type="text" class="<?php echo $wpdevbk_width; ?>"  placeholder="<?php echo $wpdevbk_placeholder2; ?>" value="<?php echo $wpdevbk_value2; ?>" id="<?php echo $wpdevbk_id2; ?>" name="<?php echo $wpdevbk_id2; ?>" />
            <?php if ( $input_append !== '' ) { ?><span class="add-on"><?php echo $input_append ?></span></div><?php } ?>
            <p class="help-block"><?php echo $wpdevbk_help_block2; ?></p>
           </div>
          </div>
        <?php
    }


    function wpdevbk_dates_selection_for_filter($wpdevbk_id,  $wpdevbk_id2,
                                                $wpdevbk_control_label,    $wpdevbk_help_block,
                                                $wpdevbk_width, $input_append = '',
                                                $exclude_items = array() , $default_item = 0) {
        
            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else  {                               $wpdevbk_value = $default_item; }
            if (isset($_REQUEST[$wpdevbk_id2]))   $wpdevbk_value2 = $_REQUEST[$wpdevbk_id2];
            else                                  $wpdevbk_value2 = '';

            $dates_interval = array(  1 => '1' . ' ' . __('day', 'wpdev-booking') ,
                                      2 => '2' . ' ' . __('days', 'wpdev-booking') ,
                                      3 => '3' . ' ' . __('days', 'wpdev-booking') ,
                                      4 => '4' . ' ' . __('days', 'wpdev-booking') ,
                                      5 => '5' . ' ' . __('days', 'wpdev-booking') ,
                                      6 => '6' . ' ' . __('days', 'wpdev-booking') ,
                                      7 => '1' . ' ' . __('week', 'wpdev-booking') ,
                                      14 => '2' . ' ' . __('weeks', 'wpdev-booking') ,
                                      30 => '1' . ' ' . __('month', 'wpdev-booking') ,
                                      60 => '2' . ' ' . __('months', 'wpdev-booking') ,
                                      90 => '3' . ' ' . __('months', 'wpdev-booking') ,
                                      183 => '6' . ' ' . __('months', 'wpdev-booking') ,
                                      365 => '1' . ' ' . __('Year', 'wpdev-booking')  );

            $filter_labels = array(
                                __('Actual dates', 'wpdev-booking'),
                                __('Today', 'wpdev-booking'),
                                __('Previous dates', 'wpdev-booking'),
                                __('All dates', 'wpdev-booking'),
                                __('Some Next days', 'wpdev-booking'),
                                __('Some Prior days', 'wpdev-booking'),
                                __('Fixed dates interval', 'wpdev-booking'),
                               );
        ?>
            <script type="text/javascript">

                function wpdevbk_days_selection_in_filter( primary_field, secondary_field, primary_value, secondary_value ) {

                    if (primary_value == '0') {         // Actual  = '', ''
                        jQuery('#' + primary_field   ).val('0');
                        jQuery('#' + secondary_field ).val('');
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[0]); ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '1') {  // Today
                        jQuery('#' + primary_field   ).val('1');
                        jQuery('#' + secondary_field ).val('');
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[1]); ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '2') {  // Previous
                        jQuery('#' + primary_field   ).val('2');
                        jQuery('#' + secondary_field ).val('');
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[2]); ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '3') { // All
                        jQuery('#' + primary_field   ).val('3');
                        jQuery('#' + secondary_field ).val('');
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[3]); ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '4') { // Next
                        jQuery('#' + primary_field   ).val('4');
                        jQuery('#' + secondary_field ).val(secondary_value);
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[4]) ; ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '5') { // Prior
                        jQuery('#' + primary_field   ).val('5');
                        jQuery('#' + secondary_field ).val(secondary_value);
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[5]) ; ?>' + ' &nbsp; <span class="caret"></span>');
                    } else if (primary_value == '6') { // Fixed
                        jQuery('#' + primary_field   ).val(secondary_value[0]);
                        jQuery('#' + secondary_field ).val(secondary_value[1]);
                        jQuery('#'+primary_field+'_selector').html( '<?php echo esc_js($filter_labels[6]) ; ?>' + ' &nbsp; <span class="caret"></span>');
                    }
                    jQuery('#' + primary_field+ '_container').hide();
                }

            </script>

          <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
            <div class="inline controls">
                <input type="hidden" value="<?php echo $wpdevbk_value; ?>"  id="<?php echo $wpdevbk_id; ?>"  name="<?php echo $wpdevbk_id; ?>" />
                <input type="hidden" value="<?php echo $wpdevbk_value2; ?>" id="<?php echo $wpdevbk_id2; ?>" name="<?php echo $wpdevbk_id2; ?>" />
                <div class="btn-group">
                    <a onclick="javascript:jQuery('#<?php echo $wpdevbk_id; ?>_container').show();" id="<?php echo $wpdevbk_id; ?>_selector" data-toggle="dropdown"  class="btn dropdown-toggle" href="#"><?php
                    if ( isset($_REQUEST[ $wpdevbk_id ]) ) {
                        if ( $_REQUEST[ $wpdevbk_id ] == '0' ) echo $filter_labels[0];
                        else if ( $_REQUEST[ $wpdevbk_id ] == '1' ) echo $filter_labels[1];
                        else if ( $_REQUEST[ $wpdevbk_id ] == '2' ) echo $filter_labels[2];
                        else if ( $_REQUEST[ $wpdevbk_id ] == '3' ) echo $filter_labels[3];
                        else if ( $_REQUEST[ $wpdevbk_id ] == '4' ) echo $filter_labels[4];
                        else if ( $_REQUEST[ $wpdevbk_id ] == '5' ) echo $filter_labels[5];
                        else echo $filter_labels[6];
                    } else {
                        echo $filter_labels[ $default_item ];
                    }
                    ?> &nbsp; <span class="caret"></span></a>
                    <ul class="dropdown-menu" style="display:none;" id="<?php echo $wpdevbk_id; ?>_container" >
                        <?php   if ( ! in_array(0, $exclude_items ) ) { ?>
                        <li><a onclick="javascript:wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '0' , '' );" href="#"><?php echo $filter_labels[0]; ?></a></li>
                        <?php } if ( ! in_array(1, $exclude_items ) ) { ?>
                        <li><a onclick="javascript:wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '1' , '' );" href="#"><?php echo $filter_labels[1]; ?></a></li>
                        <?php } if ( ! in_array(2, $exclude_items ) ) { ?>
                        <li><a onclick="javascript:wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '2' , '' );" href="#"><?php echo $filter_labels[2]; ?></a></li>
                        <?php } if ( ! in_array(3, $exclude_items ) ) { ?>
                        <li><a onclick="javascript:wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '3' , '' );" href="#"><?php echo $filter_labels[3]; ?></a></li>
                        <?php } ?>
                        <li class="divider"></li>
                        <?php   if ( ! in_array(4, $exclude_items ) ) { ?>
                        <li><div style="margin-left:15px;"> 
                                <input <?php if ( isset($_REQUEST[ $wpdevbk_id . 'days_interval_Radios']) ) if ( $_REQUEST[ $wpdevbk_id . 'days_interval_Radios'] == 'next' ) echo ' checked="CHECKED" ';  ?>
                                    type="radio" value="next" id="<?php echo $wpdevbk_id; ?>days_interval1" name="<?php echo $wpdevbk_id; ?>days_interval_Radios" style="margin:-2px 5px 0px -5px;">
                                <span><?php _e('Next', 'wpdev-booking'); ?>: </span>
                                <select class="span1" style="width:85px;" id="<?php echo $wpdevbk_id; ?>next" name="<?php echo $wpdevbk_id; ?>next" >
                                  <?php
                                  foreach ($dates_interval as $key=>$value) {
                                    if ($value != 'divider') {
                                        ?><option <?php if ( isset($_REQUEST[ $wpdevbk_id . 'next']) ) if ( $_REQUEST[ $wpdevbk_id . 'next'] == $key ) echo ' selected="SELECTED" '; ?>
                                            value="<?php echo $key; ?>"><?php echo $value; ?></option><?php
                                    }
                                  }
                                  ?>
                                </select>
                            </div></li>
                        <?php } if ( ! in_array(5, $exclude_items ) ) { ?>
                        <li><div style="margin-left:15px;">
                               <input  <?php if ( isset($_REQUEST[ $wpdevbk_id . 'days_interval_Radios']) ) if ( $_REQUEST[ $wpdevbk_id . 'days_interval_Radios'] == 'prior' ) echo ' checked="CHECKED" ';  ?>
                                    type="radio" value="prior" id="<?php echo $wpdevbk_id; ?>days_interval2" name="<?php echo $wpdevbk_id; ?>days_interval_Radios" style="margin:-2px 5px 0px -5px;">
                                <span><?php _e('Prior', 'wpdev-booking'); ?>: </span>
                                <select class="span1" style="width:85px;" id="<?php echo $wpdevbk_id; ?>prior" name="<?php echo $wpdevbk_id; ?>prior" >
                                  <?php
                                  foreach ($dates_interval as $key=>$value) {
                                    if ($value != 'divider') {
                                        ?><option <?php if ( isset($_REQUEST[ $wpdevbk_id . 'prior']) ) if ( $_REQUEST[ $wpdevbk_id . 'prior'] == '-'.$key ) echo ' selected="SELECTED" '; ?>
                                            value="-<?php echo $key; ?>"><?php echo $value; ?></option><?php
                                    }
                                  }
                                  ?>
                                </select>
                            </div></li>
                        <?php } if ( ! in_array(6, $exclude_items ) ) { ?>
                        <li>    
                            <input  <?php if ( isset($_REQUEST[ $wpdevbk_id . 'days_interval_Radios']) ) if ( $_REQUEST[ $wpdevbk_id . 'days_interval_Radios'] == 'fixed' ) echo ' checked="CHECKED" ';  ?>
                                    type="radio"  value="fixed" id="<?php echo $wpdevbk_id; ?>days_interval3" name="<?php echo $wpdevbk_id; ?>days_interval_Radios" style="margin:0 0 0 10px;">
                            <div style="margin-left:30px;margin-top:-17px;">
                                <div><?php _e('Check-in', 'wpdev-booking'); ?>: : </div>
                                <div class="input-append">
                                    <input style="width:100px;" type="text" class="span2<?php echo $wpdevbk_width; ?> wpdevbk-filters-section-calendar"  placeholder="<?php echo '2012-02-25'; ?>"
                                           value="<?php if ( isset($_REQUEST[ $wpdevbk_id . 'fixeddates']) )  echo $_REQUEST[ $wpdevbk_id . 'fixeddates']; ?>"  id="<?php echo $wpdevbk_id; ?>fixeddates"  name="<?php echo $wpdevbk_id; ?>fixeddates" />
                                    <span class="add-on"><?php echo $input_append ?></span>
                                </div>
                                <div style="margin-top: 10px;"><?php _e('Check-out', 'wpdev-booking'); ?>: : </div>
                                <div class="input-append">
                                    <input style="width:100px;" type="text" class="span2<?php echo $wpdevbk_width; ?> wpdevbk-filters-section-calendar"  placeholder="<?php echo '2012-02-25'; ?>"
                                           value="<?php if ( isset($_REQUEST[ $wpdevbk_id2 . 'fixeddates']) )  echo $_REQUEST[ $wpdevbk_id2 . 'fixeddates']; ?>"  id="<?php echo $wpdevbk_id2; ?>fixeddates"  name="<?php echo $wpdevbk_id2; ?>fixeddates" />
                                    <span class="add-on"><?php echo $input_append ?></span>
                                </div>
                            </div>
                        </li>
                        <?php }  ?>
                        <li class="divider"></li>
                        <li style="margin: 0;padding: 0 5px;text-align: right;">
                            <div class="btn-toolbar" style="margin:0px;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary"
                                    onclick="javascript:
                                    var rad_val = jQuery('input:radio[name=<?php echo $wpdevbk_id; ?>days_interval_Radios]:checked').val();
                                    if (rad_val == 'next') wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '4' , jQuery('#<?php echo $wpdevbk_id; ?>next').val() );
                                    if (rad_val == 'prior') wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '5' , jQuery('#<?php echo $wpdevbk_id; ?>prior').val() );
                                    if (rad_val == 'fixed') wpdevbk_days_selection_in_filter( '<?php echo $wpdevbk_id; ?>', '<?php echo $wpdevbk_id2; ?>', '6' , [ jQuery('#<?php echo $wpdevbk_id; ?>fixeddates').val(), jQuery('#<?php echo $wpdevbk_id2; ?>fixeddates').val()  ]  );
                                "    ><?php _e('Apply', 'wpdev-booking'); ?></button>
                            </div><div class="btn-group">
                                <button type="button" class="btn"
                                    onclick="javascript: jQuery('#<?php echo $wpdevbk_id; ?>_container').hide();"
                                ><?php _e('Close', 'wpdev-booking'); ?></button>
                              </div>
                            </div>
                        </li>
                    </ul>
 
                </div>
              <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
            </div>
          </div>
        <?php
    }



    function wpdevbk_selection_and_custom_text_for_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block, $wpdevbk_default_value = '') {

            if (isset($_REQUEST[$wpdevbk_id]))    $wpdevbk_value = $_REQUEST[$wpdevbk_id];
            else                                  $wpdevbk_value = $wpdevbk_default_value;
            $wpdevbk_selector_default = array_search($wpdevbk_value, $wpdevbk_selectors);
            if ($wpdevbk_selector_default === false) {
                    $wpdevbk_selector_default = $wpdevbk_value;//key($wpdevbk_selectors);
                    $wpdevbk_selector_default_value = $wpdevbk_value;//current($wpdevbk_selectors);
            } else $wpdevbk_selector_default_value = $wpdevbk_value;
        ?>
          <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
            <div class="inline controls">
                <div class="btn-group">
                  <a onclick="javascript:jQuery('#<?php echo $wpdevbk_id; ?>_container').show();" id="<?php echo $wpdevbk_id;?>_selector" class="btn dropdown-toggle"  href="#" data-toggle="dropdown"  ><?php echo $wpdevbk_selector_default; ?> &nbsp; <span class="caret"></span></a>
                  <ul class="dropdown-menu"  id="<?php echo $wpdevbk_id; ?>_container"  style="display:none;"  >
                      <?php
                      foreach ($wpdevbk_selectors as $key=>$value) {
                        if ($value != 'divider') {
                          ?><li><a href="#" onclick="javascript:jQuery('#<?php echo $wpdevbk_id;?>_selector').html(jQuery(this).html() + ' &nbsp; <span class=&quot;caret&quot;></span>');jQuery('#<?php echo $wpdevbk_id; ?>').val('<?php echo $value; ?>');jQuery('#<?php echo $wpdevbk_id; ?>_container').hide();" ><?php echo $key; ?></a></li><?php
                        } else { ?><li class="divider"></li><?php }
                      } ?>


                        <li class="divider"></li>
                        <li style="margin: 0;padding: 0 5px 0 15px;">
                            <div><?php _e('Custom', 'wpdev-booking'); ?>: </div>
                            <input style="width:150px;" type="text"  placeholder=""
                                       value="<?php $pos = strpos($wpdevbk_value, 'group_'); if (( $pos === false ) && ($wpdevbk_value !== 'all'))  echo $wpdevbk_value; ?>"
                                       id="<?php echo $wpdevbk_id; ?>custom"  name="<?php echo $wpdevbk_id; ?>custom" />
                        </li>

                        <li class="divider"></li>
                        <li style="margin: 0;padding: 0 5px;text-align: right;">
                            <div class="btn-toolbar" style="margin:0px;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary"
                                    onclick="javascript:
                                    var custom_val = jQuery('#<?php echo $wpdevbk_id; ?>custom').val();
                                    if (custom_val != '') {
                                        jQuery('#<?php echo $wpdevbk_id; ?>').val( custom_val );
                                        jQuery('#<?php echo $wpdevbk_id;?>_selector').html( custom_val + ' &nbsp; <span class=&quot;caret&quot;></span>');
                                    }
                                    jQuery('#<?php echo $wpdevbk_id; ?>_container').hide();
                                "    ><?php _e('Apply', 'wpdev-booking'); ?></button>
                            </div><div class="btn-group">
                                <button type="button" class="btn"
                                    onclick="javascript: jQuery('#<?php echo $wpdevbk_id; ?>_container').hide();"
                                ><?php _e('Close', 'wpdev-booking'); ?></button>
                              </div>
                            </div>
                        </li>

                  </ul>
                  <input type="hidden" value="<?php echo $wpdevbk_selector_default_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />
                </div>
              <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
            </div>
          </div>
        <?php
    }




    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  T O O L B A R       ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    // Show     V i e w    M o d e      Buttons in the Toolbar
    function wpdevbk_booking_view_mode_buttons() {
        if (! isset($_REQUEST['view_mode'])) $_REQUEST['view_mode'] = 'vm_listing';
        $selected_view_mode = $_REQUEST['view_mode'];
        $bk_admin_url = get_params_in_url( array('view_mode','wh_booking_id') );

        ?><div id="booking-listing-view-mode-buttons" class="btn-group btn-group-vertical" data-toggle="buttons-radio">
                    <a onclick="javascript:;" id="btn_vm_listing"  class="tooltip_top btn" <?php if ($selected_view_mode=='vm_listing') { echo ' data-toggle="button" ' ; } ?>
                       rel="tooltip" data-original-title="<?php  _e('Booking Listing', 'wpdev-booking'); ?>"
                        href="<?php echo $bk_admin_url . '&view_mode=vm_listing'; ?>"
                       ><i class="icon-align-justify"></i></a>
                    <a onclick="javascript:;"  id="btn_vm_calendar"  class="tooltip_bottom btn" <?php if ($selected_view_mode=='vm_calendar') { echo ' data-toggle="button" ' ; } ?>
                       rel="tooltip" data-original-title="<?php  _e('Calendar Overview', 'wpdev-booking'); ?>"
                        href="<?php echo $bk_admin_url . '&view_mode=vm_calendar'; ?>"
                       ><i class="icon-calendar"></i></a>
            </div>
            <script type="text/javascript">
                jQuery('#booking-listing-view-mode-buttons .btn').button();
                jQuery('#btn_<?php echo $selected_view_mode; ?>').button('toggle');
                <?php if ($selected_view_mode=='vm_calendar') { ?>
                    jQuery('#wpdev-booking-general h2:first').html('<?php _e('Booking Calendar - Overview', 'wpdev-booking'); ?>');
                <?php } ?>
            </script><?php
    }


    // Show Help Menu buttons in the top Toolbar
    function wpdevbk_show_help_dropdown_menu_in_top_menu_line() {

        $title = __('Help', 'wpdev-booking'); $my_icon = 'system-help22x22.png'; $my_tab = 'help'; $my_additinal_class= ' nav-tab-right '; ?>

        <?php
        $version = 'free';
        $version = get_bk_version();
        if ( wpdev_bk_is_this_demo() ) $version = 'free';
        if( ( strpos( strtolower(WPDEV_BK_VERSION) , 'multisite') !== false  ) || ($version == 'free' ) )  $multiv = '-multi';
        else                                                                                               $multiv = '';
        //$version = 'free';
        $upgrade_lnk = '';
        if ( ($version == 'personal') )  $upgrade_lnk = "http://wpbookingcalendar.com/upgrade-p" .$multiv;
        if ( ($version == 'biz_s') )     $upgrade_lnk = "http://wpbookingcalendar.com/upgrade-s" .$multiv;
        if ( ($version == 'biz_m') )     $upgrade_lnk = "http://wpbookingcalendar.com/upgrade-m" .$multiv;
        ?>
        <span class="dropdown pull-right">
            <a href="#" data-toggle="dropdown" class="dropdown-toggle nav-tab ">
                <img class="menuicons" src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/<?php echo $my_icon; ?>">
                <?php echo $title; ?> <b class="caret" style="border-top-color: #333333 !important;"></b></a>
          <ul class="dropdown-menu" id="menu1" style="right:0px; left:auto;">
            <li><a href="http://wpbookingcalendar.com/help/" target="_blank"><?php _e('Help', 'wpdev-booking'); ?></a></li>
            <li><a href="http://wpbookingcalendar.com/faq/" target="_blank"><?php _e('FAQ', 'wpdev-booking'); ?></a></li>
            <li><a href="http://wpbookingcalendar.com/support/" target="_blank"><?php _e('Technical Support', 'wpdev-booking'); ?></a></li>
            <?php if ($version == 'free') { ?>
            <li class="divider"></li>
            <li><a href="http://wpbookingcalendar.com/buy/" target="_blank"><?php _e('Purchase', 'wpdev-booking'); ?></a></li>
            <?php } else if ($version != 'biz_l') { ?>
            <li class="divider"></li>
            <li><a href="<?php echo $upgrade_lnk; ?>" target="_blank"><?php _e('Upgrade', 'wpdev-booking'); ?></a></li>
            <?php }  ?>
          </ul>
        </span>
        <?php
    }


    // Show     T A B s    in      t o o l b a r
    function wpdevbk_booking_listings_tabs_in_top_menu_line() {

        $is_only_icons = ! true;
        if ($is_only_icons) echo '<style type="text/css"> #menu-wpdevplugin .nav-tab { padding:4px 2px 6px 32px !important; } </style>';

        if (! isset($_REQUEST['tab'])) $_REQUEST['tab'] = 'filter';
        $selected_title = $_REQUEST['tab'];

        ?>
         <div style="height:1px;clear:both;margin-top:30px;"></div>
         <div id="menu-wpdevplugin">
            <div class="nav-tabs-wrapper">
                <div class="nav-tabs">

                    <?php $title = __('Filter', 'wpdev-booking'); $my_icon = 'Season-64x64.png'; $my_tab = 'filter';  $my_additinal_class= ''; ?>
                    <?php if ($_REQUEST['tab'] == 'filter') {  $slct_a = 'selected'; $selected_title = $title; $selected_icon = $my_icon; } else {  $slct_a = ''; } ?><a class="nav-tab <?php if ($slct_a == 'selected') { echo ' nav-tab-active '; } echo $my_additinal_class; ?>" title="<?php //echo __('Customization of booking form fields','wpdev-booking');  ?>"  href="#" onclick="javascript:jQuery('.visibility_container').hide(); jQuery('#<?php echo $my_tab; ?>').show();jQuery('.nav-tab').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');"><img class="menuicons" src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/<?php echo $my_icon; ?>"><?php  if ($is_only_icons) echo '&nbsp;'; else echo $title; ?></a>

                    <?php $title = __('Actions', 'wpdev-booking'); $my_icon = 'actionservices24x24.png'; $my_tab = 'actions';  $my_additinal_class= ''; ?>
                    <?php if ($_REQUEST['tab'] == 'actions') {  $slct_a = 'selected'; $selected_title = $title; $selected_icon = $my_icon; } else {  $slct_a = ''; } ?><a class="nav-tab <?php if ($slct_a == 'selected') { echo ' nav-tab-active '; } echo $my_additinal_class;  ?>" title="<?php //echo __('Customization of booking form fields','wpdev-booking');  ?>"  href="#" onclick="javascript:jQuery('.visibility_container').hide(); jQuery('#<?php echo $my_tab; ?>').show();jQuery('.nav-tab').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');"><img class="menuicons" src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/<?php echo $my_icon; ?>"><?php  if ($is_only_icons) echo '&nbsp;'; else echo $title; ?></a>

                    <?php wpdevbk_show_help_dropdown_menu_in_top_menu_line(); ?>

                </div>
            </div>
        </div>
        <?php
        
    }


    // Show    T O O L B A R   at top of page
    function wpdevbk_booking_listings_interface_header() {
        ?><div id="booking_listings_interface_header"><?php
            wpdevbk_booking_listings_tabs_in_top_menu_line();

            if (! isset($_REQUEST['tab'])) $_REQUEST['tab'] = 'filter';
            $selected_title = $_REQUEST['tab'];

        ?>
            <div class="booking-submenu-tab-container" style="">
                <div class="nav-tabs booking-submenu-tab-insidecontainer">

                    <div class="visibility_container active" id="filter" style="<?php if ($selected_title == 'filter') { echo 'display:block;'; } else { echo 'display:none;'; }  ?>">
                        <?php wpdevbk_show_booking_filters(); ?>

                        <span id="show_link_advanced_booking_filter" class="tab-bottom tooltip_right" data-original-title="<?php _e('Expand Advanced Filter','wpdev-booking'); ?>"  rel="tooltip"><a href="#" onclick="javascript:jQuery('.advanced_booking_filter').show();jQuery('#show_link_advanced_booking_filter').hide();jQuery('#hide_link_advanced_booking_filter').show();"><i class="icon-chevron-down"></i></a></span>
                        <span id="hide_link_advanced_booking_filter" style="display:none;" class="tab-bottom tooltip_right" data-original-title="<?php _e('Collapse Advanced Filter','wpdev-booking'); ?>" rel="tooltip" ><a href="#"  onclick="javascript:jQuery('.advanced_booking_filter').hide(); jQuery('#hide_link_advanced_booking_filter').hide(); jQuery('#show_link_advanced_booking_filter').show();"><i class="icon-chevron-up"></i></a></span>
                    </div>

                    <div class="visibility_container" id="actions"  style="<?php if ($selected_title == 'actions') { echo 'display:block;'; } else { echo 'display:none;'; }  ?>">
                        <?php wpdev_show_booking_actions(); ?>
                    </div>

                    <div class="visibility_container" id="help"     style="<?php if ($selected_title == 'help') { echo 'display:block;'; } else { echo 'display:none;'; }  ?>">
                    </div>

                </div>
            </div>

            <div class="btn-group" style="position:absolute;right:20px;">
                <input style="vertical-align:bottom;height: 27px;margin-bottom: 13px;" type="checkbox" checked="CHECKED" id="is_send_email_for_pending"
                     data-original-title="<?php _e('Send email notification to customer after approval, cancelation or deletion of bookings'); ?>"  rel="tooltip" class="tooltip_top"
                       />
                <span style="color: #777777;line-height: 36px;text-shadow: 0 1px 0 #FFFFFF;vertical-align: top;" ><?php _e('Emails sending','wpdev-booking') ?></span>
            </div>


            <div style="height:1px;clear:both;margin-top:1px;"></div>
        </div>
        <div style="height:1px;clear:both;margin-top:40px;"></div>
        <?php        
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Filters interface      ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 
    function wpdevbk_show_booking_filters(){
        ?>  <div style="clear:both;height:1px;"></div>
            <div class="wpdevbk-filters-section ">

                    <div style="position: absolute; right: 15px;top: 25px;" >
                    <form  name="booking_filters_formID" action="" method="post" id="booking_filters_formID" class=" form-search">
                        <?php if (isset($_REQUEST['wh_booking_id']))  $wh_booking_id = $_REQUEST['wh_booking_id'];                  //  {'1', '2', .... }
                              else                                    $wh_booking_id      = '';                    ?>
                        <input class="input-small" type="text" placeholder="<?php _e('Booking ID', 'wpdev-booking'); ?>" name="wh_booking_id" id="wh_booking_id" value="<?php echo $wh_booking_id; ?>" >
                        <button class="btn small" type="submit"><?php _e('Go', 'wpdev-booking'); ?></button>
                    </form>
                    </div>

                    <form  name="booking_filters_form" action="" method="post" id="booking_filters_form"  class="form-inline">
                        <input type="hidden" name="page_num" id ="page_num" value="1" />
                        <a class="btn btn-primary" style="float: left; margin-right: 15px; margin-top: 1px;"
                            onclick="javascript:booking_filters_form.submit();"
                            ><?php _e('Apply', 'wpdev-booking'); ?> <i class="icon-refresh icon-white"></i></a>

                        <?php if (function_exists('wpdebk_filter_field_bk_resources')) {
                                  wpdebk_filter_field_bk_resources();
                        } ?>
<?php /** ?>
          <?php

          $wpdevbk_id= '';
          $wpdevbk_selectors='';
          $wpdevbk_control_label='';
          $wpdevbk_help_block='Booking Status';
          $wpdevbk_default_value = '';
          $wpdevbk_selector_default_value='';
          ?>
          <div class="control-group" style="float:left;">
            <label for="<?php echo $wpdevbk_id; ?>" class="control-label"><?php echo $wpdevbk_control_label; ?></label>
            <div class="inline controls">
                <!--div class="btn-group" data-toggle="buttons-radio" id="radiobutton_<?php echo $wpdevbk_id; ?>"-->
                <div class="btn-group" data-toggle="buttons-checkbox" id="radiobutton_<?php echo $wpdevbk_id; ?>">
                    <a  href="#" class="btn">Approved</a>
                    <a  href="#" class="btn">Pending</a>
                </div>
                <input type="hidden" value="<?php echo $wpdevbk_selector_default_value; ?>" id="<?php echo $wpdevbk_id; ?>" name="<?php echo $wpdevbk_id; ?>" />
              <p class="help-block"><?php echo $wpdevbk_help_block; ?></p>
            </div>
          </div>
            <script type="text/javascript">
                jQuery('#radiobutton_<?php echo $wpdevbk_id; ?> .btn').button();
                <?php if (1) {  // Press the button ?>
                    jQuery('#radiobutton_<?php echo $wpdevbk_id; ?> .btn:first').button('toggle');
                <?php } ?>
            </script>
<?php /**/ ?>
                        <?php // Approved / Pending
                        $wpdevbk_id =              'wh_approved';                           //  {'', '0', '1' }
                        $wpdevbk_selectors = array(__('Pending', 'wpdev-booking')   =>'0',
                                                   __('Approved', 'wpdev-booking')  =>'1',
                                                   'divider0'=>'divider',
                                                   __('All', 'wpdev-booking')       =>'');
                        $wpdevbk_control_label =   '';
                        $wpdevbk_help_block =      __('Booking Status', 'wpdev-booking');
                        // Pending, Active, Suspended, Terminated, Cancelled, Fraud
                        wpdevbk_selectbox_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block);
                        ?>


                        <?php  // Booking Dates
                        $wpdevbk_id =              'wh_booking_date';
                        $wpdevbk_id2 =             'wh_booking_date2';
                        $wpdevbk_control_label =   '';
                        $wpdevbk_help_block =      __('Booking dates', 'wpdev-booking');
                        $wpdevbk_width =           'span2 wpdevbk-filters-section-calendar';
                        $wpdevbk_icon =            '<i class="icon-calendar"></i>' ;
                        wpdevbk_dates_selection_for_filter($wpdevbk_id, $wpdevbk_id2, $wpdevbk_control_label,  $wpdevbk_help_block,  $wpdevbk_width, $wpdevbk_icon );
                        ?>

                        <span style="display:none;" class="advanced_booking_filter">

                        <?php  // Read / Unread
                        $wpdevbk_id =              'wh_is_new';                           //  {'',  '1' }
                        $wpdevbk_selectors =        array('','1');
                        $wpdevbk_control_label =   __('Unread', 'wpdev-booking');
                        $wpdevbk_help_block =      __('Only New', 'wpdev-booking');

                        wpdevbk_checkboxbutton_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block);
                        ?>

                        
                        <?php  // Creation Dates
                        $wpdevbk_id =              'wh_modification_date';
                        $wpdevbk_id2 =             'wh_modification_date2';
                        $wpdevbk_control_label =   '';
                        $wpdevbk_help_block =      __('Creation date(s)', 'wpdev-booking');
                        $wpdevbk_width =           'span2 wpdevbk-filters-section-calendar';
                        $wpdevbk_icon =            '<i class="icon-calendar"></i>' ;
                        $exclude_items = array(0, 2, 4);
                        $default_item = 3 ;
                        wpdevbk_dates_selection_for_filter($wpdevbk_id, $wpdevbk_id2, $wpdevbk_control_label,  $wpdevbk_help_block,  $wpdevbk_width, $wpdevbk_icon, $exclude_items, $default_item );
                        ?>

                        <?php if (function_exists('wpdebk_filter_field_bk_keyword')) {
                                  wpdebk_filter_field_bk_keyword();
                        } ?>

                        <?php if (function_exists('wpdebk_filter_field_bk_paystatus')) {
                                  wpdebk_filter_field_bk_paystatus();
                        } ?>

                        <?php if (function_exists('wpdebk_filter_field_bk_costs')) {
                                  wpdebk_filter_field_bk_costs();
                        } ?>

                        </span>

                        <?php // Sort
                        $wpdevbk_id =              'or_sort';                           //  {'', '0', '1' }
                        $wpdevbk_selectors = array(__('ID', 'wpdev-booking').'&nbsp;<i class="icon-arrow-up "></i>' =>'',
                                                   __('Dates', 'wpdev-booking').'&nbsp;<i class="icon-arrow-up "></i>' =>'sort_date',
                                                   'divider0'=>'divider',
                                                   __('ID', 'wpdev-booking').'&nbsp;<i class="icon-arrow-down "></i>' =>'booking_id_asc',
                                                   __('Dates', 'wpdev-booking').'&nbsp;<i class="icon-arrow-down "></i>' =>'sort_date_asc'
                                                  );

                        $wpdevbk_selectors = apply_bk_filter('bk_filter_sort_options', $wpdevbk_selectors);
       
                        $wpdevbk_control_label =   '';
                        $wpdevbk_help_block =      __('Sort', 'wpdev-booking');
                        
                        $wpdevbk_default_value = get_bk_option( 'booking_sort_order');
                        wpdevbk_selectbox_filter($wpdevbk_id, $wpdevbk_selectors, $wpdevbk_control_label, $wpdevbk_help_block, $wpdevbk_default_value);
                        ?>

                        <?php if (class_exists('wpdev_bk_personal')) { ?>
                        <span style="display:none;" class="advanced_booking_filter">

                            <div class="clear"></div>
                            <a data-original-title="<?php _e('Save filter settings as default template (Please, click Apply filter button, before saving!)','wpdev-booking'); ?>"  rel="tooltip"
                               class="tooltip_top btn" style="margin-bottom:10px;"
                                onclick="javascript:save_bk_listing_filter( '<?php echo get_bk_current_user_id(); ?>',  'default' , '<?php echo get_params_in_url( array('page_num') ); ?>' );"
                                ><?php _e('Save as Default', 'wpdev-booking'); ?> <i class="icon-upload"></i></a>

                        </span>
                        <?php } ?>

                      <div class="clear"></div>
                    </form>

                    

            <!--div id="tooltipsinit" class="tooltip-demo well">
                <p style="margin-bottom: 0;" class="muted">Tight pants next level keffiyeh
                    <a rel="tooltip" href="#" data-original-title="first tooltip">you probably</a>

                    haven't heard of them. Photo booth beard raw denim letterpress vegan messenger bag stumptown. Farm-to-table seitan, mcsweeney's fixie sustainable quinoa 8-bit american apparel

                    <a rel="tooltip" href="#" data-original-title="Another tooltip">have a</a>

                    terry richardson vinyl chambray. Beard stumptown, cardigans banh mi lomo thundercats. Tofu biodiesel williamsburg marfa, four loko mcsweeney's cleanse vegan chambray. A

                    <a title="Another one here too" rel="tooltip" href="#">really ironic</a>

                    artisan whatever keytar, scenester farm-to-table banksy Austin

                    <a rel="tooltip" href="#" data-original-title="The last tip!">twitter handle</a>

                    freegan cred raw denim single-origin coffee viral.
                </p>
            </div>

            <script type="text/javascript">
                jQuery('#tooltipsinit a').tooltip( {
                    animation: true
                  , delay: { show: 500, hide: 100 }
                  , selector: false
                  , placement: 'top'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });
            </script>


            <div id="popover" class="well">
                <a data-content="And here's some amazing content. It's very engaging. right?" rel="popover" class="btn btn-danger" href="#" data-original-title="A Title">hover for popover</a>
            </div>


            <script type="text/javascript">
                jQuery('#popover a').popover( {
                    placement: 'bottom'
                  , delay: { show: 100, hide: 100 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
            </script-->
            </div>
            <div style="clear:both;height:1px;"></div>
        <?php
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Actions interface      ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    function wpdev_show_booking_actions(){
            $user = wp_get_current_user(); $user_bk_id = $user->ID;
        ?>
            <div class="btn-toolbar" style="margin:0px;">
                <div class="btn-group" style="margin-top: 2px; vertical-align: top;">
                    <a     data-original-title="<?php _e('Approve selected bookings'); ?>"  rel="tooltip" class="tooltip_top btn btn-primary"
                           onclick="javascript: 
                                                approve_unapprove_booking( get_selected_bookings_id_in_booking_listing() ,
                                                      1, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1);
                           " /><?php _e('Approve', 'wpdev-booking'); ?> <i class="icon-ok icon-white"></i></a>
                    <a     data-original-title="<?php _e('Set selected bookings as pending'); ?>"  rel="tooltip" class="tooltip_top btn"
                           onclick="javascript: 
                                        if ( bk_are_you_sure('<?php echo esc_js(__('Are you really want to set booking as pending ?', 'wpdev-booking')); ?>') )
                                                approve_unapprove_booking( get_selected_bookings_id_in_booking_listing() ,
                                                      0, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1);
                           " /><?php _e('Reject', 'wpdev-booking'); ?> <i class="icon-ban-circle"></i></a>
                </div>
                <div class="btn-group" style="margin-top: 2px; vertical-align: top;width:340px">
                    <a  data-original-title="<?php _e('Delete selected bookings'); ?>"  rel="tooltip" class="tooltip_top btn btn-danger"
                        onclick="javascript: 
                                if ( bk_are_you_sure('<?php echo esc_js(__('Are you really want to delete selected booking(s) ?', 'wpdev-booking')); ?>') )
                                    delete_booking( get_selected_bookings_id_in_booking_listing() ,
                                                    <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1  );
                                " >
                        <?php _e('Delete', 'wpdev-booking'); ?> <i class="icon-trash icon-white"></i></a>
                    <input  style="border-bottom-left-radius: 0; border-top-left-radius: 0; height: 28px; "
                            type="text" placeholder="<?php echo __('Reason for cancellation here', 'wpdev-booking'); ?>"
                            class="span3" value="" id="denyreason" name="denyreason" />
                </div>

                <div class="btn-group" style="margin-top: 2px; vertical-align: top;">
                    <a     data-original-title="<?php _e('Mark as read selected bookings'); ?>"  rel="tooltip" class="tooltip_top btn btn"
                           onclick="javascript:
                                                mark_read_booking( get_selected_bookings_id_in_booking_listing() ,
                                                      0, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' );
                           " /><?php _e('Read', 'wpdev-booking'); ?> <i class="icon-eye-close"></i></a>
                    <a     data-original-title="<?php _e('Mark as Unread selected bookings'); ?>"  rel="tooltip" class="tooltip_top btn"
                           onclick="javascript:                                       
                                                mark_read_booking( get_selected_bookings_id_in_booking_listing() ,
                                                      1, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' );
                           " /><?php _e('Unread', 'wpdev-booking'); ?> <i class="icon-eye-open"></i></a>
                </div>


                <?php if (function_exists('wpdebk_action_field_export_print')) {
                          wpdebk_action_field_export_print();
                } ?>

          </div>
          <div class="clear" style="height:1px;"></div>
          <div id="admin_bk_messages" style="margin:0px;"> </div>
          <div class="clear" style="height:1px;"></div>
        <?php
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  SQL for the dates filtering      ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    // SQL - WHERE -  D a t e s  (BK)
    function set_dates_filter_for_sql($wh_booking_date, $wh_booking_date2, $pref = 'dt.') {
            $sql_where= '';
            if ($pref == 'dt.')  { $and_pre = ' AND '; $and_suf = ''; }
            else                 { $and_pre = ''; $and_suf = ' AND '; }

                                                                                // Actual
            if (  ( ( $wh_booking_date  === '' ) && ( $wh_booking_date2  === '' ) ) || ($wh_booking_date  === '0') ) {
                $sql_where =               $and_pre."( ".$pref."booking_date >= ( CURDATE() - INTERVAL 1 DAY ) ) ".$and_suf ;

            } else  if ($wh_booking_date  === '1') {                            // Today
                $sql_where  =               $and_pre."( ".$pref."booking_date <= ( CURDATE() + INTERVAL 1 DAY ) ) ".$and_suf ;
                $sql_where .=               $and_pre."( ".$pref."booking_date >= ( CURDATE() - INTERVAL 1 DAY ) ) ".$and_suf ;


            } else if ($wh_booking_date  === '2') {                             // Previous
                $sql_where =               $and_pre."( ".$pref."booking_date <= ( CURDATE() + INTERVAL 1 DAY ) ) ".$and_suf ;

            } else if ($wh_booking_date  === '3') {                             // All
                $sql_where =  '';

            } else if ($wh_booking_date  === '4') {                             // Next
                $sql_where  =               $and_pre."( ".$pref."booking_date <= ( CURDATE() + INTERVAL ". $wh_booking_date2 . " DAY ) ) ".$and_suf ;
                $sql_where .=               $and_pre."( ".$pref."booking_date >= ( CURDATE() - INTERVAL 1 DAY ) ) ".$and_suf ;

            } else if ($wh_booking_date  === '5') {                             // Prior
                $wh_booking_date2 = str_replace('-', '', $wh_booking_date2);
                $sql_where  =               $and_pre."( ".$pref."booking_date >= ( CURDATE() - INTERVAL ". $wh_booking_date2 . " DAY ) ) ".$and_suf ;
                $sql_where .=               $and_pre."( ".$pref."booking_date <= ( CURDATE() + INTERVAL 1 DAY ) ) ".$and_suf ;

            } else {                                                            // Fixed

                if ( $wh_booking_date  !== '' )
                    $sql_where.=               $and_pre."( ".$pref."booking_date >= '" . $wh_booking_date . "' ) ".$and_suf;

                if ( $wh_booking_date2  !== '' )
                    $sql_where.=               $and_pre."( ".$pref."booking_date <= '" . $wh_booking_date2 . "' ) ".$and_suf;
            }
            return $sql_where;
    }

    // SQL - WHERE -  D a t e s  (Modification)
    function set_creation_dates_filter_for_sql($wh_modification_date, $wh_modification_date2, $pref = 'bk.') {
            $sql_where= '';
            if ($pref == 'bk.')  { $and_pre = ' AND '; $and_suf = ''; }
            else                 { $and_pre = ''; $and_suf = ' AND '; }

            if ($wh_modification_date  === '1') {                               // Today
                $sql_where  =               $and_pre."( ".$pref."modification_date <= ( CURDATE() + INTERVAL 1 DAY ) ) ".$and_suf ;
                $sql_where .=               $and_pre."( ".$pref."modification_date >= ( CURDATE() - INTERVAL 1 DAY ) ) ".$and_suf ;

            } else if ($wh_modification_date  === '3') {                        // All
                $sql_where =  '';

            } else if ($wh_modification_date  === '5') {                        // Prior
                $wh_modification_date2 = str_replace('-', '', $wh_modification_date2);
                $sql_where  =               $and_pre."( ".$pref."modification_date >= ( CURDATE() - INTERVAL ". $wh_modification_date2 . " DAY ) ) ".$and_suf ;
                $sql_where .=               $and_pre."( ".$pref."modification_date <= ( CURDATE() + INTERVAL 1 DAY ) ) ".$and_suf ;

            } else {                                                            // Fixed

                if ( $wh_modification_date  !== '' )
                    $sql_where.=               $and_pre."( ".$pref."modification_date >= '" . $wh_modification_date . "' ) ".$and_suf;

                if ( $wh_modification_date2  !== '' )
                    $sql_where.=               $and_pre."( ".$pref."modification_date <= '" . $wh_modification_date2 . "' ) ".$and_suf;
            }
            return $sql_where;
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Bookings listing    E N G I N E        ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Get Default params or from Request
    function wpdev_get_args_from_request_in_bk_listing(){
        $num_per_page_check = get_bk_option( 'bookings_num_per_page');
        if (empty( $num_per_page_check)) {
            $num_per_page_check = '10';
            update_bk_option( 'bookings_num_per_page', $num_per_page_check );
        }

        $args = array(
		'wh_booking_type' =>    (isset($_REQUEST['wh_booking_type']))?$_REQUEST['wh_booking_type']:'',
                'wh_approved' =>        (isset($_REQUEST['wh_approved']))?$_REQUEST['wh_approved']:'',
		'wh_booking_id' =>      (isset($_REQUEST['wh_booking_id']))?$_REQUEST['wh_booking_id']:'',
                'wh_is_new' =>          (isset($_REQUEST['wh_is_new']))?$_REQUEST['wh_is_new']:'',
		'wh_pay_status' =>      (isset($_REQUEST['wh_pay_status']))?$_REQUEST['wh_pay_status']:'',
                'wh_keyword' =>         (isset($_REQUEST['wh_keyword']))?$_REQUEST['wh_keyword']:'',
		'wh_booking_date' =>    (isset($_REQUEST['wh_booking_date']))?$_REQUEST['wh_booking_date']:'',
                'wh_booking_date2' =>   (isset($_REQUEST['wh_booking_date2']))?$_REQUEST['wh_booking_date2']:'',
		'wh_modification_date' =>  (isset($_REQUEST['wh_modification_date']))?$_REQUEST['wh_modification_date']:'',
                'wh_modification_date2' => (isset($_REQUEST['wh_modification_date2']))?$_REQUEST['wh_modification_date2']:'',
		'wh_cost' =>            (isset($_REQUEST['wh_cost']))?$_REQUEST['wh_cost']:'',
                'wh_cost2' =>           (isset($_REQUEST['wh_cost2']))?$_REQUEST['wh_cost2']:'',
		'or_sort' =>            (isset($_REQUEST['or_sort']))?$_REQUEST['or_sort']:get_bk_option( 'booking_sort_order'),
		'page_num' =>           (isset($_REQUEST['page_num']))?$_REQUEST['page_num']:'1',
                'page_items_count' =>   (isset($_REQUEST['page_items_count']))?$_REQUEST['page_items_count']:$num_per_page_check,
	);

        return $args;
    }



    // Get Default params or from Request -- for admin    C a l e n d a r   V i e w    M o d e
    function wpdev_get_args_from_request_in_bk_overview_in_calendar(){


        $start_year = date("Y");            //2012
        $start_month = date("m");           //09
        $start_day = 1;//date("d");//1;     //31


        if (isset($_REQUEST['view_days_num'])) $view_days_num = $_REQUEST['view_days_num'];
        else $view_days_num = get_bk_option( 'booking_view_days_num');
        switch ($view_days_num) {
            case '90':

                $start_day = date("d");
                $start_week_day_num = date("w");
                $start_day_weeek  = get_bk_option( 'booking_start_day_weeek' ); //[0]:Sun .. [6]:Sut

                if ($start_week_day_num != $start_day_weeek) {
                    for ($d_inc = 1; $d_inc < 8; $d_inc++) {                // Just get week  back
                        $real_date = mktime(0, 0, 0, $start_month, ($start_day-$d_inc ) , $start_year);
                        $start_week_day_num = date("w", $real_date);
                        if ($start_week_day_num == $start_day_weeek) {
                            $start_day = date("d", $real_date);
                            $start_year = date("Y", $real_date);
                            $start_month = date("m", $real_date);

                            //break;
                        }
                    }
                }

                if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                else $scroll_day = 0;

                $real_date = mktime(0, 0, 0, $start_month,    ( $start_day +$scroll_day) ,     $start_year);
                $wh_booking_date  = date("Y-m-d", $real_date);                          // '2012-12-01';

                $real_date = mktime(0, 0, 0, $start_month,    ($start_day+7*12+7+$scroll_day) ,  $start_year);
                $wh_booking_date2 = date("Y-m-d", $real_date);                          // '2013-12-31';

                break;

            case '30':
                $start_day = date("d");

                if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                else $scroll_day = 0;

                $real_date = mktime(0, 0, 0, $start_month,    ( $start_day +$scroll_day) ,     $start_year);
                $wh_booking_date  = date("Y-m-d", $real_date);                          // '2012-12-01';

                $real_date = mktime(0, 0, 0, $start_month,    ($start_day+31+$scroll_day) ,  $start_year);
                $wh_booking_date2 = date("Y-m-d", $real_date);                          // '2013-12-31';

                break;

            default:  // 365

                if (isset($_REQUEST['scroll_month'])) $scroll_month = $_REQUEST['scroll_month'];
                else $scroll_month = 0;

                $real_date = mktime(0, 0, 0, ($start_month+$scroll_month),     $start_day ,     $start_year);
                $wh_booking_date  = date("Y-m-d", $real_date);                          // '2012-12-01';

                $real_date = mktime(0, 0, 0, ($start_month+$scroll_month+13), ($start_day-1) ,  $start_year);
                $wh_booking_date2 = date("Y-m-d", $real_date);                          // '2013-12-31';

                break;
        }

        $or_sort = get_bk_option( 'booking_sort_order') ;
        $start_page = '1';
        $num_per_page_check = '100000';

        $args = array(
		'wh_booking_type' =>    (isset($_REQUEST['wh_booking_type']))?$_REQUEST['wh_booking_type']:'',
                'wh_approved' =>        '',                                     // Any
		'wh_booking_id' =>      '',                                     // Any
                'wh_is_new' =>          '',//(isset($_REQUEST['wh_is_new']))?$_REQUEST['wh_is_new']:'',                  // ?
		'wh_pay_status' =>      'all',//(isset($_REQUEST['wh_pay_status']))?$_REQUEST['wh_pay_status']:'',          // ?
                'wh_keyword' =>         '',//(isset($_REQUEST['wh_keyword']))?$_REQUEST['wh_keyword']:'',                // ?
		'wh_booking_date' =>    $wh_booking_date,
                'wh_booking_date2' =>   $wh_booking_date2, 
		'wh_modification_date' =>  '3',//(isset($_REQUEST['wh_modification_date']))?$_REQUEST['wh_modification_date']:'',     // ?
                'wh_modification_date2' => '',//(isset($_REQUEST['wh_modification_date2']))?$_REQUEST['wh_modification_date2']:'',   // ?
		'wh_cost' =>            '',//(isset($_REQUEST['wh_cost']))?$_REQUEST['wh_cost']:'',                      // ?
                'wh_cost2' =>           '',//(isset($_REQUEST['wh_cost2']))?$_REQUEST['wh_cost2']:'',                    // ?
		'or_sort' =>            $or_sort,
		'page_num' =>           $start_page,
                'page_items_count' =>   $num_per_page_check
	);

        return $args;
    }





    // S Q L    B o o k i n g    L i s t i n g
    function wpdev_sql_get_booking_lising( $args ){
	global $wpdb;
        $num_per_page_check = get_bk_option( 'bookings_num_per_page');
        if (empty( $num_per_page_check)) {
            $num_per_page_check = '10';
            update_bk_option( 'bookings_num_per_page', $num_per_page_check );
        }

        ////////////////////////////////////////////////////////////////////////
        // CONSTANTS
        ////////////////////////////////////////////////////////////////////////
	$defaults = array(
		'wh_booking_type' => '',    'wh_approved' => '',
		'wh_booking_id' => '',      'wh_is_new' => '',
		'wh_pay_status' => '',      'wh_keyword' => '',
		'wh_booking_date' => '',        'wh_booking_date2' => '',
		'wh_modification_date' => '',   'wh_modification_date2' => '',
		'wh_cost' => '',            'wh_cost2' => '',
		'or_sort' => get_bk_option( 'booking_sort_order'),
		'page_num' => '1',
                'page_items_count' => $num_per_page_check
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

        $page_start = ( $page_num - 1 ) * $page_items_count ;


        $posible_sorts = array('booking_id_asc','sort_date','sort_date_asc','booking_type','booking_type_asc','cost','cost_asc');
        if ( ($or_sort == '') || ($or_sort == 'id') || (! in_array($or_sort, $posible_sorts) ) ) $or_sort = 'booking_id';

        ////////////////////////////////////////////////////////////////////////
        // S Q L
        ////////////////////////////////////////////////////////////////////////
        // GET ONLY ROWS OF THE     B o o k i n g s    - So we can limit the requests
        $sql_start_select = " SELECT * " ;
        $sql_start_count  = " SELECT COUNT(*) as count" ;
        $sql = " FROM ".$wpdb->prefix ."booking as bk" ;
        $sql_where = " WHERE " .                                                      // Date (single) connection (Its required for the correct Pages in SQL: LIMIT Keyword)
               "       EXISTS (
                                SELECT *
                                FROM ".$wpdb->prefix ."bookingdates as dt
                                WHERE  bk.booking_id = dt.booking_id " ;
                if ($wh_approved !== '')
                    $sql_where.=           " AND approved = $wh_approved  " ;            // Approved or Pending

            $sql_where.= set_dates_filter_for_sql($wh_booking_date, $wh_booking_date2) ;

            $sql_where.=   "   ) " ;

        if ( $wh_is_new !== '' )    $sql_where .= " AND  bk.is_new = " . $wh_is_new . " ";

            // P
            $sql_where .= apply_bk_filter('get_bklist_sql_keyword', ''  , $wh_keyword );

        $sql_where.= set_creation_dates_filter_for_sql($wh_modification_date, $wh_modification_date2 ) ;

            // BS
            $sql_where .= apply_bk_filter('get_bklist_sql_paystatus', ''  , $wh_pay_status );
            $sql_where .= apply_bk_filter('get_bklist_sql_cost', ''  , $wh_cost, $wh_cost2 );

            // P  || BL
            $sql_where .= apply_bk_filter('get_bklist_sql_resources', ''  , $wh_booking_type, $wh_approved, $wh_booking_date, $wh_booking_date2 );

        if (! empty ($wh_booking_id) ) {
            if ( strpos($wh_booking_id, ',') !== false)
                $sql_where = " WHERE bk.booking_id IN (" . $wh_booking_id . ") ";
            else
                $sql_where = " WHERE bk.booking_id = " . $wh_booking_id . " ";
        }

        if (strpos($or_sort, '_asc') !== false) {                               // Order
               $or_sort = str_replace('_asc', '', $or_sort);
               $sql_order = " ORDER BY " .$or_sort ." ASC ";                                          
        } else $sql_order = " ORDER BY " .$or_sort ." DESC ";                                          // Order

        $sql_limit = " LIMIT $page_start, $page_items_count ";                        // Page s
        
        return array( $sql_start_count, $sql_start_select , $sql , $sql_where , $sql_order , $sql_limit );        
    }


    // E n g i n e     B o o k i n g    L i s t i n g
    function wpdev_get_bk_listing_structure_engine( $args ){
        global $wpdb;
///debuge($_REQUEST);
        $sql_boking_listing = wpdev_sql_get_booking_lising( $args );
//debuge($sql_boking_listing);
        $sql_start_count    = $sql_boking_listing[0];
        $sql_start_select   = $sql_boking_listing[1];
        $sql       = $sql_boking_listing[2];
        $sql_where = $sql_boking_listing[3];
        $sql_order = $sql_boking_listing[4];
        $sql_limit = $sql_boking_listing[5];

        $num_per_page_check = get_bk_option( 'bookings_num_per_page') ;
        if (empty( $num_per_page_check)) {
            $num_per_page_check = '10';
        }
	$defaults = array(
		'wh_booking_type' => '',    'wh_approved' => '',
		'wh_booking_id' => '',      'wh_is_new' => '',
		'wh_pay_status' => '',      'wh_keyword' => '',
		'wh_booking_date' => '',        'wh_booking_date2' => '',
		'wh_modification_date' => '',   'wh_modification_date2' => '',
		'wh_cost' => '',            'wh_cost2' => '',
		'or_sort' => get_bk_option( 'booking_sort_order'),
		'page_num' => '1',
                'page_items_count' => $num_per_page_check
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

        $page_start = ( $page_num - 1 ) * $page_items_count ;
//debuge($sql_start_select . $sql . $sql_where . $sql_order . $sql_limit);
        // Get Bookings Array
        $bookings_res = $wpdb->get_results(wpdevbk_db_prepare( $sql_start_select . $sql . $sql_where . $sql_order . $sql_limit ));

        // Get Number of booking for the pages
        $bookings_count = $wpdb->get_results(wpdevbk_db_prepare( $sql_start_count . $sql . $sql_where   ));

        // Get NUMBER of Bookings
        if (count($bookings_count)>0)   $bookings_count = $bookings_count[0]->count ;
        else                            $bookings_count = 0;

        $booking_types = apply_bk_filter('wpdebk_get_keyed_all_bk_resources', array() );


        // Bookings array init                 - Get the ID list of ALL bookings
        $booking_id_list = array();

        $bookings = array();
        $short_days = array();
        $short_days_type_id = array();
        if ( count($bookings_res)>0 )
        foreach ($bookings_res as $booking ) {
            if ( ! in_array($booking->booking_id, $booking_id_list) ) $booking_id_list[] = $booking->booking_id;

            $bookings[$booking->booking_id] = $booking;
            $bookings[$booking->booking_id]->dates=array();
            $bookings[$booking->booking_id]->dates_short=array();

            $bk_list_type = (isset($booking->booking_type))?$booking->booking_type:'1';
            $cont = get_form_content($booking->form, $bk_list_type, '', array('booking_id'=> $booking->booking_id , 
                                                                              'resource_title'=> (isset($booking_types[$booking->booking_type]))?$booking_types[$booking->booking_type]:''
                                                                              )
                                                         );

            $search = array ("'(<br[ ]?[/]?>)+'si","'(<p[ ]?[/]?>)+'si","'(<div[ ]?[/]?>)+'si");
            $replace = array ("&nbsp;&nbsp;"," &nbsp; "," &nbsp; ");
            $cont['content'] = preg_replace($search, $replace, $cont['content']);
            $bookings[$booking->booking_id]->form_show = $cont['content'];
            unset($cont['content']);
            $bookings[$booking->booking_id]->form_data = $cont;
        }
        $booking_id_list = implode(",",$booking_id_list);

        if (! empty($booking_id_list)) {
            // Get Dates  for all our Bookings
            $sql = " SELECT *
            FROM ".$wpdb->prefix ."bookingdates as dt
            WHERE dt.booking_id in ( " .$booking_id_list . ") ";

            if (class_exists('wpdev_bk_biz_l'))
                $sql .= " ORDER BY booking_id, type_id, booking_date   ";
            else
                $sql .= " ORDER BY booking_id, booking_date   ";

            $booking_dates = $wpdb->get_results(wpdevbk_db_prepare( $sql ));
        } else
            $booking_dates = array();


        $last_booking_id = '';
        // Add Dates to Bookings array
        foreach ($booking_dates as $date) {
            $bookings[$date->booking_id]->dates[] = $date;

                if ($date->booking_id != $last_booking_id) {
                    if (! empty($last_booking_id)) {
                        if($last_show_day != $dte) { $short_days[]= $dte; $short_days_type_id[] = $last_day_id;}

                        $bookings[ $last_booking_id ]->dates_short = $short_days;
                        $bookings[ $last_booking_id ]->dates_short_id = $short_days_type_id;
                    }
                    $last_day = '';
                    $last_day_id = '';
                    $last_show_day = '';
                    $short_days = array();
                    $short_days_type_id = array();
                }

                $last_booking_id = $date->booking_id;
                $dte = $date->booking_date;

                if (empty($last_day)) { // First date
                    $short_days[]= $dte; $short_days_type_id[] = (isset($date->type_id))?$date->type_id:'';
                    $last_show_day = $dte;
                } else {                // All other days
                    if ( wpdevbk_is_next_day( $dte ,$last_day) ) {
                        if ($last_show_day != '-') { $short_days[]= '-'; $short_days_type_id[] = ''; }
                        $last_show_day = '-';
                    } else {
                        if ($last_show_day !=$last_day) { $short_days[]= $last_day; $short_days_type_id[] = $last_day_id; }
                        $short_days[]= ','; $short_days_type_id[] = '';
                        $short_days[]= $dte; $short_days_type_id[] = (isset($date->type_id))?$date->type_id:'';
                        $last_show_day = $dte;
                    }
                }
                $last_day = $dte;
                $last_day_id = (isset($date->type_id))?$date->type_id:'';
        }

        if (isset($dte))
            if($last_show_day != $dte) { $short_days[]= $dte; $short_days_type_id[] = (isset($date->type_id))?$date->type_id:'';}
        if (isset($bookings[ $last_booking_id ]) )  {
            $bookings[ $last_booking_id ]->dates_short = $short_days;
            $bookings[ $last_booking_id ]->dates_short_id = $short_days_type_id;
        }

        
        
//debuge(array($bookings , $booking_types, $bookings_count, $page_num,  $page_items_count));
        return array($bookings , $booking_types, $bookings_count, $page_num,  $page_items_count);
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // B o o k i n g    L i s t i n g    P A G E    ////////////////////////////////////////////////////////////////////////////////////////////////

    function wpdevbk_show_booking_listings() {

        wpdevbk_get_default_bk_listing_filter_set_to_params('default');         // Get saved filters set

        if ($_REQUEST['view_mode'] == 'vm_calendar') {                           // { vm_listing | vm_calendar}
              $args = wpdev_get_args_from_request_in_bk_listing();                    // Get safy PARAMS from REQUEST
              bookings_overview_in_calendar($args);                             // If shoing the Calendar Overview, so then return from  this function
              return;
        }

        wpdevbk_booking_view_mode_buttons();
        wpdevbk_booking_listings_interface_header() ;                            // Show Filters and Action tabs

        // If the booking resources is not set, and current user  is not superadmin, so then get only the booking resources of the current user
        make_bk_action('check_for_resources_of_notsuperadmin_in_booking_listing' );

	$args = wpdev_get_args_from_request_in_bk_listing();                    // Get safy PARAMS from REQUEST
        ?><textarea id="bk_request_params" style="display:none;"><?php echo  serialize($args) ; ?></textarea><?php

        $bk_listing = wpdev_get_bk_listing_structure_engine( $args );           // Get Bookings structure
        $bookings       = $bk_listing[0];
        $booking_types  = $bk_listing[1];
        $bookings_count = $bk_listing[2];
        $page_num       = $bk_listing[3];
        $page_items_count= $bk_listing[4];

//debuge($args, count($bookings),$bookings, $booking_types, $_REQUEST);

        booking_listing_table($bookings , $booking_types);                      // Show the bookings listing table

        wpdevbk_show_pagination($bookings_count, $page_num, $page_items_count); // Show Pagination
        
        wpdevbk_booking_listing_write_js();                                     // Wtite inline  JS
        wpdevbk_booking_listing_write_css();                                    // Write inline  CSS
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Bookings listing    T A B L E      ///////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //   S H O W      B o o k i n g    L i s t i n g    T a b l e
    function booking_listing_table($bookings , $booking_types) {
        //debuge($_REQUEST);

        $user = wp_get_current_user(); $user_bk_id = $user->ID;
        
        $bk_url_listing     = 'admin.php?page=' . WPDEV_BK_PLUGIN_DIRNAME . '/'. WPDEV_BK_PLUGIN_FILENAME . 'wpdev-booking' ;
        $bk_url_add         = $bk_url_listing . '-reservation' ;
        $bk_url_resources   = $bk_url_listing . '-resources' ;
        $bk_url_settings    = $bk_url_listing . '-option' ;

        $booking_date_view_type = get_bk_option( 'booking_date_view_type');
        if ($booking_date_view_type == 'short') { $wide_days_class = ' hidden_items '; $short_days_class = ''; }
        else {                                    $wide_days_class = ''; $short_days_class = ' hidden_items '; }

        ?>
         <div id="listing_visible_bookings">
          <?php if (count($bookings)>0) { ?>
          <div class="row-fluid booking-listing-header">
              <div class="booking-listing-collumn span1">
                  <input type="checkbox" onclick="javascript:setCheckBoxInTable(this.checked, 'booking_list_item_checkbox');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  <?php _e('ID', 'wpdev-booking'); ?>
              </div>
              <div class="booking-listing-collumn span2"><?php _e('Labels', 'wpdev-booking'); ?></div>
              <div class="booking-listing-collumn span4"><?php _e('Booking Data', 'wpdev-booking'); ?></div>
              <div class="booking-listing-collumn span3"><?php _e('Booking Dates', 'wpdev-booking'); ?>&nbsp;&nbsp;&nbsp;
                  <a href="javascript:;" id="booking_dates_full" onclick="javascript:
                            jQuery('#booking_dates_full').hide();
                            jQuery('#booking_dates_small').show();
                            jQuery('.booking_dates_small').hide();
                            jQuery('.booking_dates_full').show();" data-original-title="<?php _e('Show ALL dates of booking','wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top <?php echo $short_days_class; ?> "><i class="icon-resize-full"></i></a>
                  <a href="javascript:;" id="booking_dates_small" onclick="javascript:
                            jQuery('#booking_dates_small').hide();
                            jQuery('#booking_dates_full').show();
                            jQuery('.booking_dates_small').show();
                            jQuery('.booking_dates_full').hide();" data-original-title="<?php _e('Show only check in/out dates','wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top <?php echo $wide_days_class; ?> " ><i class="icon-resize-small"></i></a>
              </div>
              <div class="booking-listing-collumn span2"><?php _e('Actions', 'wpdev-booking'); ?></div>
          </div>
          <?php } else {
                        echo '<center><h3>'.__('Nothing found!', 'wpdev-booking') .'</h3></center>';
                } ?>
        <?php

        // P
        $print_data = apply_bk_filter('get_bklist_print_header', array(array())  );

        $is_alternative_color = true;
        $id_of_new_bookings = array();

        foreach ($bookings as $bk) {
            $is_selected_color = 0;//rand(0,1);
            $is_alternative_color = ! $is_alternative_color;

            $booking_id             = $bk->booking_id;          // 100
            $is_new                 = (isset($bk->is_new))?$bk->is_new:'0';                           // 1
            $bk_modification_date   = (isset($bk->modification_date))?$bk->modification_date:'';    // 2012-02-29 16:01:58
            $bk_form                = $bk->form;                // select-one^rangetime5^10:00 - 12:00~text^name5^Jonny~text^secondname5^Smith~email^ ....
            $bk_form_show           = $bk->form_show;           // First Name:Jonny   Last Name:Smith   Email:email@server.com  Country:GB  ....
            $bk_form_data           = $bk->form_data;           // Array ([name] => Jonny... [_all_] => Array ( [rangetime5] => 10:00 - 12:00 [name5] => Jonny ... ) .... )
            $bk_dates               = $bk->dates;               // Array ( [0] => stdClass Object ( [booking_id] => 8 [booking_date] => 2012-04-16 10:00:01 [approved] => 0 [type_id] => )
            $bk_dates_short         = $bk->dates_short;         // Array ( [0] => 2012-04-16 10:00:01 [1] => - [2] => 2012-04-20 12:00:02 [3] => , [4] => 2012-04-16 10:00:01 ....

            //P
            $bk_booking_type        = (isset($bk->booking_type))?$bk->booking_type:'1';        // 3
            if (!class_exists('wpdev_bk_personal')) {
                $bk_booking_type_name = '<span class="label_resource_not_exist">'.__('Default', 'wpdev-booking').'</span>';
            } else if (isset($booking_types[$bk_booking_type]))   {
                $bk_booking_type_name   = $booking_types[$bk_booking_type]->title;        // Default
                if (strlen($bk_booking_type_name)>19) {
                    //$bk_booking_type_name = substr($bk_booking_type_name, 0,  13) . ' ... ' . substr($bk_booking_type_name, -3 );
                    $bk_booking_type_name = '<span style="cursor:pointer;" rel="tooltip" class="tooltip_top"  data-original-title="'.$bk_booking_type_name.'">'.substr($bk_booking_type_name, 0,  13) . ' ... ' . substr($bk_booking_type_name, -3 ).'</span>';
                }
            } else  {
                $bk_booking_type_name = '<span class="label_resource_not_exist">'.__('Resource not exist', 'wpdev-booking').'</span>';
            }

            $bk_hash                = (isset($bk->hash))?$bk->hash:'';                // 99c9c2bd4fd0207e4376bdbf5ee473bc
            $bk_remark              = (isset($bk->remark))?$bk->remark:'';            //
            //BS
            $bk_cost                = (isset($bk->cost))?$bk->cost:'';                // 150.00
            $bk_pay_status          = (isset($bk->pay_status))?$bk->pay_status:'';    // 30800
            $bk_pay_request         = (isset($bk->pay_request))?$bk->pay_request:'';  // 0
            $bk_status              = (isset($bk->status))?$bk->status:'';
            //BL
            $bk_dates_short_id = array(); if (count($bk->dates) > 0 ) $bk_dates_short_id      = (isset($bk->dates_short_id))?$bk->dates_short_id:array();      // Array ([0] => [1] => .... [4] => 6... [11] => [12] => 8 )

            $is_approved = 0;   if (count($bk->dates) > 0 )     $is_approved = $bk->dates[0]->approved ;
            //BS
            $is_paid = 0;
            $payment_status_titles_current = '';
            if (class_exists('wpdev_bk_biz_s')) {

                if ( is_payment_status_ok( trim($bk_pay_status) ) ) $is_paid = 1 ;
                
                $payment_status_titles = get_payment_status_titles();
                $payment_status_titles_current = array_search($bk_pay_status, $payment_status_titles);
                if ($payment_status_titles_current === FALSE ) $payment_status_titles_current = $bk_pay_status ;
            }

            if ( $is_new == 1) $id_of_new_bookings[] = $booking_id;


            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // Get SHORT Dates showing data ////////////////////////////////////////////////////////////////////////////////////////////////////
            //$short_dates_content = wpdevbk_get_str_from_dates_short($bk_dates_short, $is_approved , $bk_dates_short_id , $booking_types );
            $short_dates_content = '';
            $dcnt = 0;
            foreach ($bk_dates_short as $dt) {
                if ($dt == '-') {       $short_dates_content .= '<span class="date_tire"> - </span>';
                } elseif ($dt == ',') { $short_dates_content .= '<span class="date_tire">, </span>';
                } else {
                    $short_dates_content .= '<a href="javascript:;" class="field-booking-date ';
                    if ($is_approved) $short_dates_content .= ' approved';
                    $short_dates_content .= '">';

                    $bk_date = wpdevbk_get_date_in_correct_format($dt);
                    $short_dates_content .= $bk_date[0];
                    $short_dates_content .= '<sup class="field-booking-time">'. $bk_date[1] .'</sup>';

                     // BL
                     if (class_exists('wpdev_bk_biz_l')) {
                         if (! empty($bk_dates_short_id[$dcnt]) ) {
                             $bk_booking_type_name_date   = $booking_types[$bk_dates_short_id[$dcnt]]->title;        // Default
                             if (strlen($bk_booking_type_name_date)>19) $bk_booking_type_name_date = substr($bk_booking_type_name_date, 0,  13) . '...' . substr($bk_booking_type_name_date, -3 );

                             $short_dates_content .= '<sup class="field-booking-time date_from_dif_type"> '.$bk_booking_type_name_date.'</sup>';
                         }
                     }
                    $short_dates_content .= '</a>';
                }
                $dcnt++;
            }


            // Get WIDE Dates showing data /////////////////////////////////////////////////////////////////////////////////////////////////////
            $wide_dates_content = '';
            $dates_count = count($bk_dates); $dcnt = 0;
            foreach ($bk_dates as $dt) { $dcnt++;
                $wide_dates_content .= '<a href="javascript:;" class="field-booking-date ';
                if ($is_approved) $wide_dates_content .= ' approved';
                $wide_dates_content .= ' ">';

                $bk_date = wpdevbk_get_date_in_correct_format($dt->booking_date);
                $wide_dates_content .=  $bk_date[0];
                $wide_dates_content .= '<sup class="field-booking-time">' . $bk_date[1]. '</sup>';
                 // BL
                if (class_exists('wpdev_bk_biz_l')) {
                 if ($dt->type_id != '') {
                     $bk_booking_type_name_date   = $booking_types[$dt->type_id]->title;        // Default
                     if (strlen($bk_booking_type_name_date)>19) $bk_booking_type_name_date = substr($bk_booking_type_name_date, 0, 13) . '...' . substr($bk_booking_type_name_date, -3 );
                     $wide_dates_content .= '<sup class="field-booking-time date_from_dif_type"> '.$bk_booking_type_name_date.'</sup>';
                 }
                }
                 $wide_dates_content .= '</a>';
                 if ($dcnt<$dates_count) { $wide_dates_content .= '<span class="date_tire">, </span>'; }
            }
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


            // BS
            $pay_print_status = '';
            if (class_exists('wpdev_bk_biz_s')) {
                if ($is_paid) {
                    $pay_print_status = __('Paid OK', 'wpdev-booking');
                    if ($payment_status_titles_current == 'Completed') $pay_print_status = $payment_status_titles_current;
                } else if ( (is_numeric($bk_pay_status)) || ($bk_pay_status == '') )        {
                    $pay_print_status = __('Unknown', 'wpdev-booking');
                } else  {
                    $pay_print_status = $payment_status_titles_current;
                }
            }
            ///// Print data  //////////////////////////////////////////////////////////////////////////////
            $print_data[] = apply_bk_filter('get_bklist_print_row', array() ,
                                             $booking_id,
                                             $is_approved ,
                                             $bk_form_show,
                                             $bk_booking_type_name,
                                             $is_paid ,
                                             $pay_print_status,
                                             ($booking_date_view_type == 'short')?'<div class="booking_dates_small">' . $short_dates_content . '</div>':'<div class="booking_dates_full">' .$wide_dates_content . '</div>' ,
                                             $bk_cost
                    );

            //////////////////////////////////////////////////////////////////////////////////////////////
            ?>
          <div id="booking_mark_<?php echo $booking_id; ?>"  class="<?php if ( $is_new!= '1') echo ' hidden_items '; ?> new-label clearfix-height">
              <a href="javascript:;"  class="tooltip_bottom approve_bk_link  <?php //if ($is_approved) echo ' hidden_items '; ?> "
                       onclick="javascript:mark_read_booking( '<?php echo $booking_id; ?>' ,
                                                      0, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' );"
                       data-original-title="<?php _e('Mark','wpdev-booking'); echo ' '; _e('Unread','wpdev-booking'); ?>"  rel="tooltip" >
                        <img src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/label_new_blue.png" style="width:24px; height:24px;"></a>
          </div>
          <div id="booking_row_<?php echo $booking_id; ?>"  class="row-fluid booking-listing-row clearfix-height<?php
          if ($is_alternative_color) echo ' row_alternative_color ';
          if ($is_selected_color) echo ' row_selected_color ';
          //if ($is_new) echo ' row_unread_color ';
          
            //$date_format = get_bk_option( 'booking_date_format');
            //$time_format = get_bk_option( 'booking_time_format');
            if (empty($date_format)) $date_format = "m / d / Y, D";
            if (empty($time_format)) $time_format = 'h:i a';
            $cr_date = date_i18n($date_format  , mysql2date('U',$bk_modification_date));
            $cr_time = date_i18n($time_format  , mysql2date('U',$bk_modification_date));
          ?>" >

              <div class="booking-listing-collumn span1 bktextcenter">
                  <input type="checkbox" class="booking_list_item_checkbox" 
                         onclick="javascript: if (jQuery(this).attr('checked') !== undefined ) { jQuery(this).parent().parent().addClass('row_selected_color'); } else {jQuery(this).parent().parent().removeClass('row_selected_color');}"
                         <?php if ($is_selected_color) echo ' checked="CHECKED" '; ?>
                         id="booking_id_selected_<?php  echo $booking_id;  ?>"  name="booking_appr_<?php  $booking_id;  ?>"
                         />&nbsp;&nbsp;&nbsp;
                  <span class="field-id"><?php echo $booking_id; ?></span>
                  <div class="field-date"> <?php echo $cr_date; ?></div>
                  <span class="field-time"> <?php echo $cr_time; ?></span>
              </div>

              <div class="booking-listing-collumn span2 bktextleft booking-labels">
                  <?php make_bk_action('wpdev_bk_listing_show_resource_label', $bk_booking_type_name );  ?>
                  <?php make_bk_action('wpdev_bk_listing_show_payment_label', $is_paid,  $pay_print_status, $payment_status_titles_current);  ?>
                  <span class="label label-pending <?php if ($is_approved) echo ' hidden_items '; ?> "><?php _e('Pending', 'wpdev-booking'); ?></span>
                  <span class="label label-approved <?php if (! $is_approved) echo ' hidden_items '; ?>"><?php _e('Approved', 'wpdev-booking'); ?></span>
              </div>

              <div class="booking-listing-collumn span4 bktextjustify">
                    <div style="text-align:left"><?php echo $bk_form_show; ?></div>
              </div>

              <div class="booking-listing-collumn span3 bktextleft booking-dates">

                <div class="booking_dates_small <?php echo $short_days_class; ?>"><?php echo $short_dates_content; ?></div>
                <div class="booking_dates_full  <?php echo $wide_days_class; ?>" ><?php echo $wide_dates_content;  ?></div>

              </div>

              <?php // P
                    $edit_booking_url = $bk_url_add . '&booking_type='.$bk_booking_type.'&booking_hash='.$bk_hash.'&parent_res=1' ; ?>

              <div class="booking-listing-collumn span2 bktextcenter  booking-actions">

                  <?php make_bk_action('wpdev_bk_listing_show_cost_btn', $booking_id, $bk_cost );  ?>
                  
                  <div class="actions-fields-group">

                    <?php make_bk_action('wpdev_bk_listing_show_edit_btn', $booking_id , $edit_booking_url, $bk_remark, $bk_booking_type );  ?>

                    <a href="javascript:;"  class="tooltip_bottom approve_bk_link  <?php if ($is_approved) echo ' hidden_items '; ?> "
                       onclick="javascript:approve_unapprove_booking(<?php echo $booking_id; ?>,1, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1  );"
                       data-original-title="<?php _e('Approve','wpdev-booking'); ?>"  rel="tooltip" >
                        <img src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/accept-24x24.gif" style="width:14px; height:14px;"></a>
                    
                    <a href="javascript:;"  class="tooltip_bottom pending_bk_link  <?php if (! $is_approved) echo ' hidden_items '; ?> "
                       onclick="javascript:if ( bk_are_you_sure('<?php echo esc_js(__('Are you really want to set booking as pending ?', 'wpdev-booking')); ?>') ) approve_unapprove_booking(<?php echo $booking_id; ?>,0, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1  );"
                       data-original-title="<?php _e('Reject','wpdev-booking'); ?>"  rel="tooltip" >
                        <img src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/remove-16x16.png" style="width:15px; height:15px;"></a>
                    
                    <a href="javascript:;" 
                       onclick="javascript:if ( bk_are_you_sure('<?php echo esc_js(__('Are you really want to delete this booking ?', 'wpdev-booking')); ?>') ) delete_booking(<?php echo $booking_id; ?>, <?php echo $user_bk_id; ?>, '<?php echo getBookingLocale(); ?>' , 1   );"
                       data-original-title="<?php _e('Delete','wpdev-booking'); ?>"  rel="tooltip" class="tooltip_bottom">
                        <img src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/delete_type.png" style="width:13px; height:13px;"></a>

                    <?php make_bk_action('wpdev_bk_listing_show_payment_status_btn', $booking_id );  ?>
                      
                  </div>
              </div>

              <?php make_bk_action('wpdev_bk_listing_show_edit_fields', $booking_id , $bk_remark );  ?>

              <?php make_bk_action('wpdev_bk_listing_show_payment_status_cost_fields', $booking_id  , $bk_pay_status);  ?>
              
          </div>
        <?php } ?>
        </div>

        <?php  //if  ( is_field_in_table_exists('booking','is_new') != 0 )  renew_NumOfNewBookings($id_of_new_bookings); // Update num status if supported  ?>

        <?php make_bk_action('wpdev_bk_listing_show_change_booking_resources', $booking_types);  ?>

        <?php if ( function_exists('wpdevbk_generate_print_loyout')) wpdevbk_generate_print_loyout( $print_data );
    }


    //  P a g i n a t i o n     of    Booking Listing
    function wpdevbk_show_pagination($summ_number_of_items, $active_page_num, $num_items_per_page , $only_these_parameters = false ) {
        if (empty( $num_items_per_page)) {
            $num_items_per_page = '10';
        }

        $pages_number = ceil ( $summ_number_of_items / $num_items_per_page );
        if ( $pages_number < 2 ) return;
        
        $bk_admin_url = get_params_in_url( array('page_num') , $only_these_parameters );
        
        ?>
        <div class="pagination pagination-centered" style="height:auto;">
          <ul>
              
            <?php if ($pages_number>1) { ?>
                <li <?php if ($active_page_num == 1) echo ' class="disabled" '; ?> >
                    <a href="<?php echo $bk_admin_url; ?>&page_num=<?php if ($active_page_num == 1) { echo $active_page_num; } else { echo ($active_page_num-1); } ?>">
                        <?php _e('Prev', 'wpdev-booking'); ?>
                    </a>
                </li>
            <?php } ?>
            
            <?php for ($pg_num = 1; $pg_num <= $pages_number; $pg_num++) { ?>

              <li <?php if ($pg_num == $active_page_num ) echo ' class="active" '; ?> >
                  <a href="<?php echo $bk_admin_url; ?>&page_num=<?php echo $pg_num; ?>">
                    <?php echo $pg_num; ?>
                  </a>
              </li>

            <?php } ?>

            <?php if ($pages_number>1) { ?>
                <li <?php if ($active_page_num == $pages_number) echo ' class="disabled" '; ?> >
                    <a href="<?php echo $bk_admin_url; ?>&page_num=<?php  if ($active_page_num == $pages_number) { echo $active_page_num; } else { echo ($active_page_num+1); } ?>">
                        <?php _e('Next', 'wpdev-booking'); ?>
                    </a>
                </li>
            <?php } ?>

          </ul>
        </div>
        <?php
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //   J S   and   C S S   for the Booking Listing page   ///////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function wpdevbk_booking_listing_write_js(){
        ?>
            <script type="text/javascript">
              jQuery(document).ready( function(){
                jQuery('input.wpdevbk-filters-section-calendar').datepick(
                    {   //onSelect: selectCheckInDay,
                        showOn: 'focus',
                        multiSelect: 0,
                        numberOfMonths: 1,
                        stepMonths: 1,
                        prevText: '<<',
                        nextText: '>>',
                        dateFormat: 'yy-mm-dd',
                        changeMonth: false,
                        changeYear: false,
                        minDate: 0, maxDate: booking_max_monthes_in_calendar, //'1Y',
                        showStatus: false,
                        multiSeparator: ', ',
                        closeAtTop: false,
                        firstDay:<?php echo get_bk_option( 'booking_start_day_weeek' ); ?>,
                        gotoCurrent: false,
                        hideIfNoPrevNext:true,
                        //rangeSelect:wpdev_bk_is_dynamic_range_selection,
                        //calendarViewMode:wpdev_bk_calendarViewMode,
                        useThemeRoller :false,
                        mandatory: true/**/
                    }
                );

                jQuery('a.popover_here').popover( {
                    placement: 'top'
                  , delay: { show: 100, hide: 200 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
                jQuery('.popover_left').popover( {
                    placement: 'left'
                  , delay: { show: 100, hide: 200 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
                jQuery('.popover_right').popover( {
                    placement: 'right'
                  , delay: { show: 100, hide: 200 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
                jQuery('.popover_top').popover( {
                    placement: 'top'
                  , delay: { show: 100, hide: 200 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
                jQuery('.popover_bottom').popover( {
                    placement: 'bottom'
                  , delay: { show: 100, hide: 200 }
                  , content: ''
                  , template: '<div class="wpdevbk popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>'
                });
            <?php
            $is_use_hints = get_bk_option( 'booking_is_use_hints_at_admin_panel'  );
            if ($is_use_hints == 'On')
                if (  ( ( strpos($_SERVER['REQUEST_URI'],'wpdev-booking.php')) !== false) &&
                      (   ( strpos($_SERVER['REQUEST_URI'],'wpdev-booking.phpwpdev-booking-reservation'))  === false)
                   ) { ?>

                jQuery('.tooltip_right').tooltip( {
                    animation: true
                  , delay: { show: 500, hide: 100 }
                  , selector: false
                  , placement: 'right'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });

                jQuery('.tooltip_left').tooltip( {
                    animation: true
                  , delay: { show: 500, hide: 100 }
                  , selector: false
                  , placement: 'left'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });

                jQuery('.tooltip_top').tooltip( {
                    animation: true
                  , delay: { show: 500, hide: 100 }
                  , selector: false
                  , placement: 'top'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });

                jQuery('.tooltip_bottom').tooltip( {
                    animation: true
                  , delay: { show: 500, hide: 100 }
                  , selector: false
                  , placement: 'bottom'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });

                jQuery('.tooltip_top_slow').tooltip( {
                    animation: true
                  , delay: { show: 2500, hide: 100 }
                  , selector: false
                  , placement: 'top'
                  , trigger: 'hover'
                  , title: ''
                  , template: '<div class="wpdevbk tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
                });

                <?php } ?>
                //jQuery('.dropdown-toggle').dropdown();

               });
              </script>
        <?php
    }

    function wpdevbk_booking_listing_write_css(){
        ?>
            <style type="text/css">
                #datepick-div .datepick-header {
                       width: 172px !important;
                }
                #datepick-div {
                    -border-radius: 3px;
                    -box-shadow: 0 0 2px #888888;
                    -webkit-border-radius: 3px;
                    -webkit-box-shadow: 0 0 2px #888888;
                    -moz-border-radius: 3px;
                    -moz-box-shadow: 0 0 2px #888888;
                    width: 172px !important;
                }
                #datepick-div .datepick .datepick-days-cell a{
                    font-size: 12px;
                }
                #datepick-div table.datepick tr td {
                    border-top: 0 none !important;
                    line-height: 24px;
                    padding: 0 !important;
                    width: 24px;
                }
                #datepick-div .datepick-control {
                    font-size: 10px;
                    text-align: center;
                }
                #datepick-div .datepick-one-month {
                    height: 215px;
                }
            </style>
        <?php
    }




// TODO: Finish  here
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  C a l e n d a r    O v e r v i e w     i n t e r f a c e      //////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // C A L E N D A R    O V E R V I E W    P A G E    ////////////////////////////////////////////////////////////////////////////////////////////

    // General page structure of Calendar View Mode
    function bookings_overview_in_calendar( $args ) { //return false;

        wpdevbk_booking_view_mode_buttons() ;
        wpdevbk_booking_calendar_overview_interface_header() ;                            // Show Filters and Action tabs

        make_bk_action('check_for_resources_of_notsuperadmin_in_booking_listing' );

	$args = wpdev_get_args_from_request_in_bk_overview_in_calendar();                    // Get safy PARAMS from REQUEST
        ?><textarea id="bk_request_params" style="display:none;"><?php echo  serialize($args) ; ?></textarea><?php

        $bk_listing = wpdev_get_bk_listing_structure_engine( $args );           // Get Bookings structure
        $bookings       = $bk_listing[0];
        $booking_types  = $bk_listing[1];
        $bookings_count = $bk_listing[2];
        $page_num       = $bk_listing[3];
        $page_items_count= $bk_listing[4];

//debuge($args, count($bookings),$bookings, $booking_types[$args['wh_booking_type']], $_REQUEST);

        booking_calendar_overview_table($bookings , $booking_types);


        wpdevbk_booking_listing_write_js();                                     // Wtite inline  JS
        wpdevbk_booking_listing_write_css();                                    // Write inline  CSS
    }


    // S T R U C T U R E   of   T O O L B A R for TABS and Buttons
    function wpdevbk_booking_calendar_overview_interface_header(){
        ?><div id="booking_listings_interface_header"><?php
            
            $_REQUEST['tab_cvm'] = 'actions_cvm'; // In calendar vew mode, we are have only one tab so need to activate it.

            wpdevbk_booking_calendar_overview_tabs_in_top_menu_line();

            if (! isset($_REQUEST['tab_cvm'])) $_REQUEST['tab_cvm'] = 'filter';
            $selected_title = $_REQUEST['tab_cvm'];

        ?>
            <div class="booking-submenu-tab-container" style="">
                <div class="nav-tabs booking-submenu-tab-insidecontainer">


                    <div class="visibility_container" id="actions"  style="<?php if ($selected_title == 'actions_cvm') { echo 'display:block;'; } else { echo 'display:none;'; }  ?>">
                        <?php wpdev_show_calendar_overview_interface(); ?>
                    </div>

                    <div class="visibility_container" id="help"     style="<?php if ($selected_title == 'help') { echo 'display:block;'; } else { echo 'display:none;'; }  ?>">
                    </div>

                </div>
            </div>
            <div style="height:1px;clear:both;margin-top:1px;"></div>
        </div>
        <div style="height:1px;clear:both;margin-top:15px;"></div>
        <?php
    }


    //  T A B s    in   Calendar overview  t o o l b a r
    function wpdevbk_booking_calendar_overview_tabs_in_top_menu_line() {

        $is_only_icons = ! true;
        if ($is_only_icons) echo '<style type="text/css"> #menu-wpdevplugin .nav-tab { padding:4px 2px 6px 32px !important; } </style>';
        $selected_icon = 'calendar-48x48.png';

        if (! isset($_REQUEST['tab_cvm'])) $_REQUEST['tab_cvm'] = 'actions_cvm';
        $selected_title = $_REQUEST['tab_cvm'];

        ?>
         <div style="height:1px;clear:both;margin-top:30px;"></div>
         <div id="menu-wpdevplugin">
            <div class="nav-tabs-wrapper">
                <div class="nav-tabs">

                    <?php $title = __('Actions', 'wpdev-booking'); $my_icon = 'calendar-48x48.png'; $my_tab = 'actions_cvm';  $my_additinal_class= ''; ?>
                    <?php if ($_REQUEST['tab_cvm'] == 'actions_cvm') {  $slct_a = 'selected'; $selected_title = $title; $selected_icon = $my_icon; } else {  $slct_a = ''; } ?><a class="nav-tab <?php if ($slct_a == 'selected') { echo ' nav-tab-active '; } echo $my_additinal_class;  ?>" title="<?php //echo __('Customization of booking form fields','wpdev-booking');  ?>"  href="#" onclick="javascript:jQuery('.visibility_container').hide(); jQuery('#<?php echo $my_tab; ?>').show();jQuery('.nav-tab').removeClass('nav-tab-active');jQuery(this).addClass('nav-tab-active');"><img class="menuicons" src="<?php echo WPDEV_BK_PLUGIN_URL; ?>/img/<?php echo $my_icon; ?>"><?php  if ($is_only_icons) echo '&nbsp;'; else echo $title; ?></a>

                    <?php wpdevbk_show_help_dropdown_menu_in_top_menu_line(); ?>

                </div>
            </div>
        </div>
        <?php

    }


    // B U T T O N S   In   Actions TAB from Toolbar
    function wpdev_show_calendar_overview_interface(){
          $user = wp_get_current_user(); $user_bk_id = $user->ID;
          ?>
          <?php if (function_exists('wpdebk_filter_field_bk_resources')) {    // Booking resource selections
                if (! isset($_REQUEST['wh_booking_type'])) $_REQUEST['wh_booking_type'] = get_bk_option( 'booking_default_booking_resource');
                ?>
                 <style type="text/css">
                    #wh_booking_type {
                        height: 28px;
                        margin-bottom: 5px;
                    }
                    .wpdevbk .control-group {
                        margin-bottom:0px;
                        margin-right:20px !important;
                    }
                    .btn-toolbar {
                      margin-right:20px !important;
                      margin-left:0px !important;
                      float:left;
                    }
                 </style>
                <?php
                wpdebk_filter_field_bk_resources(! true);
                ?>
                <script type="text/javascript">
                  jQuery(document).ready( function(){
                    jQuery("#wh_booking_type").chosen().change( function(va){ // Reload the page using this wh_booking_type=219 as
                        window.location.assign("<?php $bk_admin_url = get_params_in_url( array('wh_booking_type') );
                                                      echo $bk_admin_url . '&wh_booking_type='; ?>" + jQuery("#wh_booking_type").val() );
                    })
                  });
                </script><?php
          } ?>
          <?php 
            if (! isset($_REQUEST['view_days_num'])) $_REQUEST['view_days_num'] = get_bk_option( 'booking_view_days_num');
            $view_days_num = $_REQUEST['view_days_num'];
            $bk_admin_url = get_params_in_url( array('view_days_num') );
          
            wpdev_calendar_overview_buttons_view_mode($bk_admin_url, $view_days_num);
          ?>
         <div class="btn-toolbar" style="margin:10px 0px 0px 20px ;float:left;">
          <?php
              wpdev_calendar_overview_buttons_navigations();
          ?>
             <p class="help-block" style="margin:27px 0 0;"><?php _e('Calendar Navigation', 'wpdev-booking'); ?></p>
          </div>

          <div class="clear" style="height:1px;"></div>
          <div id="admin_bk_messages" style="margin:0px;"> </div>
          <div class="clear" style="height:1px;"></div>
        <?php
    }

        // View  mode of calendar Buttons
        function wpdev_calendar_overview_buttons_view_mode($bk_admin_url, $view_days_num) {
          ?>
            <div class="btn-toolbar" style="margin:3px 0px 0px 20px;float:left;">
                <div id="calendar_overview_number_of_days_to_show" class="btn-group" style="margin: 2px 0 0px;text-align: center;vertical-align: top;"  data-toggle="buttons-radio">
                    <a     data-original-title="<?php _e('Show month', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_30"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=30'; ?>"
                           /><?php _e('Month', 'wpdev-booking'); ?> <i class="icon-align-justify"></i></a>
                    <a     data-original-title="<?php _e('Show 3 months', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_90"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=90'; ?>"
                           /><?php _e('3 Months', 'wpdev-booking'); ?> <i class="icon-th-list"></i></a>
                    <a     data-original-title="<?php _e('Show year', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn btn_dn_365"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=365'; ?>"
                           /><?php _e('Year', 'wpdev-booking'); ?> <i class="icon-th"></i></a>
                </div>
                <script type="text/javascript">
                    jQuery('#calendar_overview_number_of_days_to_show .btn').button();
                    jQuery('#calendar_overview_number_of_days_to_show .btn.btn_dn_<?php echo $view_days_num; ?>').button('toggle');
                </script>
                <p class="help-block"><?php _e('Calendar view mode', 'wpdev-booking'); ?></p>
          </div>
          <?php
          return;
          ?>
            <div class="btn-toolbar" style="margin:4px 0px 0px 20px;float:left;">
                <div id="calendar_overview_number_of_days_to_show" class="btn-group" style="float: left;margin: 2px 0 4px;text-align: center;vertical-align: top;"  data-toggle="buttons-radio">
                    <a     data-original-title="<?php _e('Show day', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_1"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=1'; ?>"
                           /><?php _e('Day', 'wpdev-booking'); ?> <i class="icon-stop"></i></a>
                    <a     data-original-title="<?php _e('Show week', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_7"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=7'; ?>"
                           /><?php _e('Week', 'wpdev-booking'); ?> <i class="icon-th-large"></i></a>
                    <a     data-original-title="<?php _e('Show month', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_30"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=30'; ?>"
                           /><?php _e('Month', 'wpdev-booking'); ?> <i class="icon-th"></i></a>
                    <a     data-original-title="<?php _e('Show 3 months', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn_dn_90"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=90'; ?>"
                           /><?php _e('3 Months', 'wpdev-booking'); ?> <i class="icon-th-list"></i></a>
                    <a     data-original-title="<?php _e('Show year', 'wpdev-booking'); ?>"  rel="tooltip" class="tooltip_top btn btn btn_dn_365"
                           onclick="javascript:;"
                           href="<?php echo $bk_admin_url . '&view_days_num=365'; ?>"
                           /><?php _e('Year', 'wpdev-booking'); ?> <i class="icon-align-justify"></i></a>
                </div>
                <script type="text/javascript">
                    jQuery('#calendar_overview_number_of_days_to_show .btn').button();
                    jQuery('#calendar_overview_number_of_days_to_show .btn.btn_dn_<?php echo $view_days_num; ?>').button('toggle');
                </script>
                <p class="help-block"><?php _e('Calendar view mode', 'wpdev-booking'); ?></p>
          </div>
          <?php
        }

        // Navigation  Buttons
        function wpdev_calendar_overview_buttons_navigations() {

            if (isset($_REQUEST['view_days_num'])) $view_days_num = $_REQUEST['view_days_num'];
            else $view_days_num = get_bk_option( 'booking_view_days_num');

            switch ($view_days_num) {
                case '90':
                    if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                    else $scroll_day = 0;
                    $scroll_params = array( '&scroll_day='.intval($scroll_day-4*7),
                                            '&scroll_day='.intval($scroll_day-7),
                                            '&scroll_day=0',
                                            '&scroll_day='.intval($scroll_day+7 ),
                                            '&scroll_day='.intval($scroll_day+4*7) );
                    $scroll_titles = array(  __('Previous 4 weeks', 'wpdev-booking'),
                                             __('Previous week', 'wpdev-booking'),
                                             __('Current week', 'wpdev-booking'),
                                             __('Next week', 'wpdev-booking'),
                                             __('Next 4 weeks', 'wpdev-booking') );
                    break;
                case '30':
                    if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                    else $scroll_day = 0;
                    $scroll_params = array( '&scroll_day='.intval($scroll_day-4*7),
                                            '&scroll_day='.intval($scroll_day-7),
                                            '&scroll_day=0',
                                            '&scroll_day='.intval($scroll_day+7 ),
                                            '&scroll_day='.intval($scroll_day+4*7) );
                    $scroll_titles = array(  __('Previous 4 weeks', 'wpdev-booking'),
                                             __('Previous week', 'wpdev-booking'),
                                             __('Current week', 'wpdev-booking'),
                                             __('Next week', 'wpdev-booking'),
                                             __('Next 4 weeks', 'wpdev-booking') );
                    break;
                default:  // 365
                    if (! isset($_REQUEST['scroll_month'])) $_REQUEST['scroll_month'] = 0;
                    $scroll_month = $_REQUEST['scroll_month'];
                    $scroll_params = array( '&scroll_month='.intval($scroll_month-3),
                                            '&scroll_month='.intval($scroll_month-1),
                                            '&scroll_month=0',
                                            '&scroll_month='.intval($scroll_month+1 ),
                                            '&scroll_month='.intval($scroll_month+3) );
                    $scroll_titles = array(  __('Previous 3 months', 'wpdev-booking'),
                                             __('Previous month', 'wpdev-booking'),
                                             __('Current month', 'wpdev-booking'),
                                             __('Next month', 'wpdev-booking'),
                                             __('Next 3 months', 'wpdev-booking') );
                    break;
            }

            $bk_admin_url = get_params_in_url( array('scroll_month', 'scroll_day') );
          ?>
                <div class="btn-group" style="height: 18px; margin: -5px -5px 0 0; vertical-align: top;float:right;">
                    <a     data-original-title="<?php echo $scroll_titles[0]; ?>"  rel="tooltip" class="tooltip_top btn "
                           href="<?php echo $bk_admin_url .$scroll_params[0].''; ?>"       /><i class="icon-backward"></i></a>
                    <a     data-original-title="<?php echo $scroll_titles[1]; ?>"  rel="tooltip" class="tooltip_top btn btn"
                           href="<?php echo $bk_admin_url .$scroll_params[1].''; ?>"        /><i class="icon-chevron-left"></i></a>

                    <a     data-original-title="<?php echo $scroll_titles[2]; ?>"  rel="tooltip" class="tooltip_top btn btn"
                           href="<?php echo $bk_admin_url .$scroll_params[2]; ?>"        /><i class="icon-screenshot"></i></a>

                    <a     data-original-title="<?php echo $scroll_titles[3]; ?>"  rel="tooltip" class="tooltip_top btn btn"
                           href="<?php echo $bk_admin_url .$scroll_params[3].''; ?>"       /><i class="icon-chevron-right"></i></a>
                    <a     data-original-title="<?php echo $scroll_titles[4]; ?>"  rel="tooltip" class="tooltip_top btn btn"
                           href="<?php echo $bk_admin_url .$scroll_params[4].''; ?>"       /><i class="icon-forward"></i></a>
                </div>
          <?php
        }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //  Bookings Calendar Overview  --  T A B L E      /////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    // B o o k i n g     C a l e n d a r    O v e r v i e w    T a b l e
    function booking_calendar_overview_table($bookings , $booking_types) {

        $user = wp_get_current_user(); $user_bk_id = $user->ID;

        $bk_url_listing     = 'admin.php?page=' . WPDEV_BK_PLUGIN_DIRNAME . '/'. WPDEV_BK_PLUGIN_FILENAME . 'wpdev-booking' ;
        $bk_url_add         = $bk_url_listing . '-reservation' ;
        $bk_url_resources   = $bk_url_listing . '-resources' ;
        $bk_url_settings    = $bk_url_listing . '-option' ;

        $fixed_time_hours_array = array( );
        for ($tt = 0; $tt < 24; $tt++) {
            $fixed_time_hours_array[ $tt * 60 * 60 ] = array();
        } /* Example: Array (     [0] => array(), [3600] =>  array(), [7200] => array(), ..... [43200] => array(),.... [82800] => array()   )*/


        // Dates array: { '2012-12-24' => array( Booking ID 1, Booking ID 2, ....), ... }
        $dates_array = $time_array = array();
        foreach ($bookings as $bk) {
            foreach ($bk->dates as $dt) {

                // Transform from MySQL date to PHP date
                $dt->booking_date = trim($dt->booking_date);
                $dta = explode(' ',$dt->booking_date);
                $tms = $dta[1];
                $tms = explode(':' , $tms);             // array('13','30','40')
                $dta = $dta[0];
                $dta = explode('-',$dta);               // array('2012','12','30')
                $php_dt = mktime($tms[0], $tms[1], $tms[2], $dta[1], $dta[2], $dta[0]) ;

                $my_date  = date("Y-m-d", $php_dt);    // '2012-12-01';
                if (! isset( $dates_array[$my_date] )) { $dates_array[$my_date] = array($bk->booking_id); }
                else                                   { $dates_array[$my_date][] = $bk->booking_id; }

                $my_time  = date("H:i:s", $php_dt);    // '21:55:01';

                $my_time_index = explode(':',$my_time);
                $my_time_index = (int)($my_time_index[0]*60*60 + $my_time_index[1]*60 + $my_time_index[2]);

                if (! isset( $time_array[$my_date] )) { $time_array[$my_date] = array( $my_time_index => array($my_time =>$bk->booking_id) ); }
                else {
                    if (! isset( $time_array[$my_date][$my_time_index] ) )
                        $time_array[$my_date][$my_time_index] = array($my_time =>$bk->booking_id);
                    else {
                        if (! isset( $time_array[$my_date][$my_time_index][$my_time] ) )
                            $time_array[$my_date][$my_time_index][$my_time] = $bk->booking_id ;
                        else {
                            $my_time_inc = 3;
                            while ( isset( $time_array[$my_date][$my_time_index][$my_time + $my_time_inc ] ) ) {
                                $my_time_inc++;
                            }
                            $time_array[$my_date][$my_time_index][($my_time+$my_time_inc)] = $bk->booking_id ; //Just in case if we are have the booking in the same time, so we are
                        }
                    }
               }

            }
        }
//debuge($time_array);
        // Sorting ..........
        foreach ($time_array as $key=>$value_t) {   // Sort the times from lower to higher
            ksort($value_t);
            $time_array[$key]=$value_t;
        }
        ksort($time_array);                         // Sort array by dates from lower to higher.
    //debuge($time_array);
        /* $time_array:
         $key_date     $value_t
        [2012-12-13] => Array ( $tt_index          $times_bk_id_array
                                [44401] => Array ( [12:20:01] => 19)
                              ),
        [2012-12-14] => Array (
                                [10802] => Array([03:00:02] => 19),
                                [43801] => Array([12:10:01] => 2)
                               ),
                .... */

        $time_array_new = array();
        foreach ($time_array as $key_date=>$value_t) {   // fill the $time_array_new - by bookings of full dates....

            $new_times_array = $fixed_time_hours_array;             // Array ( [0] => Array, [3600] => Array, [7200] => Array .....

            foreach ($value_t as $tt_index=>$times_bk_id_array) {   //  [44401] => Array ( [12:20:01] => 19 ), .....
                
                $tt_index_round = floor( ($tt_index/60)/60 ) * 60 * 60;         // 14400, 18000,
                $is_bk_for_full_date = $tt_index % 10;                          // 0, 1, 2

                switch ($is_bk_for_full_date) {
                    case 0:                                                         // Full date - fill every time slot
                        foreach ($new_times_array as $round_time_slot=>$bk_id_array) {
                            $new_times_array[$round_time_slot] = array_merge( $bk_id_array , array_values($times_bk_id_array) );
                        }
                        unset($time_array[$key_date][$tt_index]);
                        break;

                    case 1:     break;
                    case 2:     break;
                    default:    break;
                }

            }
            if (count($time_array[$key_date])==0) unset($time_array[$key_date]) ;

            $time_array_new[$key_date]=$new_times_array;
        }

        foreach ($time_array as $key_date=>$value_t) {
            $new_times_array_for_day_start = $new_times_array_for_day_end = array();
            foreach ($value_t as $tt_index=>$times_bk_id_array) {   //  [44401] => Array ( [12:20:01] => 19 ), .....

                $tt_index_round = floor( ($tt_index/60)/60 ) * 60 * 60;         // 14400, 18000,
                $is_bk_for_full_date = $tt_index % 10;                          // 0, 1, 2

                if ($is_bk_for_full_date==1) {
                    if (! isset($new_times_array_for_day_start[$tt_index_round])) $new_times_array_for_day_start[$tt_index_round] = array();
                    $new_times_array_for_day_start[$tt_index_round] = array_merge($new_times_array_for_day_start[$tt_index_round] , array_values($times_bk_id_array) );
                }
                if ($is_bk_for_full_date==2) {
                    if (! isset($new_times_array_for_day_end[$tt_index_round])) $new_times_array_for_day_end[$tt_index_round] = array();
                    $new_times_array_for_day_end[$tt_index_round]   = array_merge($new_times_array_for_day_end[$tt_index_round] , array_values($times_bk_id_array) );
                }
            }
            $time_array[$key_date] = array('start'=>$new_times_array_for_day_start, 'end'=>$new_times_array_for_day_end);
        }
            /* $time_array
            [2012-12-24] => Array
                (
                    [start] => Array (
                                        [68400] => Array ( [0] => 15 ) )
                    [end] => Array (
                                        [64800] => Array ( [0] => 6 ) )

                )    */
        $fill_this_date = array();

        foreach ($time_array_new as $ddate=>$ttime_round_array ) {
            foreach ($ttime_round_array as $ttime_round => $bk_id_array ) {

                if ( isset( $time_array[$ddate] )) {

                    if ( isset( $time_array[$ddate]['start'][$ttime_round]  )) // array
                          $fill_this_date = array_merge($fill_this_date, array_values( $time_array[$ddate]['start'][$ttime_round] ) );

                    $time_array_new[$ddate][$ttime_round] = array_merge($time_array_new[$ddate][$ttime_round], $fill_this_date );


                    if ( isset( $time_array[$ddate]['end'][$ttime_round]  )) // array
                        foreach ($time_array[$ddate]['end'][$ttime_round] as $toDelete) {
                          $fill_this_date=array_diff($fill_this_date, array($toDelete));
                        }
                          

                }
            }
        }

//debuge($time_array_new);

        $is_single_resource = true;
        if ( $is_single_resource ) {
            calendar_for_one_resource( $dates_array, $bookings, $booking_types, $time_array_new );
        } else
            calendar_for_multiple_resources( $dates_array, $bookings, $booking_types );

    }



    function calendar_for_one_resource( $dates_array, $bookings, $booking_types, $time_array_new = array() ){


            $start_year = date("Y");        // 2012
            $start_month = date("m");       // 09
            
            $view_days_num = $_REQUEST['view_days_num'];                          // Get start date and number of rows, which is depend from the view days mode
            switch ($view_days_num) {
                case '90':
                    if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                    else $scroll_day = 0;

                    $max_rows_number = 12;

                    $start_day = date("d");
                    $start_week_day_num = date("w");
                    $start_day_weeek  = get_bk_option( 'booking_start_day_weeek' ); //[0]:Sun .. [6]:Sut

                    if ($start_week_day_num != $start_day_weeek) {
                        for ($d_inc = 1; $d_inc < 8; $d_inc++) {                // Just get week  back
                            $real_date = mktime(0, 0, 0, $start_month, ($start_day-$d_inc ) , $start_year);

                            $start_week_day_num = date("w", $real_date);
                            if ($start_week_day_num == $start_day_weeek) {
                                $start_day = date("d", $real_date);
                                $start_year  = date("Y", $real_date);
                                $start_month = date("m", $real_date);
                                break;
                            }
                        }
                    }
                    break;

                case '365':
                    if (isset($_REQUEST['scroll_month'])) $scroll_month = $_REQUEST['scroll_month'];
                    else $scroll_month = 0;
                    $max_rows_number = 12;
                    $start_day = 1;
                    break;

                default:  // 30
                    if (isset($_REQUEST['scroll_day'])) $scroll_day = $_REQUEST['scroll_day'];
                    else $scroll_day = 0;

                    $max_rows_number = 31;
                    $start_day = date("d");
                    break;
            }

        ?><div class="bookings_overview_in_calendar_frame"><table class="bookings_overview_in_calendar booking_table table table-striped" cellpadding="0" cellspacing="0">
            <tr>
                <th style="width:200px;"><?php  //_e('','wpdev-booking');?></th>
                <th style="text-align:center;"><?php  _e('Dates','wpdev-booking'); //wpdev_calendar_overview_buttons_navigations();?></th>
            </tr>
            <tr><td colspan="2"> </td></tr>
            <tr>
                <td class="bk_resource_selector"></td>
                <td style="padding:0px;"><?php                                  // Header above the calendar table
                    $real_date = mktime(0, 0, 0, ($start_month), $start_day , $start_year);
                    wpdev_calendar_timeline('-1', true,  $real_date );?></td>
            </tr>
        <?php
            for ($d_inc = 0; $d_inc < $max_rows_number; $d_inc++) {

                switch ($view_days_num) {                                        // Set real start date for the each rows in calendar
                    case '90':
                        $real_date = mktime(0, 0, 0, $start_month, ( $start_day + $d_inc*7 + $scroll_day ) , $start_year);
                        break;

                    case '365':
                        $real_date = mktime(0, 0, 0, ($start_month+$d_inc + $scroll_month ), $start_day , $start_year);
                        break;

                    default:  // 30
                        $real_date = mktime(0, 0, 0, $start_month, ( $start_day + $d_inc + $scroll_day ) , $start_year);
                        break;
                }

              ?>
              <tr>
                <td style="border-right:2px solid #CC5544;">
                    <div class="resource_title"><?php
                        switch ($view_days_num) {                                // Title in first collumn of the each row in calendar
                            case '90':
                                $end_real_date = mktime(0, 0, 0, $start_month, ( $start_day + $d_inc*7 + $scroll_day )+6 , $start_year);
                                $date_format = 'M j, Y';//get_bk_option( 'booking_date_format');
                                echo date( $date_format , $real_date) . ' - ' . date( $date_format , $end_real_date);
                                break;

                            case '365':
                                echo date("F", $real_date) . ', ' . date("Y", $real_date);
                                break;

                            default:  // 30
                                 $date_format = 'D, d / m / Y';
                                echo date( $date_format , $real_date);
                                break;
                        }
                  ?></div>
                </td>
                <td  style="padding:0px;">
                    <div class="resource_dates"><?php wpdev_calendar_timeline('-1', false, $real_date, $dates_array, $bookings, $booking_types, $time_array_new);?></div>
                </td>
              </tr>
            <?php }

        ?></table></div><?php
    }


    function calendar_for_multiple_resources( $dates_array, $bookings, $booking_types ){

        if (isset($_REQUEST['scroll_month'])) $scroll_month = $_REQUEST['scroll_month'];
        else $scroll_month = 0;

        $start_year = date("Y");    //2012
        $start_month = date("m");    //09
        $start_day = 1;//date("d");//1;    //31

        ?><div class="bookings_overview_in_calendar_frame"><table class="bookings_overview_in_calendar booking_table table table-striped" cellpadding="0" cellspacing="0">
            <tr>
                <th style="width:200px;"><?php  _e('Resource','wpdev-booking');?></th>
                <th style="text-align:center;"><?php  _e('Dates','wpdev-booking'); wpdev_calendar_overview_buttons_navigations();?></th>
            </tr>
            <?php  // Several Resources?>
            <tr>
                <td style="border-right:2px solid #CC5544;"><?php ?></td>
                <td style="padding:0px;"><?php wpdev_calendar_timeline();?></td>
            </tr>
            <?php
// /* For test only */ for ($kk = 0; $kk < 3; $kk++)
            foreach ($booking_types as $resource_id=>$resource_value) {
            ?>
                <tr>
                    <td style="border-right:2px solid #CC5544;">
                        <div class="resource_id"><?php echo $resource_id; ?></div>
                        <div class="resource_title <?php if (isset($resource_value->parent)){  if ($resource_value->parent == 0) {echo 'parent';} else {echo 'child';}  } ?> "><?php echo $resource_value->title; ?></div>
                    </td>
                    <td  style="padding:0px;">
                        <div class="resource_dates"><?php wpdev_calendar_timeline($resource_id, false);?></div>
                    </td>
                </tr>
            <?php } ?>
        </table></div><?php
    }



    // M o n t h    T I M E L I N E   in the head of the calendar overview table
    function wpdev_calendar_timeline($resource_id = 0, $is_show_cell_content = true, $start_date = false, $booked_dates_array = false, $bookings = false, $booking_types = false, $time_array_new = array() ){
//debuge($bookings , $booking_types);
//debuge('29/1.049qps');
//debugq();

        $bk_url_listing     = 'admin.php?page=' . WPDEV_BK_PLUGIN_DIRNAME . '/'. WPDEV_BK_PLUGIN_FILENAME . 'wpdev-booking' ;

        // Initial  params
        $is_show_week_days      = true;                                         // Just a little different design of showing dates

        $view_days_num = $_REQUEST['view_days_num'];

        if ($resource_id == '-1') {                                             // Single booking resource

            switch ($view_days_num) {
                case '90':
                    $days_num = 7;
                    $cell_width = '13.8%';
                    $dwa = array(1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday'),7=>__('Sunday'),);
                    $time_selles_num  = 1;
                    break;
                case '365':
                    $days_num = 32;
                    $cell_width = '3%';
                    $dwa = array(1=>__('M'),2=>__('Tu'),3=>__('W'),4=>__('Th'),5=>__('F'),6=>__('Sa'),7=>__('Su'),);
                    $time_selles_num  = 1;
                    $is_show_week_days      = false;
                    break;
                default:  // 30
                    $days_num = 1;
                    $cell_width =  '99%';;
                    $dwa = array(1=>__('Mon'),2=>__('Tue'),3=>__('Wed'),4=>__('Thu'),5=>__('Fri'),6=>__('Sat'),7=>__('Sun'),);
                    $time_selles_num  = 24;//25;
                    //$view_days_num = 1;
                    break;
            }

        } else {                                                                // Multiple booking resources

            switch ($view_days_num) {
                case '1':
                    $days_num = 35;
                    $cell_width = (7*4.75*31) . 'px';
                    $dwa = array(1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday'),7=>__('Sunday'),);
                    $time_selles_num  = 24;
                    break;
                case '7':
                    $days_num = 65;
                    $cell_width = (4.75*31) . 'px';
                    $dwa = array(1=>__('Monday'),2=>__('Tuesday'),3=>__('Wednesday'),4=>__('Thursday'),5=>__('Friday'),6=>__('Saturday'),7=>__('Sunday'),);
                    $time_selles_num  = 4;
                    break;
                case '90':
                    $days_num = 180;
                    $cell_width = (12) . 'px';;
                    $dwa = array(1=>__('M'),2=>__('Tu'),3=>__('W'),4=>__('Th'),5=>__('F'),6=>__('Sa'),7=>__('Su'),);
                    $time_selles_num  = 1;
                    break;
                case '365':
                    $days_num = 365;
                    $cell_width = (2) . 'px';;
                    $is_show_week_days      = false;
                    $time_selles_num  = 1;
                    break;

                default:  // 30
                    $days_num = 95;
                    $cell_width = (31) . 'px';;
                    $dwa = array(1=>__('Mon'),2=>__('Tue'),3=>__('Wed'),4=>__('Thu'),5=>__('Fri'),6=>__('Sat'),7=>__('Sun'),);
                    $time_selles_num  = 2;//25;
                    break;
            }

        }

        if ($start_date === false) {
            $start_year     = date('Y') ;
            $start_month    = date('n') ;
            $start_day      = date('j') ;
        } else {
            $start_year     = date("Y", $start_date);    //2012
            $start_month    = date("m", $start_date);    //09
            $start_day      = date("d", $start_date);    //31
        }

        $previous_booking_id = false;

  
        ?>

        <div class="container-fluid <?php if ($resource_id == '-1') echo ' single_resource '; ?>"><div class="row-fluid"><div class="span12">

        <div id="timeline_scroller<?php  echo $resource_id; ?>" class="calendar_timeline_scroller" style="<?php if ($resource_id == '-1') echo ' width:100%; ';?>">

        <div class="calendar_timeline_frame"  style="<?php  if ($is_show_cell_content) echo 'border-bottom: 1px solid #DD8800;height: 33px !important;';  if ($resource_id == '-1') echo ' width:100%; '; ?>">
            <?php
            $is_approved = false;
            $previous_month = '';
            for ($d_inc = 0; $d_inc < $days_num; $d_inc++) {

                $real_date = mktime(0, 0, 0, $start_month, ($start_day+$d_inc) , $start_year);

                if (date('m.d.Y') == date("m.d.Y", $real_date) ) $is_today = ' today_date ';
                else  $is_today = '';

                $yy = date("Y", $real_date);    //2012
                $mm = date("m", $real_date);    //09
                $dd = date("d", $real_date);    //31
                $ww = date("N", $real_date);    //7

                $day_week = $dwa[$ww];          //Su

                $day_title = $dd;
                if ($view_days_num==1) {
                  $day_title =  wpdevbk_get_date_in_correct_format( $yy.'-'.$mm.'-'.$dd.' 00:00:00');
                  $day_title  = $day_week . ', ' .  $day_title[0];
                  $is_show_week_days      = false;
                }
                if ($view_days_num==7) {
                  $day_title =  wpdevbk_get_date_in_correct_format( $yy.'-'.$mm.'-'.$dd.' 00:00:00');
                  $day_title  = $day_week . ', ' .  $dd;
                  $is_show_week_days      = false;
                }
                if ($view_days_num==30) {
                  $day_title  = __('Times','wpdev-booking');
                  $is_show_week_days      = false;
                }

                $day_filter_id = $yy.'-'.$mm.'-'.$dd;

                if ($previous_month != $mm) {
                    $previous_month = $mm;
                    $month_title = date("F", $real_date);    //09
                    $month_class = ' new_month ';
                } else {
                    $month_title = '';
                    $month_class = '';
                }

                if ( ($d_inc> 0 ) && ($resource_id == '-1') && ($month_class == ' new_month ') && ($view_days_num == '365') ) {
                    ?>
                    <div id="cell_<?php  echo $resource_id . '_' . $day_filter_id ; ?>" class="calendar_overview_cell cell_header  time_in_days_num_<?php echo $view_days_num;?>  weekday<?php echo $ww ?><?php echo  ' '.$day_filter_id.' '.$month_class ; ?>" style="<?php echo 'width:1px;'; ?>">
                    </div>
                    <?php
                    break;
                }

                if ($is_show_cell_content ) {    // H e a d e r     ?>
                <div id="cell_<?php  echo $resource_id . '_' . $day_filter_id ; ?>" class="calendar_overview_cell cell_header  time_in_days_num_<?php echo $view_days_num;?>  weekday<?php echo $ww ?><?php echo  ' '.$day_filter_id.' '.$month_class ; ?>" style="<?php echo 'width:' . $cell_width . ';'; ?>">

                        <?php if ($month_title != '') { ?>
                              <div class="month_year"><?php echo $month_title .', ' . $yy ;?></div>
                        <?php } ?>
                       <div class="day_num"><?php echo $day_title;?></div>
                       <div class="day_week"><?php if ($is_show_week_days) { echo $day_week; }?></div>
                       <?php
                        // T i m e   c e l l s
                        $tm = floor(24 / $time_selles_num);
                        for ($tt = 0; $tt < $time_selles_num; $tt++) {
                            echo '<div class="time_section_in_day time_section_in_day_header time_hour'.($tt*$tm).' time_in_days_num_'.$view_days_num.'">'.  ( ($view_days_num<31)? (( ($tt*$tm) < 10?'0':'').($tt*$tm).':00'):'' )  .'</div>';
                        }
                       ?>
                </div>
                <?php
                } else {                        // C o n t e n t
/*
?> </div></div></div> <?php
ksort($booked_dates_array);
debuge($bookings);die;/**/
                    ?><div  id="cell_<?php  echo $resource_id . '_' . $day_filter_id ; ?>"
                          class="calendar_overview_cell weekday<?php echo $ww . ' ';  echo $is_today; echo  ' '.$day_filter_id.' '.$month_class ; ?>"
                          style="<?php echo 'width:' . $cell_width . ';'; ?>"                          
                          >
                          <?php
                           // Just show current date in calendar.
                           $tooltip_date = wpdevbk_get_date_in_correct_format( $yy.'-'.$mm.'-'.$dd.' 00:00:00', 'Y / m / d, D');
                           echo '<a  class="calendar_inday_title tooltip_top"
                                      rel="tooltip" data-original-title="'.$tooltip_date[0].'"
                                   >'.$dd.( ($view_days_num=='90')?'/'.$mm:'') .'</a>';

                           $title_in_day = $title = $title_hint ='';
                           $is_bk = 0;



                           if ($time_selles_num != 24) {  //  Full     D a t e s

                              if ($booked_dates_array !== false) {

                                   if ( isset($booked_dates_array[ $day_filter_id ]) ) {    // This date is    B O O K E D

                                       $is_bk = 1;
                                       $booked_dates_array[ $day_filter_id ] = array_unique($booked_dates_array[ $day_filter_id ]);
                                       foreach ($booked_dates_array[ $day_filter_id ] as $bk_id ) {

                                           $booking_num_in_day = count($booked_dates_array[ $day_filter_id ]);
                                           if( ($previous_booking_id != $bk_id) || ($booking_num_in_day>1) ){
                                               // if the booking take for the several  days, so then do not show title in the other days
                                               $my_bk_info = get_booking_info_4_tooltip( $bk_id , $bookings, $booking_types, $title_in_day , $title , $title_hint );

                                               $title_in_day = $my_bk_info[0];
                                               $title        = $my_bk_info[1];
                                               $title_hint   = $my_bk_info[2];
                                               $is_approved  = $my_bk_info[3];
                                           }
                                           if ($booking_num_in_day>1) {
                                               $previous_booking_id .= ',' . $bk_id;
                                           } else $previous_booking_id = $bk_id;
                                       }

                                   } else $previous_booking_id = false;


                                    // Just one day cell

                                    $title_hint = str_replace('"', "", $title_hint)   ;
                                    $link_id_parameter = str_replace(' / ', ',', $title);
                                    if ( strpos($title_in_day, ',') !== false) {
                                        $title_in_day = explode(',', $title_in_day) ;
                                        $title_in_day = $title_in_day[0] . '..' . $title_in_day[ (count($title_in_day)-1) ];
                                        $title_in_day = '<span style=\'font-size:7px;\'>' . $title_in_day . '</span>';
                                    }

                                    // Show the circle with  bk ID(s) in a day
                                    echo '<a  href="'.$bk_url_listing.'&wh_booking_id='.$link_id_parameter.'&view_mode=vm_listing&tab=actions"
                                          data-content="<div class=\'\'>'.$title_hint.'</div>"
                                          data-original-title="'.'ID: '.$title.'"
                                          rel="popover" class="popover_left  ' . ( ($title!='')?'first_day_in_bookin':'' ).' ">'.$title_in_day.'</a>';

                                    $tm = floor(24 / $time_selles_num);
                                    $tt = 0 ;
                                    $my_bkid_title = '';
                                    echo '<div class="time_section_in_day timeslots_in_this_day' . $time_selles_num .
                                                     ' time_hour'.($tt*$tm).'  time_in_days_num_'.$view_days_num.' '.
                                                     ( $is_bk ?'time_booked_in_day':'' ).' '.( $is_approved ?'approved':'' ).
                                                   ' ">'.
                                    (($is_bk)?($my_bkid_title):'').
                                    '</div>';

                              }

                           }


                           if ($time_selles_num ==24 ) {  // Time Slots in a date
                                if ( isset($time_array_new[ $day_filter_id ]) ) {


                                // Loop time cells  /////////////////////////////////////////////////////////////////////////////////////////////////
                                $tm = floor(24 / $time_selles_num);
                                for ($tt = 0; $tt < $time_selles_num; $tt++) {

                                    $my_bk_id_array = $time_array_new[$day_filter_id][$tt *60 * 60] ;
                                    $my_bk_id_array = array_unique($my_bk_id_array); //remove dublicates

                                    if (empty($my_bk_id_array)) {   // Time cell  is    E m p t y

                                        $is_bk = 0;
                                        $previous_booking_id = false;
                                        $my_bkid_title = $title_in_day = $title = $title_hint ='';

                                    } else {                        // Time cell is     B O O K E D
                                        $is_bk = 1;

                                        if( ($previous_booking_id !== $my_bk_id_array) || ($previous_booking_id === false) ){
                                           $my_bkid_title = $title_in_day = $title = $title_hint ='';
                                           foreach ($my_bk_id_array as $bk_id) {

                                               $my_bk_info = get_booking_info_4_tooltip( $bk_id , $bookings, $booking_types, $title_in_day , $title , $title_hint );

                                               $title_in_day = $my_bk_info[0];
                                               $title        = $my_bk_info[1];
                                               $title_hint   = $my_bk_info[2];
                                               $is_approved  = $my_bk_info[3];
                                           }

                                        } else {
                                            $my_bkid_title = $title_in_day = $title = $title_hint ='';
                                        }
                                        $previous_booking_id = $my_bk_id_array;


                                        $title_hint = str_replace('"', "", $title_hint)   ;
                                        $link_id_parameter = str_replace(' / ', ',', $title);
                                        if ( strpos($title_in_day, ',') !== false) {
                                            $title_in_day = explode(',', $title_in_day) ;
                                            $title_in_day = $title_in_day[0] . '..' . $title_in_day[ (count($title_in_day)-1) ];
                                            $title_in_day = '<span style=\'font-size:7px;\'>' . $title_in_day . '</span>';
                                        }

                                        // Show the circle with  bk ID(s) in a day
                                        $my_bkid_title = '<a  href="'.$bk_url_listing.'&wh_booking_id='.$link_id_parameter.'&view_mode=vm_listing&tab=actions"
                                         data-content="<div class=\'\'>'.$title_hint.'</div>"
                                         data-original-title="'.'ID: '.$title.'"
                                         rel="popover" class="popover_left  ' . ( ($title!='')?'first_day_in_bookin':'' ).' ">'.$title_in_day.'</a>';
                                    }


                                    echo '<div class="time_section_in_day timeslots_in_this_day' . $time_selles_num .
                                                     ' time_hour'.($tt*$tm).'  time_in_days_num_'.$view_days_num.' '.
                                                     ( $is_bk ?'time_booked_in_day':'' ).' '.( $is_approved ?'approved':'' ).
                                                   ' ">'.
                                    (($is_bk)?($my_bkid_title):'').
                                    '</div>';
                                } //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                               } else { // Just  time borders
                                    $tm = floor(24 / $time_selles_num);
                                    for ($tt = 0; $tt < $time_selles_num; $tt++) {
                                        echo '<div class="time_section_in_day timeslots_in_this_day' . $time_selles_num .
                                                         ' time_hour'.($tt*$tm).'  time_in_days_num_'.$view_days_num.' '.
                                                         ( $is_bk ?'time_booked_in_day':'' ).' '.( $is_approved ?'approved':'' ).
                                                       ' ">'.
                                        (($is_bk)?($my_bkid_title):'').
                                        '</div>';
                                    }
                               }

                           }

                           ?>
                        <div class="day_line"></div>
                    </div><?php
                }

            } ?>
        </div>

        </div>

        </div></div></div>
        <?php
    }


        // Get info  for mouse over tooltip in admin panel in calendar.
        function get_booking_info_4_tooltip( $bk_id, $bookings, $booking_types, $title_in_day='', $title='', $title_hint=''  ){


           //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
           // Gathering data  about the booking to  show in the calendar !!! ////////////////////////////////////////////////////
           //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            // Get it from the option settings
            $what_show_in_day_template = get_bk_option( 'booking_default_title_in_day_for_calendar_view_mode' );// '<span style="font-size:07px;">[id]</span>:[name]';


           if ($title != '')            $title .= ' / ';                            // Other Booking in the same day
           $title        .=  $bk_id ;

           if ($title_in_day != '')     $title_in_day .= ',';           // Other Booking in the same day
           //$title_in_day .=  $bk_id ;
           if (function_exists ('get_title_for_showing_in_day')) {
                $title_in_day .= get_title_for_showing_in_day($bk_id, $bookings, $what_show_in_day_template);
           } else {
               $title_in_day .=  $bk_id .':'. $bookings[$bk_id]->form_data['_all_fields_'][ 'name' ];
           }

           if ($title_hint != '') $title_hint .= ' <hr style="margin:10px 5px;" /> ';   // Other Booking in the same day

           $title_hint .= '<div class=\'booking-listing-collumn\' >';


           if (function_exists ('get_booking_title')) {

                if (isset($booking_types[$bookings[$bk_id]->booking_type]))
                     $bk_title = $booking_types[$bookings[$bk_id]->booking_type]->title;
                else $bk_title = get_booking_title( $bookings[$bk_id]->booking_type );
                $bk_title = '<span class=\'label label-resource label-info\'>' . $bk_title . '</span>' ;
           } else $bk_title = '';
           $title_hint .= '<span class=\'field-id\'>'.$bk_id.'</span>' . ' '. $bk_title;


           if (class_exists('wpdev_bk_biz_s')) {
                $title_hint .= '<div style=\'float:right;\'>';
                if (function_exists ('wpdev_bk_get_payment_status_simple')) {
                    $pay_status = wpdev_bk_get_payment_status_simple( $bookings[$bk_id]->pay_status );
                    $pay_status = '<span class=\'label label-payment-status payment-label-unknown\'><span style=\'font-size:07px;\'>'.__('Payment','wpdev-booking').'</span> '.$pay_status.'</span>';
                } else $pay_status = '';
                $title_hint .= ' '. $pay_status;

                $currency = apply_bk_filter( 'get_currency_info' );
                $title_hint .= ' <div class="cost-fields-group" style=\'float:right; margin:2px;\'>'.$currency.' '. $bookings[$bk_id]->cost .'</div>';
                $title_hint .= '</div>';
           }

           $title_hint .= '<div>'. $bookings[$bk_id]->form_show .'</div>';//$bookings[$bk_id]->form_data['name'].' ' . $bookings[$bk_id]->form_data['secondname'] ;

           //$title_hint .= ' '. $bookings[$bk_id]->remark;

           //BL
           $bk_dates_short_id = array(); if (count($bookings[$bk_id]->dates) > 0 ) $bk_dates_short_id      = (isset($bookings[$bk_id]->dates_short_id))?$bookings[$bk_id]->dates_short_id:array();      // Array ([0] => [1] => .... [4] => 6... [11] => [12] => 8 )

           $is_approved = 0;   if (count($bookings[$bk_id]->dates) > 0 )     $is_approved = $bookings[$bk_id]->dates[0]->approved ;
           $short_dates_content = wpdevbk_get_str_from_dates_short($bookings[$bk_id]->dates_short, $is_approved , $bk_dates_short_id , $booking_types );
           $short_dates_content = str_replace('"', "'", $short_dates_content);
           $title_hint .= '<div style=\'margin-top:5px;\'>' . $short_dates_content . '</div>';

           $title_hint .= '</div>';
           //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
           //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

           return( array($title_in_day, $title, $title_hint, $is_approved) );
        }

?>