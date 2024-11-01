<?php

defined( 'ABSPATH' ) || exit;
if ( !class_exists( 'WPSE_EDD_Downloads_Sheet' ) ) {
    class WPSE_EDD_Downloads_Sheet extends WPSE_Sheet_Factory {
        function __construct() {
            $allowed_columns = array();
            $allowed_columns = array(
                'ID',
                'post_title',
                'post_content',
                'post_status',
                'post_name',
                'view_post',
                '_thumbnail_id',
                'edd_price',
                'edd_download_files_file',
                'edd_download_files_name'
            );
            parent::__construct( array(
                'fs_object'          => wpseedd_fs(),
                'post_type'          => array('download'),
                'post_type_label'    => array('EDD Downloads'),
                'serialized_columns' => array(),
                'columns'            => array($this, 'get_columns'),
                'allowed_columns'    => $allowed_columns,
                'remove_columns'     => array(),
            ) );
            add_filter(
                'vg_sheet_editor/provider/post/update_item_meta',
                array($this, 'filter_cell_data_for_saving'),
                10,
                3
            );
            add_filter(
                'vg_sheet_editor/provider/post/get_item_meta',
                array($this, 'filter_cell_data_for_readings'),
                10,
                5
            );
            add_filter(
                'vg_sheet_editor/serialized_addon/column_settings',
                array($this, 'filter_serialized_column_settings'),
                10,
                5
            );
            add_filter(
                'vg_sheet_editor/columns/blacklisted_columns',
                array($this, 'blacklist_index_column'),
                10,
                2
            );
            add_filter( 'vg_sheet_editor/options_page/options', array($this, 'add_settings_page_options') );
            add_filter(
                'vg_sheet_editor/provider/post/get_item_meta',
                array($this, 'modify_files_order_for_reading'),
                10,
                3
            );
            add_filter(
                'vg_sheet_editor/provider/post/update_item_meta',
                array($this, 'modify_files_order_for_saving'),
                10,
                3
            );
        }

        // EDD saves the files using initial indices, which are different than the visible order.
        // so we show the files in normal order on the sheet and convert to the real indices before saving
        function modify_files_order_for_saving( $raw_value, $post_id, $meta_key ) {
            if ( $meta_key === 'edd_download_files' && in_array( get_post_type( $post_id ), $this->post_type ) ) {
                if ( is_array( $raw_value ) ) {
                    $existing_files = get_post_meta( $post_id, $meta_key, true );
                    if ( empty( $existing_files ) ) {
                        return $raw_value;
                    }
                    $file_indices = array();
                    $visible_order = 1;
                    foreach ( $existing_files as $file_index => $file ) {
                        $file_indices[$visible_order] = $file_index;
                        $visible_order++;
                    }
                    $new_files = array();
                    foreach ( $raw_value as $visible_order => $file ) {
                        if ( isset( $file_indices[$visible_order] ) ) {
                            $new_key = $file_indices[$visible_order];
                        } else {
                            end( $file_indices );
                            $new_key = key( $file_indices ) + 1;
                        }
                        $new_files[$new_key] = $file;
                    }
                    $raw_value = $new_files;
                }
            }
            return $raw_value;
        }

        // EDD saves the files using initial indices, which are different than the visible order.
        // so we show the files in normal order on the sheet and convert to the real indices before saving
        function modify_files_order_for_reading( $raw_value, $post_id, $meta_key ) {
            if ( $meta_key === 'edd_download_files' && in_array( get_post_type( $post_id ), $this->post_type ) ) {
                if ( is_array( $raw_value ) ) {
                    $visible_order = 1;
                    $new_files = array();
                    foreach ( $raw_value as $file_index => $file ) {
                        $new_files[$visible_order] = $file;
                        $visible_order++;
                    }
                    $raw_value = $new_files;
                }
            }
            return $raw_value;
        }

        function blacklist_index_column( $columns, $provider ) {
            if ( in_array( $provider, $this->post_type ) ) {
                for ($i = 0; $i < 20; $i++) {
                    $columns[] = 'edd_variable_prices_index_i_' . $i;
                }
                $columns[] = "edd_variable_prices_name_i_0";
                $columns[] = "edd_variable_prices_amount_i_0";
                $columns[] = "edd_variable_prices_sale_price_i_0";
                $columns = array_merge( $columns, array(
                    "edd_download_files_index_i_0",
                    "edd_download_files_attachment_id_i_0",
                    "edd_download_files_thumbnail_size_i_0",
                    "edd_download_files_name_i_0",
                    "edd_download_files_file_i_0",
                    "edd_download_files_condition_i_0"
                ) );
            }
            return $columns;
        }

        /**
         * Add fields to options page
         * @param array $sections
         * @return array
         */
        function add_settings_page_options( $sections ) {
            $fields = array(array(
                'id'       => 'edd_max_files',
                'type'     => 'text',
                'validate' => 'numeric',
                'title'    => __( 'How many files do you add to the products?', vgse_edd_downloads()->textname ),
                'desc'     => __( 'We show 3 columns for every file so you can enter the file name, url, and required price. This is helpful to display only the necessary columns', vgse_edd_downloads()->textname ),
                'default'  => 3,
            ));
            $sections[] = array(
                'icon'   => 'el-icon-cogs',
                'title'  => __( 'EDD sheet', vgse_edd_downloads()->textname ),
                'fields' => $fields,
            );
            return $sections;
        }

        function filter_serialized_column_settings(
            $settings,
            $first_set_keys,
            $field,
            $key,
            $post_type
        ) {
            if ( !in_array( $post_type, $this->post_type ) ) {
                return $settings;
            }
            if ( strpos( $key, 'edd_download_files_condition' ) !== false ) {
                $settings['title'] = str_replace( 'Condition', __( 'Required price', vgse_edd_downloads()->textname ), $settings['title'] );
                $settings['default_value'] = 'all';
            }
            return $settings;
        }

        function filter_cell_data_for_readings(
            $value,
            $id,
            $key,
            $single,
            $context
        ) {
            if ( $context !== 'read' || !in_array( get_post_type( $id ), $this->post_type ) ) {
                return $value;
            }
            return $value;
        }

        function filter_cell_data_for_saving( $new_value, $id, $key ) {
            if ( !in_array( get_post_type( $id ), $this->post_type ) ) {
                return $new_value;
            }
            if ( $key === 'edd_download_files' && is_array( $new_value ) ) {
                foreach ( $new_value as $index => $file ) {
                    if ( empty( $file['condition'] ) ) {
                        $new_value[$index]['condition'] = 'all';
                        $file['condition'] = 'all';
                    }
                    $variable_prices = VGSE()->helpers->get_current_provider()->get_item_meta(
                        $id,
                        'edd_variable_prices',
                        true,
                        'save',
                        true
                    );
                    if ( !is_numeric( $file['condition'] ) && $file['condition'] !== 'all' && !empty( $variable_prices ) && ($price = wp_list_filter( $variable_prices, array(
                        'name' => $file['condition'],
                    ) )) ) {
                        $new_value[$index]['condition'] = $price[0]['index'];
                    }
                }
            }
            return $new_value;
        }

        function get_columns() {
            $settings = get_option( VGSE()->options_key, array() );
            $columns = array();
            $columns['_variable_pricing'] = array(
                'data_type'         => 'meta_data',
                'title'             => __( 'Variable pricing', vgse_edd_downloads()->textname ),
                'allow_to_save'     => true,
                'supports_formulas' => true,
                'allow_to_hide'     => true,
                'allow_to_rename'   => true,
                'formatted'         => array(
                    'type'              => 'checkbox',
                    'checkedTemplate'   => '1',
                    'uncheckedTemplate' => '',
                ),
                'default_value'     => '',
            );
            $columns['_edd_download_sales'] = array(
                'data_type'     => 'meta_data',
                'title'         => __( 'Sales', vgse_edd_downloads()->textname ),
                'allow_to_save' => false,
            );
            $columns['_edd_download_earnings'] = array(
                'data_type'     => 'meta_data',
                'title'         => __( 'Earnings', vgse_edd_downloads()->textname ),
                'allow_to_save' => false,
            );
            $serialized_fields = array();
            $serialized_fields[] = array(
                'sample_field_key'               => 'edd_variable_prices',
                'sample_field'                   => array(array(
                    'name'   => '',
                    'amount' => '',
                )),
                'column_width'                   => 175,
                'column_title_prefix'            => __( 'Variable Price', vgse_edd_downloads()->textname ),
                'level'                          => ( !empty( $settings['edd_max_variable_prices'] ) ? (int) $settings['edd_max_variable_prices'] : 3 ),
                'allowed_post_types'             => array(vgse_edd_downloads()->post_type),
                'is_single_level'                => false,
                'allow_in_wc_product_variations' => false,
                'index_start'                    => 1,
                'label_index_start'              => 1,
                'wpse_source'                    => 'edd_sheet',
            );
            $serialized_fields[] = array(
                'sample_field_key'               => 'edd_download_files',
                'sample_field'                   => array(array(
                    'file'      => '',
                    'name'      => '',
                    'condition' => '',
                )),
                'column_width'                   => 150,
                'column_title_prefix'            => __( 'Files', vgse_edd_downloads()->textname ),
                'level'                          => ( !empty( $settings['edd_max_files'] ) ? (int) $settings['edd_max_files'] : 3 ),
                'allowed_post_types'             => array(vgse_edd_downloads()->post_type),
                'is_single_level'                => false,
                'allow_in_wc_product_variations' => false,
                'index_start'                    => 1,
                'label_index_start'              => 1,
                'wpse_source'                    => 'edd_sheet',
            );
            $serialized_fields[] = array(
                'sample_field_key'               => '_edd_bundled_products',
                'sample_field'                   => array(''),
                'column_width'                   => 150,
                'column_title_prefix'            => __( 'Bundle product', vgse_edd_downloads()->textname ),
                'level'                          => ( !empty( $settings['edd_max_bundle_products'] ) ? (int) $settings['edd_max_bundle_products'] : 3 ),
                'allowed_post_types'             => array(vgse_edd_downloads()->post_type),
                'is_single_level'                => true,
                'allow_in_wc_product_variations' => false,
                'wpse_source'                    => 'edd_sheet',
            );
            $serialized_fields[] = array(
                'sample_field_key'               => '_edd_bundled_products_conditions',
                'sample_field'                   => array(''),
                'column_width'                   => 150,
                'column_title_prefix'            => __( 'Bundle required price', vgse_edd_downloads()->textname ),
                'level'                          => ( !empty( $settings['edd_max_bundle_products'] ) ? (int) $settings['edd_max_bundle_products'] : 3 ),
                'allowed_post_types'             => array(vgse_edd_downloads()->post_type),
                'is_single_level'                => true,
                'allow_in_wc_product_variations' => false,
                'wpse_source'                    => 'edd_sheet',
            );
            foreach ( $serialized_fields as $serialized_field ) {
                // Very late init, so we can overwrite the automatic custom columns
                $serialized_field['column_init_priority'] = 80;
                new WP_Sheet_Editor_Serialized_Field($serialized_field);
            }
            return $columns;
        }

    }

    new WPSE_EDD_Downloads_Sheet();
}