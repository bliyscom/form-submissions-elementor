<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Form_Submissions_Table_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'form-submissions-table';
    }

    public function get_title() {
        return esc_html__( 'Form Submissions Table', 'form-submissions-table-for-elementor' );
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_keywords() {
        return [ 'form', 'submissions', 'table' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__( 'Content', 'form-submissions-table-for-elementor' ),
            ]
        );

        $forms = $this->get_available_forms();
        $this->add_control(
            'form_name',
            [
                'label' => esc_html__( 'Select Form', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $forms,
                'default' => '',
            ]
        );

        $this->add_control(
            'fields',
            [
                'label' => esc_html__( 'Fields to Display (comma-separated, leave empty for all)', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => '',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_table',
            [
                'label' => esc_html__( 'Table Style', 'form-submissions-table-for-elementor' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'table_border_color',
            [
                'label' => esc_html__( 'Border Color', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} table' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_background',
            [
                'label' => esc_html__( 'Header Background', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} th' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cell_padding',
            [
                'label' => esc_html__( 'Cell Padding', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} td, {{WRAPPER}} th' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} table',
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => esc_html__( 'Items per Page', 'form-submissions-table-for-elementor' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '10' => '10',
                    '20' => '20',
                    '50' => '50',
                    '100' => '100',
                ],
                'default' => '10',
            ]
        );

        $this->end_controls_section();
    }

    private function get_available_forms() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $results = $wpdb->get_results( "SELECT DISTINCT form_name FROM {$wpdb->prefix}e_submissions" );
        $forms = [];
        foreach ( $results as $result ) {
            $forms[ $result->form_name ] = $result->form_name;
        }
        return $forms;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $form_name = $settings['form_name'];
        if ( empty( $form_name ) ) {
            echo '<p>' . esc_html__( 'Please select a form.', 'form-submissions-table-for-elementor' ) . '</p>';
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in $nonce_valid.
        $nonce_valid = ! empty( $_GET['efs_nonce'] ) && wp_verify_nonce( $_GET['efs_nonce'], 'efs_table' );
        global $wpdb;
        $per_page = ( $nonce_valid && ! empty( $_GET['per_page'] ) ) ? intval( $_GET['per_page'] ) : intval( $settings['per_page'] );
        $per_page = min( $per_page, 100 );
        $current_page = ( $nonce_valid && ! empty( $_GET['efs_page'] ) ) ? intval( $_GET['efs_page'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $total_submissions = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}e_submissions WHERE form_name = %s", $form_name ) );
        $total_pages = ceil( $total_submissions / $per_page );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $submissions = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}e_submissions WHERE form_name = %s LIMIT %d OFFSET %d", $form_name, $per_page, $offset ) );

        if ( empty( $submissions ) ) {
            echo '<p>' . esc_html__( 'No submissions found.', 'form-submissions-table-for-elementor' ) . '</p>';
            return;
        }

        // Get unique fields
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $all_fields = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT `key` FROM {$wpdb->prefix}e_submissions_values WHERE submission_id IN (SELECT id FROM {$wpdb->prefix}e_submissions WHERE form_name = %s)", $form_name ) );
        $fields = [];
        foreach ( $all_fields as $field ) {
            $fields[] = $field->key;
        }

        $selected_fields = ! empty( $settings['fields'] ) ? array_map( 'trim', explode( ',', $settings['fields'] ) ) : $fields;

        echo '<table style="border: 1px solid; width: 100%;">';
        echo '<thead><tr>';
        foreach ( $selected_fields as $field ) {
            echo '<th>' . esc_html( $field ) . '</th>';
        }
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $submissions as $submission ) {
            echo '<tr>';
            foreach ( $selected_fields as $field ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $value = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM {$wpdb->prefix}e_submissions_values WHERE submission_id = %d AND `key` = %s", $submission->id, $field ) );
                echo '<td>' . esc_html( $value ) . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
        // Pagination
        if ( $total_pages > 1 ) {
            echo '<div class="pagination">';
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                $url = add_query_arg( [ 'efs_page' => $i, 'per_page' => $per_page, 'efs_nonce' => wp_create_nonce( 'efs_table' ) ], '' );
                echo '<a href="' . esc_url( $url ) . '">' . esc_html( $i ) . '</a> ';
            }
            echo '</div>';
        }

        // Per page selector
        echo '<form method="get">';
        wp_nonce_field( 'efs_table', 'efs_nonce' );
        echo '<select name="per_page" onchange="this.form.submit()">';
        foreach ( [10,20,50,100] as $opt ) {
            $selected = $per_page == $opt ? 'selected' : '';
            echo '<option value="' . esc_attr( $opt ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $opt ) . '</option>';
        }
        echo '</select>';
        echo '</form>';
    }
}